const GoBack = function (backButton) {
  if (backButton !== null) {
    backButton.addEventListener('click', (event) => {
      event.preventDefault()
      window.history.back()
    })
  }
}

export default GoBack
