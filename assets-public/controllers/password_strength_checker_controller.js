import { Controller } from '@hotwired/stimulus';
import axios from 'axios'

export default class extends Controller {
  static values = {
    route: String
  }
  static targets = ['input', 'meterSections']

  connect () {
    this.debouncedStrength = this.debounce(this.getStrength)
  }

  calculateStrength () {
    this.debouncedStrength()
  }

  getStrength () {
    axios.post(
      this.routeValue,
      {
        password: this.inputTarget.value
      }
    )
      .then((response) => this.updateMeter(response.data.strength, this))
      .catch((error) => console.error(error))
  }

  debounce (func, timeout = 500) {
    let timer;

    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
  }

  updateMeter (strength) {
    const classes = ['weak', 'medium', 'strong', 'very-strong', 'very-strong']
    const meterSections = this.meterSectionsTarget.querySelectorAll('.meter-section')

    // Remove all strength classes
    meterSections.forEach((section) => {
      section.classList.remove(...classes)
    })

    // Add the appropriate strength class based on the strength value
    for (let i = 0; i <= strength; i++) {
      meterSections[i].classList.add(classes[strength])
    }
  }
}
