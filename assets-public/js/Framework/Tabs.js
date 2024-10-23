import { Tab } from 'bootstrap'

const Tabs = function () {
  const anchor = document.location.hash
  if (anchor !== '') {
    const tab = document.querySelector('.nav-tabs button[data-bs-target="' + anchor + '"]')
    if (tab !== null) {
      const tabTrigger = new Tab(tab)
      tabTrigger.show()
    }
  }
}

export default Tabs
