import { DatePicker } from './DatePicker.js'

export class TimePicker extends DatePicker {
  constructor (element) {
    super(element, true, true)

    element.parentNode.querySelector('[data-flatpicker-clear]').addEventListener('click', event => this.element._flatpickr.clear())
  }
}
