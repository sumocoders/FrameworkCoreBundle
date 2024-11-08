import { Controller } from '@hotwired/stimulus'
import Sortable from 'sortablejs'

export default class extends Controller {
  static targets = ['itemContainer', 'addButton']

  connect () {
    // set index if not defined
    const index = this.element.dataset.index

    const items = this.element.querySelectorAll('[data-role="collection-item"]')
    let numberOfItems = items.length
    const minimumItems = this.element.dataset.min !== undefined && this.element.dataset.min !== 'null' ? parseInt(this.element.dataset.min) : 0
    const maximumItems = this.element.dataset.max !== undefined && this.element.dataset.max !== 'null' ? parseInt(this.element.dataset.max) : null

    if (index === undefined || index === null) {
      this.element.dataset.index = numberOfItems
    }

    if (this.element.dataset.allowDragAndDrop === '1') {
      Sortable.create(this.element.querySelector('ul'), {
        handler: '[data-role="collection-item-change-order"]',
        animation: 150,
        ghostClass: 'collection-item-selected',
        chosenClass: 'collection-item-selected',
        dragClass: 'collection-item-selected'
      })
    }

    // Add minimum items
    while (numberOfItems < minimumItems) {
      this.addItem(new Event('click'), this.addButtonTarget)
      numberOfItems++
    }

    if (maximumItems && numberOfItems >= maximumItems) {
      this.addButtonTargets.forEach((element) => element.setAttribute('disabled', 'disabled'))
    }
  }

  addItem (event) {
    let numberOfItems = this.element.querySelectorAll('[data-role="collection-item"]').length
    const maximumItems = this.element.dataset.max !== undefined && this.element.dataset.max !== 'null' ? parseInt(this.element.dataset.max) : null

    // Check if the button should have been disabled
    if (maximumItems && numberOfItems >= maximumItems) {
      this.addButtonTargets.forEach((element) => element.setAttribute('disabled', 'disabled'))

      return
    }

    numberOfItems++

    document.dispatchEvent(new Event('add.collection.item'))

    let prototype = this.element.dataset.prototype
    // get the new index
    const index = parseInt(this.element.dataset.index)
    // Replace '__name__' in the prototype's HTML to
    // instead be a number based on how many items we have
    prototype = prototype.replace(/__name__/g, index)
    // increase the index with one for the next item
    this.element.dataset.index = index + 1

    // Display the form in the page before the "new" link
    this.itemContainerTarget.insertAdjacentHTML('beforeend', prototype)

    if (maximumItems && numberOfItems >= maximumItems) {
      this.addButtonTargets.forEach((element) => element.setAttribute('disabled', 'disabled'))
    }

    document.dispatchEvent(new Event('added.collection.item'))
  }

  deleteItem (event) {
    document.dispatchEvent(new Event('remove.collection.item'))

    const itemToRemove = event.target.closest('[data-role="collection-item"]')
    const numberOfItems = this.element.querySelectorAll('[data-role="collection-item"]').length - 1
    const maximumItems = this.element.dataset.max !== undefined && this.element.dataset.max !== 'null' ? parseInt(this.element.dataset.max) : null

    itemToRemove.parentNode.removeChild(itemToRemove)

    if (maximumItems && numberOfItems < maximumItems) {
      this.addButtonTargets.forEach((element) => element.removeAttribute('disabled'))
    }

    document.dispatchEvent(new Event('removed.collection.item'))
  }
}
