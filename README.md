# ⚠️ OBSOLETE: this repository is no longer maintained

> [!CAUTION]
> **This repository is obsolete.** XsFood.org has moved to a new repository:
>
> **➡️ [https://github.com/ir-dev/xsfood.org](https://github.com/ir-dev/xsfood.org/)**
>
> All new work, issues and pull requests go there. Nothing in this repository is deployed or kept up to date.

> [!WARNING]
> **Do not deploy this code.** It is kept only as a record. `data.php` has known security problems:
> SQL built from request parameters (SQL injection), an unauthenticated `?type=sendsms` endpoint that
> sends SMS to any number, and TLS verification turned off.

---

## XsFood.org: Share Your Excess Food (legacy code)

A simple app to share your excess food (#xsfood) with people who need it.

| Path | What it is |
|---|---|
| `index.html`, `data.php`, `css/`, `js/`, `images/`, `datedrop/` | The xsfood.org site as deployed in Feb 2024: a static page plus a PHP/MySQL API |
| `bkup_git_src_/` | The original 2017 code, including the Android WebView app (`Mobile/android`) |
| `key_pints.txt` | Deployment notes |

## Secrets

This repository contains no secrets. `data.php` reads its database and SMS settings from
environment variables or from a local `.env_secret` file, which is gitignored. To run it, copy
`.env_secret.example` to `.env_secret` and fill in the values.

The HTML pages are static, so they contain the placeholders `__GOOGLE_MAPS_API_KEY__` and
`__GOOGLE_GEOCODE_API_KEY__`. Replace them with your own keys at deploy time.
