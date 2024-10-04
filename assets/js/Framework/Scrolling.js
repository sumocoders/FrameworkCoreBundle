const Scrolling = function (scrollToTop) {
  document.querySelectorAll('a[href*=\\#]').forEach((link) => {
    link.addEventListener('click', scrollTo)
  })

  // On long pages, show the Back to top link
  if (scrollToTop) {
    window.addEventListener('scroll', () => {
      if (document.body.scrollY > 1000) {
        scrollToTop.classList.remove('d-none')
        scrollToTop.classList.add('show')
      } else {
        scrollToTop.classList.add('d-none')
        scrollToTop.classList.remove('show')
      }
    })
  }
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
