
# Deploy steps
1) Delete existing repo contents.
2) Upload *all* files from this merged package.
3) Ensure no Node/Docker config files remain (package.json, Procfile, railway.toml, Dockerfile).
4) Commit & push → Railway auto-detects PHP.
5) Test locally: `https://YOURDOMAIN/go.php?test=1` (should 302 → `index.php?t=...`). Refresh → expired.
6) Give ads team: `https://YOURDOMAIN/go.php`.
