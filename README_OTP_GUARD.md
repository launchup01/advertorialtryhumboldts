# One-Time Token Guard Implementation

This version of the site adds a PHP guard to enforce one‑time link access.

## How it works
* Each visitor must have a valid token via the `?t=` query parameter.
* Valid tokens are stored in `tokens.txt`. Once used, the token is removed from the file.
* Requests with no token or an invalid token are redirected to `/blocked.html` or `/expired.html`.

## Minting tokens
Visit `generate_tokens.php?n=5` to mint five links. Each link will look like `index.php?t=...` and can only be used once. Adjust `n` to generate a different number of links.

## Customization
* To restrict access to clicks from a particular referrer (e.g. Facebook), set `$ALLOWED_REFERRER_SUBSTR` at the top of `index.php` to your desired domain.
* Update `$REDIRECT_BLOCKED` and `$REDIRECT_EXPIRED` in `index.php` if you change the names of your blocked/expired pages.

## Deployment notes
* Remove any Node.js–specific files (`Procfile`, `package.json`, `package-lock.json`, `railway.toml`) so that Railway detects PHP automatically【360334144933384†L28-L34】.
* Railway resets the filesystem on each deploy. Tokens stored in `tokens.txt` are therefore reset after every deployment.
