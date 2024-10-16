import { Controller } from '@hotwired/stimulus';
import { Popover } from 'bootstrap'

export default class extends Controller {
  connect() {
    this.element.popoverObject = new Popover(this.element)
  }
}
