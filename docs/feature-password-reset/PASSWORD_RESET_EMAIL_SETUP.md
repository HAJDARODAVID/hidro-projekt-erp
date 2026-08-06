# Password Reset Email — Server Setup Guide

This app already has the "forgot password" flow built in (it came from the
Laravel/UI auth scaffolding). Nothing new needed to be coded — the only gap
was a visible "Forgot password?" link on the login page, which has been added
along with matching styling for the reset pages. This document explains how
the flow works and what you need to configure **on the production server** so
the reset emails actually get delivered.

## How the flow works

1. User clicks **Forgot password?** on `/login` → goes to `GET /password/reset`
   (`resources/views/auth/passwords/email.blade.php`).
2. User submits their email → `POST /password/email`, handled by
   `App\Http\Controllers\Auth\ForgotPasswordController` (uses Laravel's
   built-in `SendsPasswordResetEmails` trait).
3. Laravel generates a signed token, stores a hash of it in the
   `password_reset_tokens` table, and sends the user an email containing a
   link to `/password/reset/{token}` — using the framework's default
   `Illuminate\Auth\Notifications\ResetPassword` notification, delivered
   through your configured mailer.
4. User opens the link → `resources/views/auth/passwords/reset.blade.php`,
   sets a new password → `POST /password/reset`, handled by
   `ResetPasswordController` (`ResetsPasswords` trait), which updates the
   `password` column on `users` and logs them in.

All routes are registered by `Auth::routes()` in [routes/web.php](../../routes/web.php).
There is nothing else to wire up in code — the remaining work is purely
server/environment configuration so mail actually leaves the server.

## What's already configured

- `password_reset_tokens` migration — already run in production (confirmed via
  `php artisan migrate:status`).
- `config/auth.php` → `passwords.users.table` points at `password_reset_tokens`,
  `expire` is 60 minutes, `throttle` is 60 seconds between requests.
- `App\Models\User` uses the `Notifiable` trait, required for notifications
  (including password reset emails) to work.
- The reset notification is sent **synchronously**, not queued — so no queue
  worker is required for this feature specifically, even though the app uses
  `QUEUE_CONNECTION=redis` for other jobs.

## What you need to set up on the server

### 1. Pick a way to actually send mail

You cannot send email reliably straight from a VPS's own IP — most providers
(Gmail, Outlook, etc.) will spam-box or reject mail from unknown residential
or generic cloud IPs, and getting a fresh IP's reputation up is slow. Use a
transactional email provider instead. Any of these work with Laravel's built
in `smtp` mailer without extra packages:

| Provider | Free tier | Notes |
|---|---|---|
| Mailgun | ~5,000 emails/mo (with a verified domain) | Widely used with Laravel, has a native driver too |
| Amazon SES | Pay-as-you-go, very cheap | Best if you're already on AWS |
| Postmark | 100 emails/mo free | Excellent deliverability, great for transactional-only mail |
| Brevo (Sendinblue) | 300 emails/day free | Easy SMTP setup |

Any of these gives you SMTP credentials (host, port, username, password) that
drop straight into `.env` — no code changes needed since the app already uses
Laravel's standard `smtp` mailer.

### 2. Update `.env` on the production server

SSH into the server (`165.245.223.177`, per `deploy.php`) and edit the
deployed `.env` (typically `shared/.env` under the Deployer release path).
Replace the local `mailpit` values with your provider's SMTP credentials:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@hidro-projekt.hr"
MAIL_FROM_NAME="${APP_NAME}"
```

Also make sure `APP_URL` is set to your real production URL (used to build
the reset link in the email):

```env
APP_URL=https://your-production-domain.hr
```

### 3. Clear cached config after editing `.env`

Laravel caches config in production. After changing `.env`, run:

```bash
php artisan config:clear
php artisan config:cache
```

(If your `deploy.php` deployment recipe already runs `config:cache` on every
deploy, you only need this after manually editing `.env` outside of a deploy.)

### 4. Set up domain authentication (SPF, DKIM, DMARC)

This is the step that most affects whether your reset emails land in the
inbox instead of spam. Your email provider (Mailgun/SES/Postmark/etc.) will
give you DNS records to add for the sending domain:

- **SPF** — TXT record authorizing the provider's servers to send on behalf
  of your domain.
- **DKIM** — TXT record(s) with a public key so receiving servers can verify
  the email wasn't tampered with.
- **DMARC** — TXT record telling receivers what to do with mail that fails
  SPF/DKIM.

Add these in whatever DNS host manages `hidro-projekt.hr` (or the domain you
send `MAIL_FROM_ADDRESS` from). Most providers verify domain ownership and
show you exactly which records to add and confirm once they're live —
usually within minutes to a few hours depending on DNS propagation.

### 5. Check outbound firewall rules

Confirm the server can reach the SMTP provider on the port you configured
(587 for TLS submission, 465 for implicit TLS). Some hosts block outbound 25
by default (fine, we're not using 25) but occasionally also restrict 587.
Quick check from the server:

```bash
telnet smtp.your-provider.com 587
# or
nc -zv smtp.your-provider.com 587
```

### 6. Test it end-to-end

From the server, after deploying:

```bash
php artisan tinker
```

```php
Mail::raw('Test email from production', function ($message) {
    $message->to('you@example.com')->subject('SMTP test');
});
```

If that arrives, trigger the real flow: go to `/password/reset` on the live
site, submit a real user's email, and confirm the email arrives with a
working reset link pointing at `APP_URL`.

### 7. Watch logs if something fails

```bash
tail -f storage/logs/laravel.log
```

Failed mail sends throw exceptions that land here — most commonly
authentication failures (wrong SMTP credentials) or connection timeouts
(wrong port / firewall).

## Optional: queueing the reset email

Currently the reset email sends synchronously (blocking the HTTP request
until the SMTP provider accepts it — usually well under a second, so this is
fine as-is). If you ever want it queued through the existing Redis queue,
you'd publish a notification class overriding
`ResetPassword`/`SendsPasswordResetEmails::sendResetLinkResponse()` to
implement `ShouldQueue`, and make sure `php artisan queue:work` (or your
Supervisor config for it) is running on the server. Not necessary for this
feature to function — only relevant if reset-email volume ever becomes high
enough to matter.
