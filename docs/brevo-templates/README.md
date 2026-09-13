# Brevo Transactional Templates

These files are copy-ready starting points for the Brevo hosted templates used by `auth-service`.

Recommended template names:
- `SBSI Welcome - Temporary Password`
- `SBSI Password Reset`
- `SBSI Email Verification`
- `SBSI Password Changed Confirmation`

Recommended subjects:
- Welcome / temporary password: `Welcome to {{ params.app_name }}`
- Password reset: `Reset Your Password`
- Email verification: `Verify Your Email Address`
- Password changed confirmation: `Your Password Was Changed`

Template variable mapping used by the app:
- `app_name`
- `email`
- `first_name`
- `temporary_password`
- `login_url`
- `reset_url`
- `verification_url`
- `changed_at`

Brevo template ID to env mapping:
- `BREVO_TEMPLATE_WELCOME_TEMP_PASSWORD`
- `BREVO_TEMPLATE_PASSWORD_RESET`
- `BREVO_TEMPLATE_EMAIL_VERIFICATION`
- `BREVO_TEMPLATE_PASSWORD_CHANGED_CONFIRMATION`
