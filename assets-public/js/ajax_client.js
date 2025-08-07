import axios from 'axios'
import addToast from 'sumocoders/addToast'

class AjaxClient {
  constructor () {
    const options = {
      transformRequest: [
        (data, header) => {
          if (this.instance.csrf_token) {
            data.csrf_token = this.instance.csrf_token
          }
          return data
        }, ...axios.defaults.transformRequest
      ]
    }

    this.instance = axios.create(options)
    this.configureDefaults()
    this.configureInterceptors()
  }

  configureDefaults () {
    this.instance.defaults.timeout = 2500
    this.instance.defaults.headers.common = {
      'Accept': 'application/json',
    }
    this.instance.defaults.busy_targets = []
  }

  configureInterceptors () {
    this.instance.interceptors.request.use(
      (config) => {
        // Do something before request is sent
        this.setBusy()
        return config
      },
      (error) => {
        // Do something with request error
        return Promise.reject(error)
      },
      { runWhen: () => this.instance.busy_targets }
    )
    this.instance.interceptors.response.use(
      // Successful request
      (response) => {
        // Any status code that lie *within* the range of 2xx cause this function to trigger
        this.resetBusy()

        if (response.data.disable_interceptor) {
          return response
        }

        if (response.data.message) {
          addToast(response.data.message, 'success')
        }

        return response
      },
      (error) => {
        // Any status codes that falls *outside* the range of 2xx cause this function to trigger
        this.resetBusy()

        if (error.response.data.disable_interceptor) {
          return Promise.reject(error)
        }

        console.error(error)

        if (error.response.data.message) {
          addToast(error.response.data.message, 'danger')

          return Promise.reject(error)
        }

        if (error.message) {
          addToast(error.message, 'danger')

          return Promise.reject(error)
        }

        return Promise.reject(error)
      })
  }

  setBusy () {
    this.instance.busy_targets.forEach((button) => {
      if (!button.dataset.originalContent) {
        button.dataset.originalContent = button.innerHTML
      }
      button.disabled = true
      button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>'
    })
  }

  resetBusy () {
    this.instance.busy_targets.forEach((button) => {
      button.disabled = false
      if (button.dataset.originalContent) {
        button.innerHTML = button.dataset.originalContent
      }
      button.dataset.originalContent = ''
    })
  }
}

const instance = new AjaxClient()

export default instance.instance
