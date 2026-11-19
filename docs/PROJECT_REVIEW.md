# PurifyCMS Project Review

Review of `main` at `8e5069c` (v4.2.1), 2026-11-19. Covers the front controller, kernel, security layer, e-commerce/checkout, payment gateway, admin panel, deployment files and CI.

## 1. Critical security issues (details disclosed privately)

Four critical issues were confirmed against a local install. To avoid exposing unpatched installations, technical details and reproduction steps are being shared privately with the maintainers instead of in this public document.

| # | Area | Impact | Severity |
|---|------|--------|----------|
| S1 | Admin panel | Missing access control exposes customer data and allows data deletion | Critical |
| S2 | Order lookup | Insufficient input validation exposes stored data | Critical |
| S3 | Checkout | Order totals can be manipulated by the client | Critical |
| S4 | Checkout | Security checks crash instead of rejecting the request | High |

These should be patched and released before any of the hardening work below.

## 2. Bugs and hardening (lower risk)

- **APCu cache never hits.** `PurifyVolt::set()` and `PurifyVolt::get()` in `inc/kernel.php` use different key prefixes, so every read falls back to the per-request array. CI doesn't catch it because APCu isn't installed there.
- **Cache flush is publicly reachable.** A leftover license-revalidation action in `index.php` clears OPcache, APCu and the HTML cache with no login. It should be removed along with the other license leftovers (`LIC_STATUS`, the "Unlicensed" badge).
- **Static HTML cache may store per-visitor content.** Every GET with a 200 response is cached. Any page showing a CSRF token, captcha or cart state would serve the first visitor's copy to everyone.
- **`p_lang()` writes locale files at runtime** when a key is missing. That causes write races and stray strings, and works against the locale-parity CI check.
- **Read-only mode detection is string-based** in `inc/security.php` and should check the parsed route instead of substring matches on the full request URI.
- **Forwarded IP headers are trusted too broadly.** The admin IP allowlist and `PayTR::getClientIP()` both rely on client-controllable headers in some configurations. `getClientIP()` also validates the IP but returns it either way.
- **PayTR debugging is enabled in production.** `debug_on` is set twice in the request payload, ending at `1`. `callback_debug.log` stores every raw callback body with no rotation.
- **The checkout rate limit is per session**, so clients that don't send cookies are never limited.
- **Docker Compose conflicts with the installer.** It mounts `./inc/config.php` read-only. On a fresh clone Docker creates a directory there, so the web installer cannot write the config, which contradicts the README.

## 3. Architecture and code quality

- **`index.php` (782 lines) handles everything:** routing, checkout, webhooks, the contact form, the sitemap, maintenance mode and caching. Moving each `api/*` handler into its own file would make each one easier to review and test.
- **Authentication is checked separately in every admin page.** Enforce it once in the admin dispatcher in `index.php` (allowing only `login`/`logout`) so a new page can't ship without it.
- **Leftovers from the closed-source history:** split-string includes (`'in'.'c/ker'.'nel'`), license code, the `PurifyCMS/3.5` user agent, and a mix of Turkish and English comments.
- **The updater** extracts the latest GitHub release over the web root without a checksum or signature check. Consider publishing checksums or signing releases.
- **Tests are minimal.** The smoke test asserts `LIC_STATUS` and one tax calculation. Add HTTP-level tests run against `php -S`:
  - unauthenticated requests to admin routes are rejected
  - malformed order IDs are rejected
  - tampered checkouts are rejected
  - cache keys round-trip through APCu

## 4. Strengths

- File-per-order storage with `flock` suits a database-free design.
- Login uses bcrypt, `password_verify`, brute-force throttling and session ID regeneration.
- PayTR callbacks are verified with HMAC and `hash_equals`.
- The file manager is confined to `themes/` via `realpath`.
- Apache routes every `.php` request through `index.php`, so internal scripts can't be reached directly.
- The CI matrix covers PHP 8.1–8.3 and includes a Docker build.

## Suggested order

1. Patch S1–S4 and cut a security release.
2. Centralize admin authentication and add HTTP-level regression tests.
3. Fix the APCu prefix, remove license leftovers, and fix the Docker config mount.
4. Split `index.php` into per-route handlers.
