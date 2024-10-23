import { Tooltip as BootstrapTooltip } from 'bootstrap'

const Tooltip = function () {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new BootstrapTooltip(tooltipTriggerEl)
  })
}

export default Tooltip
