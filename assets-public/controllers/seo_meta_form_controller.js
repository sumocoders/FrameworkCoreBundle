import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['pageTitleCheckbox', 'pageTitleField', 'metaDescriptionCheckbox', 'metaDescriptionField']
  static values = { pageTitleSourceSelector: { type: String, default: '' } }

  connect () {
    this.syncField(this.pageTitleCheckboxTarget, this.pageTitleFieldTarget)
    this.syncField(this.metaDescriptionCheckboxTarget, this.metaDescriptionFieldTarget)

    this.sourceElement = null
    this.onSourceInput = null

    if (this.pageTitleSourceSelectorValue) {
      this.sourceElement = document.querySelector(this.pageTitleSourceSelectorValue)

      if (!this.sourceElement) {
        console.warn(`seo-meta-form: no element matches selector "${this.pageTitleSourceSelectorValue}"`)
      } else {
        this.onSourceInput = () => this.mirrorPageTitle()
        this.sourceElement.addEventListener('input', this.onSourceInput)
        this.mirrorPageTitle()
      }
    }
  }

  disconnect () {
    if (this.sourceElement && this.onSourceInput) {
      this.sourceElement.removeEventListener('input', this.onSourceInput)
    }
  }

  togglePageTitle () {
    this.syncField(this.pageTitleCheckboxTarget, this.pageTitleFieldTarget)
    this.mirrorPageTitle()
  }

  toggleMetaDescription () {
    this.syncField(this.metaDescriptionCheckboxTarget, this.metaDescriptionFieldTarget)
  }

  syncField (checkbox, field) {
    field.disabled = !checkbox.checked
  }

  mirrorPageTitle () {
    if (this.sourceElement && this.pageTitleFieldTarget.disabled) {
      this.pageTitleFieldTarget.value = this.sourceElement.value
    }
  }
}
