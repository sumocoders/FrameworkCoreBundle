import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  addAnchorToUrl (event) {
    window.history.pushState({}, '', event.target.dataset['bs-target'])
  }
}
