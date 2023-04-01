var __classPrivateFieldGet =
    (this && this.__classPrivateFieldGet) ||
    function (receiver, state, kind, f) {
        if (kind === "a" && !f)
            throw new TypeError(
                "Private accessor was defined without a getter"
            );
        if (
            typeof state === "function"
                ? receiver !== state || !f
                : !state.has(receiver)
        )
            throw new TypeError(
                "Cannot read private member from an object whose class did not declare it"
            );
        return kind === "m"
            ? f
            : kind === "a"
            ? f.call(receiver)
            : f
            ? f.value
            : state.get(receiver);
    };
var _MarqueeTextElement_renderRoot;
const DEFAULT_DURATION = "5s";
class MarqueeTextElement extends HTMLElement {
    constructor() {
        super(...arguments);
        _MarqueeTextElement_renderRoot.set(
            this,
            this.attachShadow({ mode: "open" })
        );
    }
    get duration() {
        const value = this.getAttribute("duration");
        return value !== null && value !== void 0 ? value : DEFAULT_DURATION;
    }
    set duration(value) {
        this.setAttribute("duration", value);
    }
    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;
        if (newValue === null) return;
        if (newValue) this.style.setProperty("--animation-duration", newValue);
    }
    connectedCallback() {
        __classPrivateFieldGet(
            this,
            _MarqueeTextElement_renderRoot,
            "f"
        ).innerHTML = `
    <style>
    @keyframes marqueeeee {
        0% {
          translate: 100%;
        }
        35% {
          translate: 0%;
        }
        65% {
            translate: 0%;
        }
        100% {
            translate: -100%;
        }
      }
      :host slot {
        animation: var(--animation-duration, ${DEFAULT_DURATION}) linear infinite marqueeeee;
        display: inline-block;
      }
      :host {
        overflow: hidden;
        max-width: 100vw;
        display: block;
      }
      @media (prefers-reduced-motion) {
        :host slot {
          animation: none;
        }
      }
    </style>
    <slot></slot>
    `;
    }
}
_MarqueeTextElement_renderRoot = new WeakMap();
MarqueeTextElement.observedAttributes = ["duration"];
export default MarqueeTextElement;
if (!window.customElements.get("marquee-text")) {
    window.MarqueeTextElement = MarqueeTextElement;
    window.customElements.define("marquee-text", MarqueeTextElement);
}
