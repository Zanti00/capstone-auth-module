# Integration Guide: Capstone Authentication Module

This guide details how external sub-systems, such as **SERMS** (Smart Expense Reimbursement Management System) and **CMS** (Content Management System), can integrate with the Capstone Auth Module to securely authenticate users and verify access. 

It includes a comprehensive **AI Implementation Plan** at the end, which you can provide directly to your AI coding assistant (like GitHub Copilot, Gemini, or Claude) to automatically build the integration.

---

## 1. Integration Architecture Options

The Auth Module is built with **Laravel Passport** as an OAuth 2.0 Server. It provides two main integration pathways:

### Option A: OAuth 2.0 Client Flow (Recommended)
Best for separate applications, third-party services, and mobile apps.
1. **Registration**: An admin runs `php artisan passport:client` on the Auth Module to generate a `client_id` and `client_secret` for your sub-system.
2. **Authorization Code Flow**: Your frontend redirects the user to `https://auth.capstone.com/oauth/authorize`.
3. **Token Exchange**: The user logs in centrally and is redirected back with a `code`. Your backend exchanges this code at `/oauth/token` for an `access_token`.
4. **API Requests**: You query user data by passing `Authorization: Bearer <access_token>` to `/api/user` or `/api/me/permissions`.

### Option B: First-Party Cookie SSO
Best for Single Page Applications (SPAs) on the exact same root domain behind an API Gateway.
1. **Login**: Frontend calls `POST /api/login`.
2. **Cookies**: Auth Module sets **HttpOnly, Secure, SameSite=Strict** cookies for `access_token`, `refresh_token`, and `session_id`.
3. **State**: Browsers automatically send these cookies to endpoints sharing the domain. 
*(Note: Requires Auth Module configuration updates to support wildcard domains like `.capstone.com` if hosted on subdomains).*

---

## 2. Important Security Constraints

When communicating with the Auth Module, sub-systems must gracefully handle several strict security middlewares:

1. **Forced Password Change (`require.password.change`)**
   - **Behavior:** The Auth Module will return a `403 Forbidden` for most API endpoints if `is_password_changed = false`.
   - **Action Required:** Intercept this response and redirect the user to a "Change Password" UI that calls `POST /api/me/password`.
2. **Payload Encryption (RSA)**
   - **Behavior:** Sensitive endpoints may require encrypted JSON payloads.
   - **Action Required:** Fetch the public key from `GET /api/encryption-key` and encrypt the request body, including the `X-Encrypted: true` header.
3. **Session Inactivity Timeout**
   - **Behavior:** The module strictly enforces a 120-minute inactivity limit on sessions.
   - **Action Required:** Handle `401 Unauthorized` responses by clearing local session state and prompting the user to re-authenticate.

---
---

## 🤖 AI Implementation Prompt & Plan

*Developers: You can copy and paste everything below this line directly into your AI coding assistant to automate the integration process.*

<system_context>
You are an expert AI engineer assisting with the integration of a new sub-system (SERMS / CMS) into a central Capstone Authentication Module.
The Auth Module acts as an OAuth 2.0 Provider built on Laravel Passport. 
Your goal is to scaffold the authentication service, HTTP clients, and middleware required for the sub-system to communicate with the Auth Module securely.
</system_context>

<implementation_plan>
### Phase 0: Prerequisites & Initialization
Before writing any code, ensure the sub-system environment is fully prepared:
1. **Dependencies**: Verify and install necessary packages (e.g., HTTP clients like `guzzlehttp/guzzle` or Axios, and OAuth packages like Laravel Socialite if applicable). Run `composer install` or `npm install` as needed.
2. **Environment Configuration**: Ensure `.env` is properly copied from `.env.example` and essential keys (like `APP_KEY`, Database credentials) are configured.
3. **Database Migrations**: Run the sub-system's existing migrations (`php artisan migrate`). If the sub-system caches remote user data, generate and run a migration to store mapped Auth Module IDs and tokens.
4. **Cache/Config Clear**: Clear outdated caches (`php artisan optimize:clear` or equivalent) to prevent stale config issues.

### Phase 1: Environment & HTTP Client Setup
1. Define environment variables in the sub-system for:
   - `AUTH_MODULE_BASE_URL` (The base URL of the Auth Module).
   - `AUTH_CLIENT_ID` (The OAuth client ID).
   - `AUTH_CLIENT_SECRET` (The OAuth client secret).
   - `AUTH_REDIRECT_URI` (The callback URI for OAuth flow).
2. Create an `AuthHttpClient` wrapper/service in the sub-system that points to `AUTH_MODULE_BASE_URL`.
   - Configure it to automatically attach the `Authorization: Bearer <token>` header if a token exists in the current sub-system session.

### Phase 2: Implement OAuth 2.0 Login Flow
1. **Login Route (`/login`)**: Create an endpoint that generates a secure OAuth state/PKCE challenge and redirects the user's browser to:
   `GET {AUTH_MODULE_BASE_URL}/oauth/authorize?client_id=...&redirect_uri=...&response_type=code&scope=`
2. **Callback Route (`/auth/callback`)**: Create an endpoint to handle the redirect from the Auth Module.
   - Extract the `code` from the query parameters.
   - Make a back-channel request to `POST {AUTH_MODULE_BASE_URL}/oauth/token` with the `grant_type=authorization_code` to receive the `access_token` and `refresh_token`.
3. **Session Storage**: Securely store the `access_token` and `refresh_token` in the sub-system's local storage (e.g., an HttpOnly cookie or secure server-side session).

### Phase 3: Identity & Permission Syncing
1. After successfully acquiring the `access_token`, make a request to `GET {AUTH_MODULE_BASE_URL}/api/user` and `GET {AUTH_MODULE_BASE_URL}/api/me/permissions`.
2. Sync/cache this user identity and role/permission matrix within the sub-system's local state or database to avoid querying the Auth Module on every request.

### Phase 4: Middleware & Error Handling Implementation
Implement global interceptors or middleware for your HTTP client to handle the Auth Module's strict security policies:
1. **401 Unauthorized (Session Timeout / Token Expiry)**:
   - Intercept 401s.
   - Attempt to call `POST {AUTH_MODULE_BASE_URL}/oauth/token` with `grant_type=refresh_token` to get a new token.
   - If the refresh fails, forcefully log the user out locally and redirect them back to the login route.
2. **403 Forbidden (Forced Password Change)**:
   - Intercept 403s where the error payload indicates a password change is required.
   - Redirect the user to a dedicated "Update Password" route in your app.
   - From that route, format a request to `POST {AUTH_MODULE_BASE_URL}/api/me/password` to satisfy the constraint.
3. **RSA Payload Encryption (Optional depending on routes used)**:
   - Create a utility function that calls `GET {AUTH_MODULE_BASE_URL}/api/encryption-key` and uses the returned RSA public key to encrypt request bodies for sensitive calls. Include the `X-Encrypted: true` HTTP header.

### Phase 5: Logout Flow
1. **Logout Route (`/logout`)**: Create an endpoint that:
   - Makes a request to `POST {AUTH_MODULE_BASE_URL}/api/logout` to invalidate the token upstream.
   - Clears all local session state, cached permissions, and stored tokens in the sub-system.
   - Redirects the user to the public landing page.
</implementation_plan>

**Instructions to AI:**
Please review this implementation plan. If you are ready, begin with Phase 0 by verifying the environment, installing required dependencies, configuring the `.env`, and running necessary migrations before proceeding to Phase 1.
