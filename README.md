# Ferrata Labs, WordPress

The live site at ferratalabs.ai. Separate from the Next.js repo at
`../FerrataLabs`, which is the design reference and the source of `llms.txt`.

**Two copies, on purpose.** The Next.js app is where the design system, the typed
content and the generated AEO files live. This repo is what actually serves the
domain. Neither is a copy of the other; when they disagree, decide deliberately
rather than syncing blindly.

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

## Known state

- **Yoast SEO** writes `robots.txt` and points it at `/sitemap_index.xml`, which
  currently 404s because Yoast's XML sitemap feature is switched off. Turning it
  on is the fix; do not hand-write a sitemap.
- **`insert-headers-and-footers`** (WPCode) is installed, which is where JSON-LD
  schema should go rather than into theme files.
- **`/pledge/`** is a published page carrying noindex. It should be trashed.
