import { Controller } from '@hotwired/stimulus'
import { setCookie } from 'sumocoders/cookie'

export default class extends Controller {
  static targets = ['toggleable']

  toggle () {
    const targets = this.toggleableTargets || (this.toggleableTarget ? [this.toggleableTarget] : [])
    if (!targets.length) return

    // Determine the next state: if any target is open, collapse all; otherwise, expand all
    const nextShouldCollapse = targets.some((el) => !el.classList.contains('sidebar-collapsed'))

    // Apply the state to all targets
    targets.forEach((el) => {
      el.classList.toggle('sidebar-collapsed', nextShouldCollapse)
    })

    // set cookie to reflect whether the sidebar is open (not collapsed)
    setCookie('sidebar_is_open', !nextShouldCollapse)
  }
}
