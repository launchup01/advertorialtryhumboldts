
# One‑Time Token Guard (Facebook single‑URL friendly)

This package lets you use **one campaign URL** in Facebook/Instagram ads while still preventing sharing.
Use **`go.php`** as your ad destination. It mints a fresh one‑time token on each ad click (requires `fbclid`), binds it to a secure cookie in the same browser, and redirects to `index.php?t=TOKEN`, which **consumes** the token and renders your landing page once.

---

## Files

- `go.php` — single URL for ads. Ignores FB crawler, **requires `fbclid`** (set `?test=1` during desktop testing), sets a secure cookie, and 302‑redirects to `index.php?t=...`.
- `index.php` — one‑time guard that **consumes** the token and verifies the cookie binding before including `index.original.html`.
- `index.original.html` — **your landing page content**. Replace this placeholder with your real HTML if needed.
- `blocked.html` / `expired.html` — fallback pages.
- `tokens.txt` — file‑based token store (ephemeral per deploy is OK).
- `generate_tokens.php` — optional batch token generator for manual tests; **LOCKED** by `?secret=`. Not used by the ad flow.

> **Important:** Remove/rename any `package.json`, `Procfile`, `railway.toml`, or `Dockerfile` in your repo so Railway's PHP builder (Nixpacks/Railpack) detects PHP automatically.

---

## Deploy (GitHub → Railway)

1. **Delete everything** in the repo (or use a clean branch).
2. Add **these files** (not the zip itself) to the repo.
3. Commit & push to the branch Railway deploys from (usually `main`).
4. Watch the deploy logs in Railway — it should detect **PHP / FrankenPHP**.
5. Visit `https://YOURDOMAIN/go.php?test=1` for a quick local test.

---

## Production config recommendations

- In `go.php`:
  - `REQUIRE_FBCLID = true`  (default) — **keep this ON** in production.
  - `REQUIRE_FB_REFERRER = false` — referrers are flaky; leave OFF.
  - `TOKEN_TTL = 1800` seconds (30 min). Adjust as needed.
- In `index.php`:
  - `ALLOWED_REFERRER_SUBSTR = ''` (empty) — keep empty for reliability.
  - `Cache-Control: no-store` is already set.

> **Ad Destination URL (give to your advertiser):**
>
> `https://YOURDOMAIN/go.php`

Facebook will automatically append `fbclid` to real ad clicks. The script uses that to mint a fresh token, bind it to the visitor’s browser via a secure cookie, and redirect to the guarded page. Sharing the redirected URL will fail (no cookie binding + token already consumed).

---

## Testing

- **Desktop quick test** (no Facebook):
  - Open: `https://YOURDOMAIN/go.php?test=1`
  - You should be redirected to `index.php?t=...` and see the page.
  - Refresh the same URL → `expired.html` (token consumed).
- **Manual generator test** (guard only):
  - Edit `generate_tokens.php` and set a strong `$SECRET`.
  - Open: `https://YOURDOMAIN/generate_tokens.php?n=1&secret=YOUR_SECRET`
  - Click the printed `index.php?t=...` once; reload → expired.

---

## Scaling notes (Redis/Postgres vs file storage)

- **File storage (`tokens.txt`)** is fine for modest traffic on a single instance.
- For **higher scale or multiple instances**, use **Redis** (fast, atomic ops, TTL) or **Postgres** (durable; use `DELETE ... WHERE token=?` to consume). These backends avoid file lock contention and let you scale horizontally.

---

## Troubleshooting

- Seeing `blocked` at `/` or `/go.php` without `?test=1` is **expected**.
- If PHP doesn’t run, ensure no Node/Docker config files remain in the repo.
- If `tokens.txt` isn’t writable, switch to:  
  `sys_get_temp_dir() . '/tokens.txt'` in both `go.php` and `index.php`.
- Facebook crawler prefetch (`facebookexternalhit`) is ignored so you don’t burn tokens before a real click.

