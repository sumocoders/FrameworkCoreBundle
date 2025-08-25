import { Controller } from '@hotwired/stimulus'
import { setCookie } from 'sumocoders/cookie'
import { readCookie } from 'sumocoders/cookie'

export default class extends Controller {
  connect() {
    // This runs when the controller's element is added to the DOM
    this.showTheme()
    console.log('Theme controller connected')
  }

  showTheme () {
    const getStoredTheme = () => readCookie('theme')
    const setStoredTheme = theme => setCookie('theme', theme)

    const getPreferredTheme = () => {
      const storedTheme = getStoredTheme()
      if (storedTheme) {
        return storedTheme
      }

      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const setTheme = theme => {
      if (theme === 'auto') {
        document.documentElement.setAttribute('data-bs-theme', (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'))
      } else {
        document.documentElement.setAttribute('data-bs-theme', theme)
      }
    }

    setTheme(getPreferredTheme())

    const showActiveTheme = (theme, focus = false) => {
      const themeSwitcher = document.querySelector('#bd-theme')

      if (!themeSwitcher) {
        return
      }

      const themeSwitcherText = document.querySelector('#bd-theme-text')
      const activeThemeIcon = document.querySelector('[data-bs-theme-icon]')
      const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
      const iconOfActiveBtn = btnToActive.querySelector('i')

      document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
        element.classList.remove('active')
        element.setAttribute('aria-pressed', 'false')
      })

      btnToActive.classList.add('active')
      btnToActive.setAttribute('aria-pressed', 'true')
      activeThemeIcon.classList.remove(...activeThemeIcon.classList)
      activeThemeIcon.classList.add(...iconOfActiveBtn.classList)
      const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`
      themeSwitcher.setAttribute('aria-label', themeSwitcherLabel)

      if (focus) {
        themeSwitcher.focus()
      }
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      const storedTheme = getStoredTheme()
      if (storedTheme !== 'light' && storedTheme !== 'dark') {
        setTheme(getPreferredTheme())
        this.toggleDarkAssets(getPreferredTheme())
      }
    })

    document.querySelectorAll('[data-bs-theme-value]')
      .forEach(toggle => {
        toggle.addEventListener('click', () => {
          const theme = toggle.getAttribute('data-bs-theme-value')
          setStoredTheme(theme)
          setTheme(theme)
          showActiveTheme(theme, true)
          this.toggleDarkAssets(theme)
        })
      })
  }

  toggleDarkAssets (theme) {
    const logo = document.querySelector('[data-navbar-logo]')
    const darkLogo = document.querySelector('[data-navbar-logo-dark]')

    if (theme === 'dark') {
      if (logo) {
        darkLogo.classList.remove('d-none')
        logo.classList.add('d-none')
      }
    } else {
      if (logo) {
        darkLogo.classList.add('d-none')
        logo.classList.remove('d-none')
      }
    }
  }
}
