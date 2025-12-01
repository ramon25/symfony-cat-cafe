import { Controller } from '@hotwired/stimulus';

/**
 * Tabs controller for switching between content panels
 *
 * Usage:
 *   <div data-controller="tabs"
 *        data-tabs-active-class="border-purple-500 text-purple-600"
 *        data-tabs-inactive-class="border-transparent text-gray-500">
 *     <button data-tabs-target="tab" data-action="click->tabs#select">Tab 1</button>
 *     <button data-tabs-target="tab" data-action="click->tabs#select">Tab 2</button>
 *     <div data-tabs-target="panel">Panel 1</div>
 *     <div data-tabs-target="panel" class="hidden">Panel 2</div>
 *   </div>
 */
export default class extends Controller {
    static targets = ['tab', 'panel'];
    static classes = ['active', 'inactive'];

    connect() {
        // Initialize: ensure first tab is active
        if (this.tabTargets.length > 0) {
            this.showTab(0);
        }
    }

    select(event) {
        const clickedTab = event.currentTarget;
        const index = this.tabTargets.indexOf(clickedTab);
        if (index !== -1) {
            this.showTab(index);
        }
    }

    showTab(index) {
        // Update tabs
        this.tabTargets.forEach((tab, i) => {
            if (i === index) {
                // Active tab
                tab.setAttribute('aria-selected', 'true');
                if (this.hasInactiveClass) {
                    this.inactiveClasses.forEach(cls => tab.classList.remove(cls));
                }
                if (this.hasActiveClass) {
                    this.activeClasses.forEach(cls => tab.classList.add(cls));
                }
            } else {
                // Inactive tabs
                tab.setAttribute('aria-selected', 'false');
                if (this.hasActiveClass) {
                    this.activeClasses.forEach(cls => tab.classList.remove(cls));
                }
                if (this.hasInactiveClass) {
                    this.inactiveClasses.forEach(cls => tab.classList.add(cls));
                }
            }
        });

        // Update panels
        this.panelTargets.forEach((panel, i) => {
            if (i === index) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }
}
