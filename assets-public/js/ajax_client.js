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
  }

  configureInterceptors () {
    this.instance.interceptors.response.use(
      // Successful request
      function (response) {
        // Any status code that lie *within* the range of 2xx cause this function to trigger

        if (response.data.disable_interceptor) {
          return response
        }

        if (response.data.message) {
          addToast(response.data.message, 'success')
        }

        return response
      },
      function (error) {
        // Any status codes that falls *outside* the range of 2xx cause this function to trigger

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
}

const instance = new AjaxClient()

export default instance.instance
