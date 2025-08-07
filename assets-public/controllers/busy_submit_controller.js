import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  connect () {
    this.element.addEventListener('turbo:submit-start', (event) => this.disableSubmit(event))
    this.element.addEventListener('turbo:submit-end', (event) => this.enableSubmit(event))
  }

  disconnect () {
    this.enableSubmits()
  }

  disableSubmit (event) {
    const button = event.detail.formSubmission.submitter
    if (button) {
      if (!button.dataset.originalContent) {
        button.dataset.originalContent = button.innerHTML
      }
      button.disabled = true
      button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>'
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
      if (button.dataset.originalContent) {
        button.innerHTML = button.dataset.originalContent
      }
      button.dataset.originalContent = ''
    }

    this.enableSubmits()
  }

  enableSubmits () {
    this.element.querySelectorAll('[type="submit"]').forEach((submitTarget) => {
      submitTarget.disabled = false
    })
  }
}
