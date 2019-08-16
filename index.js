function parseANSI(string) {

  // Classname mapping
  const values = {
    1: 'term-bold',
    2: 'term-faint',
    3: 'term-italic',
    4: 'term-underline',
    8: 'term-conceal',
    9: 'term-cross',
    30: 'term-fg-black',
    31: 'term-fg-red',
    32: 'term-fg-green',
    33: 'term-fg-yellow',
    34: 'term-fg-blue',
    35: 'term-fg-magenta',
    36: 'term-fg-cyan',
    37: 'term-fg-white',
    90: 'term-fg-bright-black',
    91: 'term-fg-bright-red',
    92: 'term-fg-bright-green',
    93: 'term-fg-bright-yellow',
    94: 'term-fg-bright-blue',
    95: 'term-fg-bright-magenta',
    96: 'term-fg-bright-cyan',
    97: 'term-fg-bright-white',
    40: 'term-bg-black',
    41: 'term-bg-red',
    42: 'term-bg-green',
    43: 'term-bg-yellow',
    44: 'term-bg-blue',
    45: 'term-bg-magenta',
    46: 'term-bg-cyan',
    47: 'term-bg-white',
    100: 'term-bg-bright-black',
    101: 'term-bg-bright-red',
    102: 'term-bg-bright-green',
    103: 'term-bg-bright-yellow',
    104: 'term-bg-bright-blue',
    105: 'term-bg-bright-magenta',
    106: 'term-bg-bright-cyan',
    107: 'term-bg-bright-white'
  };

  // Reset patterns
  const patterns = {
    0: /[0-9]+/,
    21: /^1$/,
    22: /^(1|2)$/,
    23: /^3$/,
    24: /^4$/,
    25: /^(5|6)$/,
    28: /^8$/,
    29: /^9$/,
    39: /^(3|9)[0-7]$/,
    49: /^(4|10)[0-7]$/,
    54: /^(51|52)$/,
    55: /^53$/,
    65: /^6[0-4]$/,
  };

  // The current style context
  let context = [];

  // Whether there is a stray open span tag
  let stray = false;

  // Remove everything from the context matching the reset pattern of
  // the passed code
  function reset(code) {
    if (code in patterns == false) return;
    context = context.filter(item => {
      return patterns[code].test(item.code) === false;
    });
  }

  // Parse the string
  string = string.replace(/\033\[([0-9;]*)([A-Za-z])/g, (match, codes, type) => {

    // Only handle sequences that modify the appearance
    if (type !== 'm') return '';

    codes.split(';').forEach(code => {
      const value = values[code.trim()] || null;

      if (value) {
        // Because fore and background colors must be unique we reset
        // any previously set fore or background colors
        [39, 49].some(key => {

          // Test if the code matches a fore or background color
          if (patterns[key].test(code)) {
            reset(key);
            return true;
          }
        });

        // Add the value to the current context
        return context.push({
          value: value,
          code: code
        });
      }

      // When the value is not defined the code either resets the
      // context with a specific pattern or the sequence is simply
      // removed from the string
      return reset(code);
    });

    let result = '';

    if (stray) {
      stray = false;
      result +='</span>';
    }

    if (context.length) {
      stray = true;
      result += `<span class="${context.map(item => item.value).join(' ')}">`;
    }

    return result;
  });

  // Close any stray open span
  if (stray) string += '</span>';

  return string;
}

panel.plugin('lukaskleinschmidt/terminal', {
  sections: {
    terminal: {
      data: function () {
        return {
          autoscroll: true,
          error: null,
          isLoading: false,
          options: {
            delay: null,
            endpoint: null,
            headline: null,
            help: null,
            start: null,
            stop: null,
            theme: null,
          },
          show: 'stdout',
          terminal: {
            status: null,
            stderr: '',
            stdout: '',
          },
          timestamp: null,
        }
      },
      computed: {
        icon() {
          return this.terminal.status ? 'loader' : 'circle-outline';
        },
        output() {
          return parseANSI(this.terminal[this.show]);
        },
        url() {
          return [this.parent, this.options.endpoint, this.name].join('/');
        },
        theme() {
          const theme = this.options.theme;
          return theme ? 'terminal-section-' + theme : '';
        },
        status() {
          return this.terminal.status;
        }
      },
      created() {
        this.isLoading = true;

        this.load()
          .then(response => {
            this.isLoading = false;
            this.options   = response.options;
            this.terminal  = response.terminal;
          })
          .catch(error => {
            this.isLoading = false;
            this.error = error.message;
          });
      },
      watch: {
        output() {
          if (this.autoscroll) {
            const element = this.$refs.output || null;
            if (! element) return;

            this.$nextTick(() => {
              element.scrollTo(0, element.scrollHeight);
            });
          }
        },
        status(status) {
          if (status) this.poll();
        }
      },
      methods: {
        handleResponse(response) {
          this.terminal = response;

          const stdout = this.terminal.stdout;
          const stderr = this.terminal.stderr;

          // Switch to the tab with the first incoming output
          if (! stdout && stderr) {
            this.show = 'stderr';
          } else if (! stderr && stdout) {
            this.show = 'stdout';
          }
        },
        handleSubmit() {
          if (this.status === false && false) {
            return this.$refs.dialog.open();
          }

          this.submit();
        },
        submit() {
          if (this.$refs.dialog.isOpen) {
            this.$refs.dialog.close();
          }

          this.status ? this.stop() : this.start();
        },
        stop() {
          this.$api
            .post(this.url, { action: 'stop' })
            .then(this.handleResponse);
        },
        poll() {
          const now = Date.now();
          const delay = this.timestamp - now + this.options.delay;

          if (delay > 0) {

            return setTimeout(this.poll, delay);
          }

          // Set the current timestamp
          this.timestamp = now;

          this.$api.get(this.url, null, {}, true).then(response => {
            const element = this.$refs.output;
            const { offsetHeight, scrollTop, scrollHeight} = element;

            // Figure out whether autoscroll should kick in or not
            this.autoscroll = (offsetHeight + scrollTop) > (scrollHeight - 20);

            // Update data
            this.handleResponse(response);

            // Continue polling
            if (this.status === true) {
              this.poll();
            }
          });
        },
        start() {
          this.terminal.status = true;
          this.terminal.stderr = '';
          this.terminal.stdout = '';

          // Set the current timestamp
          this.timestamp = Date.now();

          this.$api
            .post(this.url, { action: 'start' })
            .then(this.handleResponse);
        }
      },
      template: `
        <section v-if="isLoading === false" :class="['terminal-section', theme]">

          <header class="k-section-header">
            <k-headline>
              {{ options.headline }}
            </k-headline>

            <k-button-group v-if="! error">
              <k-button :icon="icon" @click="handleSubmit">{{ status ? options.stop : options.start }}</k-button>
            </k-button-group>
          </header>

          <template v-if="error">
            <k-box theme="negative">
              <k-text size="small">
                <strong>{{ $t("error.section.notLoaded", { name: name }) }}:</strong>
                {{ error }}
              </k-text>
            </k-box>
          </template>

          <template v-else>
            <div class="terminal-output">
              <nav>
                <div>
                  <k-button @click="show = 'stdout'">Output</k-button>
                </div>
                <div>
                  <k-button @click="show = 'stderr'" :disabled="! terminal.stderr">Errors</k-button>
                </div>
              </nav>
              <pre ref="output"><code v-html="output" /></pre>
            </div>

            <footer class="k-collection-footer">
              <k-text
                v-if="options.help"
                theme="help"
                class="k-collection-help"
                v-html="options.help"
              />
            </footer>
          </template>

          <k-dialog
            ref="dialog"
            button="Start"
            theme="positive"
            icon="wand"
            @submit="submit"
          >
            <k-text>
              Do you really want to delete the user:<br>
              <strong>bastian</strong>?
            </k-text>
          </k-dialog>

        </section>
      `
    }
  }
});
