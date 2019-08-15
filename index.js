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

panel.plugin('lukaskleinschmidt/tasks', {
  sections: {
    task: {
      data: function () {
        return {
          autoscroll: true,
          delay: null,
          endpoint: null,
          headline: null,
          show: 'stdout',
          status: null,
          stderr: '',
          stdout: '',
          text: null,
        }
      },
      computed: {
        icon() {
          return this.status ? 'loader' : 'circle-outline';
        },
        output() {
          return parseANSI(this[this.show]);
        },
        url() {
          return [this.parent, this.endpoint, this.name].join('/');
        }
      },
      created() {
        this.load().then(response => {
          this.delay = response.delay;
          this.endpoint = response.endpoint;
          this.headline = response.headline;
          this.status = response.status.status;
          this.stderr = response.status.stderr;
          this.stdout = response.status.stdout;
          this.text = response.text;
        });
      },
      watch: {
        output() {
          if (this.autoscroll) {
            const element = this.$refs.output;

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
          this.status = response.status;
          this.stderr = response.stderr;
          this.stdout = response.stdout;
        },
        handleSubmit() {
          this.status ? this.kill() : this.run();
        },
        kill() {
          this.$api
            .post(this.url, { action: 'kill' })
            .then(this.handleResponse);
        },
        poll() {
          this.$api.get(this.url, null, {}, true).then(response => {
            const element = this.$refs.output;
            const { offsetHeight, scrollTop, scrollHeight} = element;

            // Figure out whether autoscroll should kick in or not
            this.autoscroll = (offsetHeight + scrollTop) > (scrollHeight - 20);

            // Update data
            this.handleResponse(response);

            // Continue polling
            if (this.status === true) {
              setTimeout(this.poll, this.delay);
            }
          });
        },
        run() {
          this.status = true;
          this.stderr = '';
          this.stdout = '';

          this.$api
            .post(this.url, { action: 'run' })
            .then(this.handleResponse);
        }
      },
      template: `
        <section class="tasks-section">

          <header class="k-section-header">
            <k-headline>
              {{ headline }}
            </k-headline>

            <k-button-group v-if="true">
              <k-button :icon="icon" @click="handleSubmit">{{ status ? 'Stop' : 'Start' }}</k-button>
            </k-button-group>
          </header>

          <div class="tasks-terminal">
            <nav>
              <k-button @click="show = 'stdout'">Output</k-button>
              <k-button @click="show = 'stderr'">Errors</k-button>
            </nav>
            <pre ref="output"><code v-html="output" /></pre>
          </div>

          <footer class="k-collection-footer">
            <k-text
              v-if="help"
              theme="help"
              class="k-collection-help"
              v-html="help"
            />
          </footer>

          <!--
          <k-button @click="$refs.dialog.open()">Open Dialog</k-button>

          <k-dialog
            ref="dialog"
            button="Ausführen"
            theme="positive"
            icon="wand"
          >
            <k-text>
              Do you really want to delete the user:<br>
              <strong>bastian</strong>?
            </k-text>
          </k-dialog>
          -->
        </section>
      `
    }
  }
});
