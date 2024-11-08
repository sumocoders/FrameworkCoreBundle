import Clipboard from '@stimulus-components/clipboard'
import addToast from 'sumocoders/addToast'

export default class extends Clipboard {
  static values = {
    successMessage: { type: String, default: 'Copied to clipboard!' }
  }

  copied () {
    super.copied()
    addToast(this.successMessageValue, 'success')
  }
}
