import Toast from './Components/Toast.vue'
import { createApp } from 'vue'

export function addFlash (message, type, delay = 10000) {
  const toastId = 'toast' + Date.now()

  const app = createApp(Toast, {
    type: type,
    message: message,
    delay: delay,
    id: toastId
  })
  app.mount('#toast-wrapper')

  return toastId
}
