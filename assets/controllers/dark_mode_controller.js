import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['icon']

    connect() {
        // Check for saved preference or system preference
        const savedTheme = localStorage.getItem('theme')
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            this.enableDarkMode(false)
        } else {
            this.disableDarkMode(false)
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                if (e.matches) {
                    this.enableDarkMode(false)
                } else {
                    this.disableDarkMode(false)
                }
            }
        })
    }

    toggle() {
        if (document.documentElement.classList.contains('dark')) {
            this.disableDarkMode(true)
        } else {
            this.enableDarkMode(true)
        }
    }

    enableDarkMode(save) {
        document.documentElement.classList.add('dark')
        if (save) {
            localStorage.setItem('theme', 'dark')
        }
        this.updateIcon()
    }

    disableDarkMode(save) {
        document.documentElement.classList.remove('dark')
        if (save) {
            localStorage.setItem('theme', 'light')
        }
        this.updateIcon()
    }

    updateIcon() {
        if (!this.hasIconTarget) return

        const isDark = document.documentElement.classList.contains('dark')
        // Sun icon for dark mode (click to switch to light), Moon icon for light mode (click to switch to dark)
        this.iconTarget.innerHTML = isDark
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>'
    }
}
