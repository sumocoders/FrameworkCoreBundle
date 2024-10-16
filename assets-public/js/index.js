// Other components
import Scrolling from './Framework/Scrolling.js'

export function Framework () {
  const scrollToTopEl = document.querySelector('[data-role="back-to-top"]')
  Scrolling(scrollToTopEl)
}
