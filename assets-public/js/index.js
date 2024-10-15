// Other components
import GoBack from './Framework/GoBack.js'
import Scrolling from './Framework/Scrolling.js'
import Tabs from './Framework/Tabs.js'
import FormCollection from './Framework/FormCollection.js'
import PasswordStrengthChecker from './Framework/PasswordStrengthChecker.js'

export function Framework () {
  const scrollToTopEl = document.querySelector('[data-role="back-to-top"]')
  Scrolling(scrollToTopEl)

  const backButtonEl = document.querySelector('[data-button-previous="back"]')
  GoBack(backButtonEl)

  // initialize collections
  document.querySelectorAll('[data-role="collection"]').forEach((element) => {
    FormCollection(element)
  })

  Tabs()
  PasswordStrengthChecker()
}
