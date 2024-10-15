import Clipboard from '@stimulus-components/clipboard'
import addToast from 'sumocoders/addToast'

export default class extends Clipboard {
  copied() {
    super.copied()
    addToast('Copied to clipboard!', 'success')
  }
}
