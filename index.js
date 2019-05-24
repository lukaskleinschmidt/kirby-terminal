panel.plugin('lukaskleinschmidt/tasks', {
  sections: {
    task: {
      data: function () {
        return {
          command: null,
          endpoint: null,
          headline: null,
          path: null,
          status: null,
          stdout: null,
          stderr: null,
          text: null,
        }
      },
      computed: {
        url: function () {
          var parts = [
            this.endpoint,
            this.command,
          ];

          if (this.path) {
            parts.push(this.path);
          }

          return parts.join('/');
        },
        icon: function () {
          if (this.status) {
            return 'loader';
          }

          return 'circle-outline';
        }
      },
      created: function () {
        this.load().then(response => {
          this.command = response.command;
          this.endpoint = response.endpoint;
          this.headline = response.headline;
          this.path = response.path;
          this.status = response.status.status;
          this.stdout = response.status.stdout;
          this.stderr = response.status.stderr;
          this.text = response.text;
        });
      },
      mounted: function () {
        this.$refs.stdout.$refs.input.readOnly = true;
        // this.$refs.stderr.$refs.input.readOnly = true;
      },
      watch: {
        status: function (status) {
          if (status) {
            this.poll();
          }
        }
      },
      methods: {
        update: function (response) {
          this.status = response.status;
          this.stdout = response.stdout;
          this.stderr = response.stderr;
        },
        run: function () {

          // Close the confirm dialog
          // this.$refs.dialog.close();

          this.$api.post(this.url).then(this.update);
        },
        kill: function () {
          this.$api.delete(this.url).then(this.update);
        },
        poll: function () {
          this.$api.get(this.url).then(response => {

            // Update state
            this.update(response);

            // Continue polling
            if (this.status === true) {
              setTimeout(this.poll, 1000);
            }
          });
        },
        onClick: function () {
          this.status ? this.kill() : this.run();
        }
      },
      template: `
        <section class="command-section">
          <k-headline>{{ headline }}</k-headline>
          <k-text>{{ text }}</k-text>

          <br />

          <k-button @click="onClick" :icon="icon">
            {{ status ? 'Stop' : 'Start' }}
          </k-button>

          <br />
          <br />

          <nav>
            <k-button>
              Output
              <k-icon type="circle-outline" />
            </k-button>
            <k-button>
              Errors
              <k-icon type="circle" />
            </k-button>
          </nav>


          <div class="command-stdout">
            <!--<div style="white-space: pre-wrap" v-html="stdout" />-->
            <k-textarea-input v-model="stdout" :buttons="false" ref="stdout" />
          </div>

          <div class="command-stderr">
            <k-textarea-input v-model="stderr" :buttons="false" ref="stderr" />
          </div>

          <k-dialog ref="dialog" icon="upload" @submit="run">
            <k-text>
              Möchtest du die Inhalte jetzt auf dem Live System synchronisieren?
            </k-text>
          </k-dialog>
        </section>
      `
    }
  }
});
