export default function Form (forms) {
  forms.forEach((form) => {
    form.addEventListener('submit', () => { hijackSubmit() })
  })

  const hijackSubmit = () => {
    const event = new Event('form_submitting')
    document.dispatchEvent(event)
  }
}
