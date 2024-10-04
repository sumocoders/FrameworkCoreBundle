import axios from 'axios'
import { debounce } from 'lodash'

export default function PasswordStrengthChecker () {
  const passwordInput = document.querySelectorAll('[data-role="check-password"] input[type="password"]')[0]
  const meterSections = document.querySelectorAll('.meter-section')

  const debounced = debounce(getStrength, 250, { maxWait: 750, leading: true, trailing: true })
  if (passwordInput) passwordInput.addEventListener('input', debounced)

  function getStrength () {
    const password = passwordInput.value
    const dataRoute = passwordInput.closest('[data-route]').dataset.route

    axios.post(dataRoute, {
      password: password
    })
      .then(function (response) {
        updateMeter(response.data.strength)
      })
      .catch(function (error) {
        console.error(error)
      })
  }

  function updateMeter (strength) {
    const classes = ['weak', 'medium', 'strong', 'very-strong', 'very-strong']

    // Remove all strength classes
    meterSections.forEach((section) => {
      section.classList.remove(...classes)
    })

    // Add the appropriate strength class based on the strength value
    for (let i = 0; i <= strength; i++) {
      meterSections[i].classList.add(classes[strength])
    }
  }
}
