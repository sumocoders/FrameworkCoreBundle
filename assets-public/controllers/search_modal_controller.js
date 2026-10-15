import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'

// Opens the quick search modal (see docs/quick-search.md) on Cmd+K / Ctrl+K and moves focus through its results
// with the arrow keys.
export default class extends Controller {
  open (event) {
    event.preventDefault()
    Modal.getOrCreateInstance(this.element).show()
  }

  close () {
    Modal.getInstance(this.element)?.hide()
  }

  focus () {
    this.element.querySelector('input')?.focus()
  }

  next (event) {
    this.move(event, 1)
  }

  previous (event) {
    this.move(event, -1)
  }

  move (event, step) {
    const items = [...this.element.querySelectorAll('.list-group-item-action')]
    if (items.length === 0) {
      return
    }

    event.preventDefault()
    const index = items.indexOf(document.activeElement)
    if (index === -1) {
      items.at(step > 0 ? 0 : -1).focus()
      return
    }

    items[Math.min(Math.max(index + step, 0), items.length - 1)].focus()
  }
}
