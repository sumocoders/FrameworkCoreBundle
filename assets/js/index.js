// External frameworks
import * as bootstrap from 'bootstrap/dist/js/bootstrap.js'

// Vue components
// import Toast from 'core/Framework/Components/Toast.vue'

// Other components
import Form from 'sumocoders/Form'
import GoBack from 'sumocoders/GoBack'
import Popover from 'sumocoders/Popover'
import Scrolling from 'sumocoders/Scrolling'
import Sidebar from 'sumocoders/Sidebar'
import TomSelect from 'tom-select'
import Tabs from 'sumocoders/Tabs'
import Tooltip from 'sumocoders/Tooltip'
import FormCollection from 'sumocoders/FormCollection'
import { DatePicker } from 'sumocoders/DatePicker'
import { DateTimePicker } from 'sumocoders/DateTimePicker'
import { TimePicker } from 'sumocoders/TimePicker'
import Theme from 'sumocoders/Theme'
import SelectSearch from 'sumocoders/SelectSearch'

window.bootstrap = bootstrap

export function Framework () {
  const formsList = document.querySelectorAll('form')
  Form(formsList)

  const scrollToTopEl = document.querySelector('[data-role="back-to-top"]')
  Scrolling(scrollToTopEl)

  const sidebarEl = document.querySelector('[data-sidebar-wrapper]')
  Sidebar(sidebarEl)

  const backButtonEl = document.querySelector('[data-button-previous="back"]')
  GoBack(backButtonEl)

  // initialize selects
  document.querySelectorAll('[data-role="select"]').forEach((element) => {
    if (element.dataset.options !== null) {
      element.select = new TomSelect(element, element.dataset.options)
    } else {
      element.select = new TomSelect(element, {})
    }
  })

  document.querySelectorAll('[data-role="select-search"]').forEach((element) => {
    SelectSearch(element)
  })

  // initialize collections
  document.querySelectorAll('[data-role="collection"]').forEach((element) => {
    FormCollection(element)
  })

  // initialize datetimepickers
  initializeDateTimePickers()

  // initialize clipboard
  document.querySelectorAll('[data-role="clipboard"]').forEach((element) => {
    element.clipboard = new Clipboard(element)
  })

  Tabs()
  Tooltip()
  Popover()
}

document.addEventListener('DOMContentLoaded', function () {
  Theme()
})

document.addEventListener('added.collection.item', function () {
  initializeDateTimePickers()
})

const initializeDateTimePickers = function () {
  document.querySelectorAll('[data-role="date-picker"]').forEach((element) => {
    element.datepicker = new DatePicker(element)
  })

  document.querySelectorAll('[data-role="time-picker"]').forEach((element) => {
    element.timepicker = new TimePicker(element)
  })

  document.querySelectorAll('[data-role="date-time-picker"]').forEach((element) => {
    element.datetimepicker = new DateTimePicker(element)
  })
}
