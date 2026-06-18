export function hasAuthStatusCookie(): boolean {
  return document.cookie
    .split(';')
    .map((cookie) => cookie.trim())
    .some((cookie) => cookie.startsWith('is_authenticated='))
}

export function hasLocalUser(): boolean {
  return !!localStorage.getItem('user')
}

export function isAuthenticatedClientSide(): boolean {
  return hasLocalUser() || hasAuthStatusCookie()
}

export function clearClientAuthState(): void {
  localStorage.removeItem('user')
  document.cookie = 'is_authenticated=; Max-Age=0; path=/; SameSite=Strict'
}
