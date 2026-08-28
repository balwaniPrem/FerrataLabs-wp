# Backup, 28 Aug 2026, before the v2 rollout

A point-in-time capture of the live WordPress site as it stood before any attempt
to promote the v2 redesign. Taken so the current site can be put back.

```
pages/     the 17 live content pages, exactly as the server rendered them
wp-json/   the page records as stored in the database, via the REST API
files/     everything tracked under site/, minus v2
```

`/v2/` is deliberately excluded, as is anything generated from it. The folder name
ends in "-pre-v2", so a `find` for `*v2*` matches every path inside it; that is the
folder name, not v2 content.

## What this does not contain

**There is no database dump.** WordPress page content, Yoast settings, plugin
configuration and users live in MySQL, which SFTP cannot reach. `wp-json/pages.json`
is the closest substitute: it holds the rendered and raw content of every page, but
not settings, menus, users or plugin state.

So a restore from this backup is: put `files/` back, and re-enter anything that
lived in the database. If the rollout is going to touch the database, take a real
dump from the 10Web panel first. This is not a substitute for one.

## Things learned while taking it, which matter for the rollout

- **`index.php` beats `index.html` on this server.** Verified by serving both from
  one directory: PHP won. Static files therefore cannot replace the home page, and
  removing WordPress's `index.php` would take down wp-admin and every route with it.
- **The host caches pages.** The home page returns `x-cache`, and a deleted test
  directory kept serving from cache after the files were gone. Any cutover needs a
  cache purge or it will look like nothing happened.
- **Every v2 page carries `noindex, nofollow`.** Promoting them unchanged would
  remove the whole site from search.
- **The v2 contact form does not submit.** It is a Next server action and there is
  no Next server behind static files. The WordPress theme's PHP handler works today.
