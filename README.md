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

## Remotes

| Repo | Holds |
|---|---|
| `balwaniPrem/FerrataLabs-wp` (this one) | the WordPress site |
| `balwaniPrem/FerrataLabs` | the Next.js source of truth |

Both are **public**. Nothing here is secret by construction: `wp-config.php`, the
salts and `../ferratalabs.sftp` are all gitignored and have never been committed.
Check that before adding anything to `site/`, because this repo mirrors a live
document root and the default assumption for a file on the server is that it does
not belong in git.

Worth knowing: the theme carries `parts/pledge.php`, so Pledge is public source
here as it already is in the Next repo. CLAUDE.md §12 calls Pledge unlisted rather
than private, and a public repo is the outer edge of that. If Pledge ever needs to
be genuinely confidential, both repos go private and it is authentication that
protects the app, not routing.

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

## `site/v2/` — the redesign preview

A static snapshot of the Next.js site served at **ferratalabs.ai/v2/**, so the
repositioning work can be reviewed on the real domain before anything is ported
into the theme. It is not WordPress: 17 plain HTML files plus their assets, sitting
in a directory the WordPress rewrite passes straight through.

**It is self-contained.** Every internal link stays inside `/v2/`, so clicking
around the preview never drops back onto the live v1 pages. That is `basePath`,
set in the Next repo's `next.config.ts` and gated behind `PREVIEW_BASE` so normal
production builds are untouched.

**Every page carries `noindex, nofollow`.** A preview on the live domain is
near-duplicate content against the real pages, and the snapshot script refuses to
write a page it could not mark. Do not "fix" this by removing the meta tag.

To regenerate after further changes in the Next repo:

```bash
cd ../FerrataLabs
PREVIEW_BASE=/v2 npm run build
PREVIEW_BASE=/v2 npx next start -p 3002 &
node scripts/snapshot-preview.mjs /v2 http://localhost:3002 ../FerrataLabs-WordPress/site/v2
```

Then upload `site/v2/` wholesale. Chunk filenames are content-hashed and the build
ID changes every build, so **delete the remote `v2/_next/` first** or the directory
accumulates orphans from every previous snapshot.

Two things the snapshot cannot carry, both expected:

- **The contact form does not submit.** It is a Next server action and there is no
  Next server behind these files. The form renders for review only.
- **`/pledge` and `/sterling` are absent.** The crawler follows links, and nothing
  links to the unlisted consoles. That is the correct outcome, per CLAUDE.md §12.

Delete the whole thing with one `rm -r` of `v2/` on the server when it has served
its purpose. Nothing else references it.

## `site/LocalPulse/` — the LocalPulse prototype

A working prototype at **ferratalabs.ai/LocalPulse/**, re-themed so it reads as a
Ferrata agent rather than a separate product. One self-contained HTML file: sign-in,
eleven screens, publish sheet, notifications drawer, all interactive.

Built from an untouched source by a script, both committed, neither served:

```
prototypes/localpulse-prototype-v3.html   the original, unmodified
scripts/build-localpulse.py               the transform
python3 scripts/build-localpulse.py site/LocalPulse/index.html
```

**Behaviour is untouched.** The script rewrites the theme layer, the brand lockup
and the copy; it never edits screen markup and never touches the script block. The
JS is byte-identical apart from punctuation. Re-run the script rather than editing
`site/LocalPulse/index.html`, or the next build discards the edit.

What the transform does, all of it §4 and §7:

- Remaps the palette to warm monochrome, and **deletes the dark theme** rather than
  overriding it, because §4 is light-theme only
- Radius to 0 for content, pill only for badges and nav items
- Shadows and gradients out, except the poster mocks: those are the marketing images
  the product generates, so they are depicted content, not chrome
- **No blue.** The Facebook, Instagram and Google glyphs were brand colours and are
  now neutral mono initials, which also avoids implying a partnership (§8)
- Archivo, IBM Plex Sans and IBM Plex Mono, referenced from the theme's own
  `assets/fonts.css` rather than copied, so the prototype cannot drift from the brand
- The app bar carries the three Ferrata levels: **Ferrata Labs → LocalPulse →
  workspace**, matching Pledge and Sterling. The mark belongs to the first level only
- 113 em dashes removed per §7: `·` where one separated two labels, `:` where it
  joined a clause, `–` where it marked an empty table cell

Two things worth knowing:

- **The URL is case-sensitive.** `/LocalPulse/` works, `/localpulse/` 404s. Say the
  word if you want a lowercase alias.
- **It carries `noindex, nofollow`**, on the same reasoning as `/v2/`: illustrative
  data on the live domain should not be in search results.

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
