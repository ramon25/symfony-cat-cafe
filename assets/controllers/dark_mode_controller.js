import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['icon']

    connect() {
        const savedTheme = localStorage.getItem('theme')

        if (savedTheme === 'dark') {
            this.applyDark()
        } else if (savedTheme === 'light') {
            this.applyLight()
        } else {
            // System mode (no saved preference)
            this.applySystem()
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (!localStorage.getItem('theme')) {
                this.applySystem()
            }
        })
    }

    toggle() {
        const savedTheme = localStorage.getItem('theme')

        // Cycle: light → dark → system → light
        if (savedTheme === 'light') {
            localStorage.setItem('theme', 'dark')
            this.applyDark()
        } else if (savedTheme === 'dark') {
            localStorage.removeItem('theme')
            this.applySystem()
        } else {
            // System mode → light
            localStorage.setItem('theme', 'light')
            this.applyLight()
        }
    }

    applyDark() {
        document.documentElement.classList.add('dark')
        this.updateIcon('dark')
    }

    applyLight() {
        document.documentElement.classList.remove('dark')
        this.updateIcon('light')
    }

    applySystem() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        if (prefersDark) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
        this.updateIcon('system')
    }

    updateIcon(mode) {
        if (!this.hasIconTarget) return

        const icons = {
            // Sun icon for light mode
            light: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>',
            // Moon icon for dark mode
            dark: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>',
            // Computer/monitor icon for system mode
            system: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>'
        }

        this.iconTarget.innerHTML = icons[mode]
    }
}
