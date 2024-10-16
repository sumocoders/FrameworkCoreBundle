import { Controller } from '@hotwired/stimulus';
import debounce from 'sumocoders/debounce'

export default class extends Controller {
  connect () {
    this.debouncedScroll = debounce(() => this.checkScroll(), 100)
  }

  scroll () {
    this.debouncedScroll()
  }

  checkScroll () {
    if (window.scrollY > 1000) {
      this.element.classList.remove('d-none')
      this.element.classList.add('show')
    } else {
      this.element.classList.add('d-none')
      this.element.classList.remove('show')
    }
  }
}
