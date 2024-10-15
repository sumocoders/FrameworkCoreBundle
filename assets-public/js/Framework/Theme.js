import { setCookie, readCookie } from './Cookies.js'

const Theme = function () {
  const togglers = document.querySelectorAll('[data-theme-toggler]')

  togglers.forEach(toggler => {
    toggler.addEventListener('change', (event) => { handleToggleTheme(event) })
    toggler.addEventListener('hide.bs.dropdown', (event) => { handleDropdownHiding(event) })
  })

  // if cookie is not yet set, get the theme of the device, default light
  if (readCookie('theme') == null) {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      setCookie('theme', 'dark')
      // set switch toggle checked
      togglers.forEach(toggler => {
        toggler.checked = true
      })
    } else {
      setCookie('theme', 'light')
    }
  }

  // show the theme
  showTheme(readCookie('theme'))
}

const showTheme = function (themeToBe) {
  const darkThemePath = document.querySelector('body').dataset.themePath
  const darkStyleLinkTag = document.querySelector('link[rel=stylesheet][href="' + darkThemePath + '"]')
  const body = document.querySelector('body')
  const logo = document.querySelector('[data-navbar-logo]')
  const darkLogo = document.querySelector('[data-navbar-logo-dark]')

  if (themeToBe === 'dark') {
    if (darkStyleLinkTag === null) {
      addDarkStyleLinkToHead()
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

const addDarkStyleLinkToHead = function () {
  const darkThemePath = document.querySelector('body').dataset.themePath
  const link = document.createElement('link')
  link.type = 'text/css'
  link.rel = 'stylesheet'
  link.href = darkThemePath
  document.querySelector('head').appendChild(link)
}

const handleToggleTheme = function (event) {
  // set new theme
  let themeToBe = 'light'
  if (event.target.checked) {
    themeToBe = 'dark'
  }

  // set cookie
  setCookie('theme', themeToBe)

  // show the theme
  showTheme(themeToBe)
}

const handleDropdownHiding = function (event) {
  // do not close dropdown when toggle is clicked
  if (event.clickEvent.length && event.clickEvent.target.closest('[data-theme-toggler-wrapper]').length) {
    event.preventDefault()
  }
}

export default Theme
