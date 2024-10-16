// Other components
import Scrolling from './Framework/Scrolling.js'
import FormCollection from './Framework/FormCollection.js'

export function Framework () {
  const scrollToTopEl = document.querySelector('[data-role="back-to-top"]')
  Scrolling(scrollToTopEl)

  // initialize collections
  document.querySelectorAll('[data-role="collection"]').forEach((element) => {
    FormCollection(element)
  })
}
