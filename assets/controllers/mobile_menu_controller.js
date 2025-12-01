import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'openIcon', 'closeIcon', 'overlay'];

    connect() {
        this.isOpen = false;
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.menuTarget.classList.remove('translate-x-full');
        this.menuTarget.classList.add('translate-x-0');
        this.overlayTarget.classList.remove('opacity-0', 'pointer-events-none');
        this.overlayTarget.classList.add('opacity-100');
        this.openIconTarget.classList.add('hidden');
        this.closeIconTarget.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.isOpen = false;
        this.menuTarget.classList.add('translate-x-full');
        this.menuTarget.classList.remove('translate-x-0');
        this.overlayTarget.classList.add('opacity-0', 'pointer-events-none');
        this.overlayTarget.classList.remove('opacity-100');
        this.openIconTarget.classList.remove('hidden');
        this.closeIconTarget.classList.add('hidden');
        document.body.style.overflow = '';
    }
}
