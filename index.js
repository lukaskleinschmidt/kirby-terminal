panel.plugin('lukaskleinschmidt/tasks', {
  sections: {
    task: {
      data: function () {
        return {
          command: null,
          endpoint: null,
          path: null,
          status: null,
          stdout: null,
          stderr: null,
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
        }
      },
      created: function () {
        this.load().then(response => {
          this.command = response.command;
          this.endpoint = response.endpoint;
          this.path = response.path;
          this.status = response.status.status;
          this.stdout = response.status.stdout;
          this.stderr = response.status.stderr;
        });
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
          this.$refs.dialog.close();

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
      },
      template: `
        <section class="command-section">
          <k-headline>Test</k-headline>
          <k-text>Lorem Ipsum …</k-text>

          <br />
          <k-button @click="$refs.dialog.open()" icon="wand" :disabled="status">Run</k-button>
          <br />
          <br />

          <div class="command-stdout">
            <k-input v-model="stdout" name="text" type="textarea" :buttons="false" :disabled="true" />
          </div>

          <div class="command-stderr">
            <k-input v-model="stderr" name="text" type="textarea" :buttons="false" :disabled="true" />
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
