<template>
  <section class="task-section">
    <k-headline>{{ headline }}</k-headline>
    <k-text>{{ text }}</k-text>

    <br />

    <k-button @click="handleClick" :icon="icon">
      {{ status ? 'Stop' : 'Start' }}
    </k-button>

    <br />
    <br />

    <div class="task-terminal">
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

      <pre class="task-stdout">
        <code v-html="parseANSI(stdout)" />
      </pre>

      <pre class="task-stderr">
        <code v-html="parseANSI(stderr)" />
      </pre>
    </div>
  </section>
</template>

<script>
import { parseANSI } from './helpers';

export default {
  data() {
    return {
      command: null,
      endpoint: null,
      headline: null,
      path: null,
      status: null,
      stdout: '',
      stderr: '',
      text: null,
    }
  },
  computed: {
    url() {
      var parts = [
        this.endpoint,
        this.command,
      ];

      if (this.path) parts.push(this.path);

      return parts.join('/');
    },
    icon() {
      if (this.status) {
        return 'loader';
      }

      return 'circle-outline';
    }
  },
  mounted() {
    console.log(this.parent);
  },
  created() {
    this.load().then(response => {
      this.command = response.command;
      this.endpoint = response.endpoint;
      this.headline = response.headline;
      this.path = response.path;
      this.delay = response.delay;
      this.status = response.status.status;
      this.stdout = response.status.stdout;
      this.stderr = response.status.stderr;
      this.text = response.text;
    });
  },
  watch: {
    status(status) {
      if (status) this.poll();
    }
  },
  methods: {
    handleClick() {
      this.status ? this.kill() : this.run();
    },
    handleResponse(response) {
      this.status = response.status;
      this.stdout = response.stdout;
      this.stderr = response.stderr;
    },
    kill() {
      this.$api
        .delete(this.url)
        .then(this.handleResponse);
    },
    parseANSI(value) {
      return parseANSI(value);
    },
    poll() {
      const onStart = this.$api.config.onStart;

      // Make the next request silent so the loading spinner and mous loading
      // symbol will not appear
      this.$api.config.onStart = (requestId) => {
        this.$api.requests.push(requestId);
        this.$api.config.onStart = onStart;
      };

      this.$api.get(this.url).then(response => {

        // Update state
        this.handleResponse(response);

        // Continue polling
        if (this.status === true) {
          setTimeout(this.poll, this.delay);
        }
      });
    },
    run() {
      this.status = true;
      this.stdout = '';
      this.stderr = '';

      this.$api
        .post(this.url)
        .then(this.handleResponse);
    }
  }
}
</script>

<style lang="scss">

</style>
