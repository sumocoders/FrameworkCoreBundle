export function isEnabled () {
  // try to grab the property
  let cookiesEnabled = !!(navigator.cookieEnabled)

  // unknown property?
  if (typeof navigator.cookieEnabled === 'undefined' && !cookiesEnabled) {
    // try to set a cookie
    document.cookie = 'testcookie'
    cookiesEnabled = document.cookie.includes('testcookie')
  }

  return cookiesEnabled
}

export function readCookie (name) {
  const cookies = document.cookie.split(';')
  name = name + '='

  for (let cookie of cookies) {
    cookie = cookie.trim()
    if (cookie.indexOf(name) === 0) {
      return cookie.substring(name.length, cookie.length)
    }
  }

  return null
}

export function setCookie (name, value, days = 7) {
  const expireDate = new Date()
  expireDate.setDate(expireDate.getDate() + days)
  document.cookie = name + '=' + value + ';expires=' + expireDate.toUTCString() + ';path=/'
}
