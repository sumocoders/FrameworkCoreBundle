import { Controller } from '@hotwired/stimulus'
import { Toast } from 'bootstrap'

export default class extends Controller {
  static values = {
    type: String,
    message: String,
    autohide: { type: Boolean, default: true }
  }

  connect () {
    this.element.classList.add('toast')
    this.element.classList.add('toast-' + this.typeValue)
    this.element.role = this.typeValue === 'danger' ? 'alert' : 'status'
    this.element['aria-live'] = this.typeValue === 'danger' ? 'assertive' : 'polite'
    this.element['aria-atomic'] = 'true'
    this.element.dataset.bsAutohide = this.autohideValue ? 'true' : 'false'
    this.element.id = 'toast-' + Math.random().toString(36).substring(2, 15)
    let icon = 'fas fa-info-circle'
    if (this.typeValue === 'success') {
      icon = 'fas fa-check'
    } else if (this.typeValue === 'danger') {
      icon = 'fas fa-exclamation-triangle'
    } else if (this.typeValue === 'warning') {
      icon = 'fas fa-exclamation'
    }
    this.element.innerHTML = `
       <div class="toast-body">
         <div class="d-flex flex-row align-items-center">
           <div class="toast-icon-wrapper me-3">
             <i class="toast-icon ${icon}"></i>
           </div>
           ${this.messageValue}
         </div>
         <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
       </div>
    `
    if (this.autohideValue) {
      this.element.innerHTML += `
        <div class="toast-progress">
          <div class="toast-progress--inner"></div>
        </div>
      `
    }

    const toastBootstrap = Toast.getOrCreateInstance(this.element)
    toastBootstrap.show()
  }
}
