export default function addToast (message, type = 'info', autohide = true) {
  document.querySelector('body').insertAdjacentHTML('beforeend', `
    <turbo-stream action="append" targets="#toast-wrapper">
      <template>
        <div
         data-controller="toast" 
         data-toast-type-value="${type}" 
         data-toast-message-value="${message}"
         data-toast-autohide-value="${autohide ? 'true' : 'false'}"
        ></div>
      </template>
    </turbo-stream>
  `)
}
