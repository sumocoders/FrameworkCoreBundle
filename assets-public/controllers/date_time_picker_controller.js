import { Controller } from '@hotwired/stimulus';
import flatpickr from 'flatpickr'

// import the most common languages
import { Austria } from 'flatpickr/dist/l10n/at.js'
import { Czech } from 'flatpickr/dist/l10n/cs.js'
import { Danish } from 'flatpickr/dist/l10n/da.js'
import { Dutch } from 'flatpickr/dist/l10n/nl.js'
import { Estonian } from 'flatpickr/dist/l10n/et.js'
import { Finnish } from 'flatpickr/dist/l10n/fi.js'
import { French } from 'flatpickr/dist/l10n/fr.js'
import { German } from 'flatpickr/dist/l10n/de.js'
import { Greek } from 'flatpickr/dist/l10n/gr.js'
import { Latvian } from 'flatpickr/dist/l10n/lv.js'
import { Lithuanian } from 'flatpickr/dist/l10n/lt.js'
import { Italian } from 'flatpickr/dist/l10n/it.js'
import { Norwegian } from 'flatpickr/dist/l10n/no.js'
import { Polish } from 'flatpickr/dist/l10n/pl.js'
import { Portuguese } from 'flatpickr/dist/l10n/pt.js'
import { Slovak } from 'flatpickr/dist/l10n/sk.js'
import { Swedish } from 'flatpickr/dist/l10n/sv.js'
import { Spanish } from 'flatpickr/dist/l10n/es.js'
import { Slovenian } from 'flatpickr/dist/l10n/sl.js'

export default class extends Controller {
  static values = {
    enableTime: { type: Boolean, default: false },
    showCalendar: { type: Boolean, default: true }
  }

  connect() {
    let locale = document.documentElement.lang
    if (locale === 'en') {
      locale = 'default'
    }

    this.element._flatpickr = flatpickr(this.element, {
      locale,
      enableTime: this.enableTimeValue,
      noCalendar: !this.showCalendarValue
    })
  }
}
