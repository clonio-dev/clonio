---
title: Profile and Security
introduction: Manage your user profile, change your password, and configure two-factor authentication.
---

# Profile and Security

Access your profile settings by clicking your name in the bottom-left corner of the sidebar and selecting your account settings.

## Profile Information

Update your personal details:

- **Name** -- Your display name, shown in the sidebar and in audit trail reports as the initiator or configurator of cloning runs.
- **Email** -- Your login email address. Changing this may require email verification depending on the application configuration.

## Password

Change your password by providing your current password and entering a new one. Passwords must meet the application's minimum security requirements.

## Two-Factor Authentication (2FA)

Clonio supports time-based one-time password (TOTP) two-factor authentication for enhanced account security.

### Enabling 2FA

1. Navigate to your profile settings
2. Find the Two-Factor Authentication section
3. Click to enable 2FA
4. Scan the QR code with an authenticator app (Google Authenticator, Authy, 1Password, etc.)
5. Enter the verification code to confirm setup
6. Save the recovery codes in a secure location

### Recovery Codes

When 2FA is enabled, Clonio provides a set of recovery codes. Each code can be used once to log in if you lose access to your authenticator app. Store these codes securely -- they are the only way to recover your account without the authenticator.

### Disabling 2FA

You can disable 2FA from the same profile settings section. You will need to confirm with your current password or a valid 2FA code.

## Session Security

Your login session is managed by the application. For security:

- Sessions expire after a period of inactivity
- You can log out from the sidebar menu
- All actions (cloning creation, run initiation, configuration changes) are attributed to your user account in the audit trail
