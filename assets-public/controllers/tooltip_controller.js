import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap'

export default class extends Controller {
  connect() {
    this.element.tooltip = new Tooltip(this.element)
  }
}
