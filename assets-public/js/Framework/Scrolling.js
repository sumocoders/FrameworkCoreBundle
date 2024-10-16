const Scrolling = function () {
  document.querySelectorAll('a[href*=\\#]').forEach((link) => {
    link.addEventListener('click', scrollTo)
  })
}

const scrollTo = (event) => {
  const anchor = event.currentTarget
  const href = anchor.href
  const url = href.substr(0, href.indexOf('#'))
  const hash = href.substr(href.indexOf('#'))

  // If the hash is only a hash, there's nowhere to go to
  if (hash === '#') {
    return
  }

  const targetElement = document.querySelector(hash)

  /* check if we have an url, and if it is on the current page and the element exists disabled for nav-tabs */
  if (
    (url === '' || url.indexOf(document.location.pathname) >= 0) &&
    !anchor.hasAttribute('data-no-scroll') &&
    targetElement != null
  ) {
    window.scroll({
      top: targetElement.scrollHeight,
      left: 0,
      behavior: 'smooth'
    })
  }
}

export default Scrolling
