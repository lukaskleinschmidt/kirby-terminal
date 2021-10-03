import Terminal from "./components/Terminal.vue";
import "./index.css";

window.panel.plugin("lukaskleinschmidt/terminal", {
  sections: {
    terminal: Terminal,
  },
});
