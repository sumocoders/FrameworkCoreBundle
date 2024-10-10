import { addFlash } from './Flash.js'

const Clipboard = function (element) {
  element.addEventListener('click', (event) => {
    const elementId = event.currentTarget.getAttribute('data-clipboard-target')

    if (elementId === null) {
      console.error(
        'data-clipboard-target attribute was not provided. This is the element we will be copying from.'
      )
      return
    }

    const textSource = document.querySelector(elementId)

    if (textSource === null) {
      console.error('Source element was not found "' + elementId + '".')
      return
    }

    const text = textSource.value || textSource.innerText || textSource.textSource
    const successMessage = event.currentTarget.getAttribute('data-success-message')

    if (!navigator.clipboard) {
      document.execCommand('copy')

      if (successMessage !== null) {
        addFlash(successMessage, 'success')
      }
    } else {
      navigator.clipboard.writeText(text).then(
        function () {
          if (successMessage !== null) {
            addFlash(successMessage, 'success')
          }
        }
      )
    }
  })
}

export default Clipboard
