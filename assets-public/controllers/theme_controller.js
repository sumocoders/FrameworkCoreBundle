import { Controller } from '@hotwired/stimulus'
import { setCookie } from 'sumocoders/cookie'

export default class extends Controller {
  toggle (event) {
    let themeToBe = 'light'
    if (event.target.checked) {
      themeToBe = 'dark'
    }

    setCookie('theme', themeToBe)

    this.showTheme(themeToBe)
  }

  stopDropdownHiding (event) {
    if (event.clickEvent.target.closest('[data-controller="theme"]') !== null) {
      event.preventDefault()
    }
  }

  showTheme (themeToBe) {
    const darkThemePath = document.querySelector('body').dataset.themePath
    const darkStyleLinkTag = document.querySelector('link[rel=stylesheet][href="' + darkThemePath + '"]')
    const body = document.querySelector('body')
    const logo = document.querySelector('[data-navbar-logo]')
    const darkLogo = document.querySelector('[data-navbar-logo-dark]')

    if (themeToBe === 'dark') {
      if (darkStyleLinkTag === null) {
        this.addDarkStyleLinkToHead()
      }
      darkLogo.classList.remove('d-none')
      logo.classList.add('d-none')
      body.setAttribute('data-bs-theme', 'dark')
    } else {
      if (darkStyleLinkTag !== null) {
        darkStyleLinkTag.remove()
      }
      darkLogo.classList.add('d-none')
      logo.classList.remove('d-none')
      body.setAttribute('data-bs-theme', 'light')
    }
  }

  addDarkStyleLinkToHead () {
    const darkThemePath = document.querySelector('body').dataset.themePath
    const link = document.createElement('link')
    link.type = 'text/css'
    link.rel = 'stylesheet'
    link.href = darkThemePath
    document.querySelector('head').appendChild(link)
  }
}
