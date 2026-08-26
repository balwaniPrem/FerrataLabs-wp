# Ferrata Labs, WordPress

The live site at ferratalabs.ai. Separate from the Next.js repo at
`../FerrataLabs`, which is the design reference and the source of `llms.txt`.

**Changes flow one way: Next.js first, here second.**

`../FerrataLabs` is the source of truth for design, copy and content. Work is built
and verified there on a branch, reviewed as a PR, and only then ported here and
pushed live.

**Do not edit this theme to try something out, and do not edit through wp-admin as a
shortcut.** Either makes the live site the source of truth for design, which it is
not, and the next `pull.sh` silently overwrites the work.

This repo takes straight commits. Branches and PRs live in the Next.js repo; a
branch here would describe a state the server is not in.

## What is tracked

`site/` mirrors the document root at `/home/wplive/web/wp-live/`.

| Tracked | Why |
|---|---|
| `wp-content/themes/ferrata-labs/` | The custom theme. This is the actual work. |
| `wp-content/mu-plugins/tenweb-init.php` | Small host hook, worth versioning |
| `robots.txt`, `llms.txt`, `llms-full.txt` | Root files we author |

Not tracked: WordPress core, default themes, third-party plugins, uploads,
caches, and `wp-config.php` (it holds database credentials and salts).

## Server

Host, port and user are in `../ferratalabs.sftp`, one level above this repo so it
can never be committed. Document root is `/home/wplive/web/wp-live/`.

```
./scripts/pull.sh          # fetch tracked paths from the server
./scripts/push.sh <path>   # upload one file, relative to site/
```

Pull first, commit, then edit. The server is authoritative: someone can change
the theme through wp-admin at any time, so a pull can overwrite local work.

## The two legitimately diverge

Not drift to be fixed:

- `/pledge` and `/sterling` consoles are Next-only routes and do not belong here
- `llms.txt` and `llms-full.txt` are **generated** in the Next repo and **exported**
  here. Never hand-edit them. Regenerate with `npm run export:wp` in `../FerrataLabs`
- Yoast settings, plugins and WordPress pages exist only on the server

## Known state

- **Yoast SEO** writes `robots.txt` and points it at `/sitemap_index.xml`, which
  currently 404s because Yoast's XML sitemap feature is switched off. Turning it
  on is the fix; do not hand-write a sitemap.
- **`insert-headers-and-footers`** (WPCode) is installed, which is where JSON-LD
  schema should go rather than into theme files.
- **`/pledge/`** is a published page carrying noindex. It should be trashed.
