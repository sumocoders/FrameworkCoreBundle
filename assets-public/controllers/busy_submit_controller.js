import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  connect () {
    this.disableSubmit = this.disableSubmit.bind(this);
    this.enableSubmit = this.enableSubmit.bind(this);
    this.element.addEventListener('turbo:submit-start', this.disableSubmit)
    this.element.addEventListener('turbo:submit-end', this.enableSubmit)
  }

  disconnect () {
    this.enableSubmits()
    this.element.removeEventListener('turbo:submit-start', this.disableSubmit)
    this.element.removeEventListener('turbo:submit-end', this.enableSubmit)
  }

  disableSubmit (event) {
    const button = event.detail.formSubmission.submitter
    if (button) {
      button.disabled = true
      if (button.tagName === 'BUTTON') {
        button.prepend(this.createSpinner())
      } else {
        console.debug('No spinner added, element is not a button.')
      }
    }

    this.disableSubmits()
  }

  disableSubmits () {
    this.element.querySelectorAll('[type="submit"]').forEach((submitTarget) => {
      submitTarget.disabled = true
    })
  }

  enableSubmit (event) {
    const button = event.detail.formSubmission.submitter
    if (button) {
      button.disabled = false
      const spinner = button.querySelector('.spinner-border')
      if (spinner) {
        button.removeChild(spinner)
      }
    }

    this.enableSubmits()
  }

  enableSubmits () {
    this.element.querySelectorAll('[type="submit"]').forEach((submitTarget) => {
      submitTarget.disabled = false
    })
  }

  createSpinner () {
    let spinner = document.createElement('span')
    spinner.setAttribute('class', 'spinner-border spinner-border-sm me-2')
    spinner.setAttribute('aria-hidden', 'true')
    return spinner
  }
}
