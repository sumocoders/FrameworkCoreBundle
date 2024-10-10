import { readCookie, setCookie } from './Cookies.js'

const Sidebar = function (sidebar) {
  if (sidebar !== null) {
    sidebarCookie(sidebar)
    document.querySelector('[data-sidebar-toggler]').addEventListener('click', () => {
      sidebarCollapse(sidebar)
    })
  }
}

const sidebarCookie = (sidebar) => {
  if (window.innerWidth > 576) {
    // read cookie
    if (readCookie('sidebar_is_open') === 'false') {
      sidebar.classList.add('sidebar-collapsed')
    } else {
      sidebar.classList.remove('sidebar-collapsed')
    }
  }
}

const sidebarCollapse = (sidebar) => {
  sidebar.classList.toggle('sidebar-collapsed')
  // set cookie
  if (sidebar.classList.contains('sidebar-collapsed')) {
    setCookie('sidebar_is_open', 'false')
  } else {
    setCookie('sidebar_is_open', 'true')
  }
}

export default Sidebar
