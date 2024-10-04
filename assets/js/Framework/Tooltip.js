const Tooltip = function () {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new window.bootstrap.Tooltip(tooltipTriggerEl)
  })
}

export default Tooltip
