# Facebook-Safe Single-URL One-Time Guard

This package adds **go.php** (a single URL you can give to Facebook Ads) that mints a fresh token **per click** and redirects to your guarded page (`index.php?t=...`). The guard consumes the token on first load to prevent sharing.

## Files
* `go.php` — single destination URL for your ad; mints a token and redirects.
* `index.php` — PHP one-time token guard; includes `index.original.html` after validation.
* `index.original.html` — your original page (auto-renamed from `index.html`).
* `generate_tokens.php` — admin helper to mint batches (locked with `?secret=`).
* `tokens.txt` — newline-separated tokens; consumed on first use.
* `blocked.html` / `expired.html` — fallback pages.

## How to deploy
1. Delete current repo files. Upload **the contents** of this ZIP to your repo root.
2. Remove any Node/Docker config (`package.json`, `Procfile`, `railway.toml`, `Dockerfile`) so Railway detects PHP.
3. Commit & push; Railway should redeploy with PHP automatically.

## Ad destination (give this to your media buyer)
Use **one URL**: `/go.php`
Example: `https://YOURDOMAIN/go.php`

> While testing on desktop, you can append `?test=1` to bypass needing a real Facebook `fbclid` parameter:
> `https://YOURDOMAIN/go.php?test=1`

## Optional settings
* In `go.php`, set `$REQUIRE_FB_REFERRER` and `$REQUIRE_FBCLID` to `true` for production (default), `false` for testing.
* In `index.php`, you may set `$ALLOWED_REFERRER_SUBSTR = 'facebook.com'` to add an extra filter.

## Quick test
1. Open `/go.php?test=1` → should 302 to `/index.php?t=...` and load the page.
2. Refresh → should hit `/expired.html`.
3. Open root `/` or `/index.php` w/o token → `blocked.html`.

## Notes
* File storage is ephemerally reset per deployment on platforms like Railway. This is fine because tokens are short-lived and minted per click. If you need cross-deployment persistence, switch `tokens.txt` to a DB or Redis.
