import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['toggleable']

  connect () {
    if (window.innerWidth > 576) {
      // read local storage
      if (localStorage.getItem('sidebar_is_open') === 'false') {
        this.toggleableTarget.classList.add('sidebar-collapsed')
      } else {
        this.toggleableTarget.classList.remove('sidebar-collapsed')
      }
    }
  }

  toggle () {
    this.toggleableTarget.classList.toggle('sidebar-collapsed');

    // set local storage
    localStorage.setItem('sidebar_is_open', this.toggleableTarget.classList.contains('sidebar-collapsed') ? 'false' : 'true')
  }
}
