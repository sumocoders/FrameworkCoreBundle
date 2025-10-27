import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'

export default class extends Controller {
  static values = {
    modalTitle: String,
    confirmationMessage: { type: String, default: 'Are you sure?' },
    cancelButtonText: { type: String, default: 'Cancel' },
    confirmButtonText: { type: String, default: 'Ok' },
    closeButtonText: { type: String, default: 'Close' },
  }
  static targets = ['element']

  connect () {
    if (!this.isForm() && !this.isLink()) {
      console.error('Confirm controller: Only form or a elements are supported.')
    }

    let eventName = ''
    if (this.isForm()) eventName = 'submit'
    if (this.isLink()) eventName = 'click'

    this.elementTarget.addEventListener(eventName, (e) => {
      e.preventDefault()
      e.stopPropagation()
      this.showModal()
    })
  }

  isForm () {
    return this.elementTarget && this.elementTarget.tagName.toLowerCase() === 'form'
  }

  isLink () {
    return this.elementTarget && this.elementTarget.tagName.toLowerCase() === 'a'
  }

  showModal () {
    // remove existing modal
    const existingModal = document.querySelector('div[data-role="confirm-modal"]')
    if (existingModal) {
      existingModal.parentNode.removeChild(existingModal)
    }

    // create modal
    const modal = document.createElement('div')
    modal.className = 'modal fade'
    modal.tabIndex = -1
    modal.setAttribute('aria-labelledby', 'confirmModalTitle')
    modal.setAttribute('aria-hidden', 'true')
    modal.setAttribute('data-role', 'confirm-modal')
    const modalDialog = document.createElement('div')
    modalDialog.className = 'modal-dialog'
    modal.appendChild(modalDialog)
    const modalContent = document.createElement('div')
    modalContent.className = 'modal-content'
    modalDialog.appendChild(modalContent)
    const modalHeader = document.createElement('div')
    modalHeader.className = 'modal-header'
    modalContent.appendChild(modalHeader)
    if (this.modalTitleValue !== '') {
      const modalTitle = document.createElement('h5')
      modalTitle.className = 'modal-title'
      modalTitle.id = 'confirmModalTitle'
      modalTitle.textContent = this.modalTitleValue || 'Confirm'
      modalHeader.appendChild(modalTitle)
    }
    const closeButton = document.createElement('button')
    closeButton.type = 'button'
    closeButton.className = 'btn-close'
    closeButton.setAttribute('data-bs-dismiss', 'modal')
    closeButton.setAttribute('aria-label', this.closeButtonTextValue)
    modalHeader.appendChild(closeButton)
    const modalBody = document.createElement('div')
    modalBody.className = 'modal-body'
    modalBody.textContent = this.confirmationMessageValue
    modalContent.appendChild(modalBody)
    const modalFooter = document.createElement('div')
    modalFooter.className = 'modal-footer'
    modalContent.appendChild(modalFooter)
    const cancelButton = document.createElement('button')
    cancelButton.type = 'button'
    cancelButton.className = 'btn btn-secondary'
    cancelButton.setAttribute('data-bs-dismiss', 'modal')
    cancelButton.textContent = this.cancelButtonTextValue
    modalFooter.appendChild(cancelButton)
    const confirmButton = document.createElement('button')
    confirmButton.type = 'button'
    confirmButton.className = 'btn btn-primary'
    confirmButton.textContent = this.confirmButtonTextValue
    confirmButton.setAttribute('data-role', 'confirm-button')
    modalFooter.appendChild(confirmButton)
    document.body.appendChild(modal)

    // show modal
    const bootstrapModal = new Modal(modal, { backdrop: 'static' })
    bootstrapModal.show()

    // remove modal from DOM after hiding
    modal.addEventListener('hidden.bs.modal', () => {
      modal.parentNode && modal.parentNode.removeChild(modal)
    })

    // bind on confirm button click
    const confirmBtn = document.querySelector('[data-role="confirm-button"]')
    confirmBtn.addEventListener('click', () => {
      this.handleConfirm()
      bootstrapModal.hide()
    })
  }

  handleConfirm () {
    if (this.isForm()) {
      this.elementTarget.submit()
      return
    }

    if (this.isLink()) {
      if (this.elementTarget.target) {
        window.open(this.elementTarget.href, this.elementTarget.target)
        return
      }

      window.location = this.elementTarget.href
    }
  }

  disconnect () {
    const existingModal = document.querySelector('div[data-role="confirm-modal"]')
    if (existingModal) {
      existingModal.parentNode.removeChild(existingModal)
    }
  }
}
