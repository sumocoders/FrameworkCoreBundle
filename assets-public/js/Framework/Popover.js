import { Popover as BootstrapPopover } from 'bootstrap'

const Popover = function () {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new BootstrapPopover(tooltipTriggerEl)
  })
}

export default Popover
