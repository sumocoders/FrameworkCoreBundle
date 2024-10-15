// Other components
import Form from './Framework/Form.js'
import GoBack from './Framework/GoBack.js'
import Scrolling from './Framework/Scrolling.js'
import Tabs from './Framework/Tabs.js'
import FormCollection from './Framework/FormCollection.js'
import { DatePicker } from './Framework/DateTimePicker/DatePicker.js'
import { DateTimePicker } from './Framework/DateTimePicker/DateTimePicker.js'
import { TimePicker } from './Framework/DateTimePicker/TimePicker.js'
import PasswordStrengthChecker from './Framework/PasswordStrengthChecker.js'

export function Framework () {
  const formsList = document.querySelectorAll('form')
  Form(formsList)

  const scrollToTopEl = document.querySelector('[data-role="back-to-top"]')
  Scrolling(scrollToTopEl)

  const backButtonEl = document.querySelector('[data-button-previous="back"]')
  GoBack(backButtonEl)

  // initialize collections
  document.querySelectorAll('[data-role="collection"]').forEach((element) => {
    FormCollection(element)
  })

  // initialize datetimepickers
  initializeDateTimePickers()

  Tabs()
  PasswordStrengthChecker()
}

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
