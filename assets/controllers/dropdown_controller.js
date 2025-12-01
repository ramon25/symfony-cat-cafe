import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["menu"];

    connect() {
        // Close dropdown when clicking outside
        this.boundClickOutside = this.clickOutside.bind(this);
        document.addEventListener("click", this.boundClickOutside);

        // Listen for other dropdowns opening
        this.boundCloseFromOther = this.closeFromOther.bind(this);
        document.addEventListener("dropdown:open", this.boundCloseFromOther);
    }

    disconnect() {
        document.removeEventListener("click", this.boundClickOutside);
        document.removeEventListener("dropdown:open", this.boundCloseFromOther);
    }

    toggle(event) {
        event.stopPropagation();
        const isOpening = this.menuTarget.classList.contains("hidden");

        if (isOpening) {
            // Close all other dropdowns first
            document.dispatchEvent(new CustomEvent("dropdown:open", {
                detail: { source: this.element }
            }));
        }

        this.menuTarget.classList.toggle("hidden");
    }

    closeFromOther(event) {
        // Close this dropdown if another one is opening
        if (event.detail.source !== this.element) {
            this.menuTarget.classList.add("hidden");
        }
    }

    clickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.menuTarget.classList.add("hidden");
        }
    }
}
