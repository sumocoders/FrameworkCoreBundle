import { Controller } from '@hotwired/stimulus';
import { setCookie } from 'sumocoders/cookie'

export default class extends Controller {
  static targets = ['toggleable']

  toggle () {
    this.toggleableTarget.classList.toggle('sidebar-collapsed');

    // set cookie
    setCookie('sidebar_is_open', !this.toggleableTarget.classList.contains('sidebar-collapsed'))
  }
}
