#!/usr/bin/env python3
"""
Re-theme the LocalPulse prototype to the Ferrata Labs design system.

Structure and behaviour are left alone: no screen HTML is rewritten and the
script block is untouched, so every interaction still works. What changes is
the theme layer, the brand lockup, and the copy rule in CLAUDE.md §7.

Every replacement asserts. A silent miss here would ship a half-themed page.
"""
import re, sys, pathlib

SRC = pathlib.Path(__file__).resolve().parent.parent / "prototypes/localpulse-prototype-v3.html"
DST = pathlib.Path(sys.argv[1])
h = SRC.read_text(encoding="utf-8")


def sub1(old, new, label):
    global h
    assert h.count(old) == 1, f"{label}: expected 1 occurrence, found {h.count(old)}"
    h = h.replace(old, new, 1)


MARK = ('<svg width="{s}" height="{s}" viewBox="0 0 48 48" role="img" aria-label="Ferrata Labs">'
        '<rect width="48" height="48" fill="var(--ink)"/>'
        '<g transform="translate(8,8)" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="square" fill="none">'
        '<path d="M4 27 V5"/><path d="M4 23 H20"/><path d="M4 16 H26"/><path d="M4 9 H16"/></g></svg>')

# ── 1. head ────────────────────────────────────────────────────────────────
sub1(
    "<title>LocalPulse Studio</title>",
    '<title>LocalPulse · Ferrata Labs</title>\n'
    '<meta name="viewport" content="width=device-width,initial-scale=1">\n'
    '<meta name="robots" content="noindex, nofollow">\n'
    # Declared exactly as the theme declares it, down to the version string.
    # sizes="any" and the type are not optional: without them Chrome deprioritises
    # an SVG favicon, falls back to /favicon.ico, which 404s here, and keeps
    # showing whatever it cached for the origin previously. Matching the theme's
    # URL byte for byte also means the whole domain shares one cache entry.
    '<link rel="icon" href="/wp-content/themes/ferrata-labs/assets/icon.svg?ver=1.2.0" sizes="any" type="image/svg+xml">\n'
    '<link rel="apple-touch-icon" href="/wp-content/themes/ferrata-labs/assets/apple-icon.png?ver=1.2.0" sizes="180x180" type="image/png">\n'
    '<!-- The same self-hosted faces the live site uses, referenced rather than copied\n'
    '     so this prototype cannot drift from the brand. -->\n'
    '<link rel="stylesheet" href="/wp-content/themes/ferrata-labs/assets/fonts.css">',
    "head",
)

# ── 2. tokens ──────────────────────────────────────────────────────────────
# Replace the palette, the two dark-theme blocks and the radius/shadow scale in
# one go. The prototype names its variables semantically, so remapping them here
# carries most of the re-theme on its own.
start = h.index(":root{")
end = h.index("*{box-sizing:border-box}")
assert 0 < start < end
h = h[:start] + """:root{
  /* Ferrata Labs design system, CLAUDE.md §4. Warm monochrome on one grey family,
     black as the primary accent, and iron oxide rationed to the small mono labels
     that number or name a thing. Light theme only: the dark palette the prototype
     shipped with is gone rather than overridden, because §4 permits one theme. */
  --bg:#F7F6F3; --surface:#FFFFFF; --surface-2:#F7F6F3; --surface-3:#F1EFEB;
  --line:#E8E6E1; --line-strong:#D8D5CF;
  --ink:#111111; --ink-2:#37352F; --ink-3:#6B6862;
  --accent:#111111; --accent-ink:#FFFFFF; --accent-soft:#F1EFEB; --accent-line:#D8D5CF;
  --advisory:#A34A32; --advisory-soft:#FBF3F0; --advisory-line:#E6CFC7;
  --ok:#346538; --ok-soft:#EDF3EC;
  --warn:#7A5C14; --warn-soft:#FBF6E9;
  --crit:#8F2F2D; --crit-soft:#FBECEC;
  /* the prototype used violet for the avatar; there is no purple in the system */
  --violet:#6B6862; --violet-soft:#F1EFEB;
  /* §4 forbids shadows. Separation is carried by hairlines instead. */
  --shadow:none; --shadow-lift:none;
  --font-ui:"IBM Plex Sans","IBM Plex Sans Fallback",system-ui,sans-serif;
  --font-display:Archivo,"Archivo Fallback",system-ui,sans-serif;
  --font-mono:"IBM Plex Mono","IBM Plex Mono Fallback",ui-monospace,monospace;
  /* content is sharp, chrome is pill */
  --r-card:0; --r-ctl:0; --r-chip:0; --r-pill:999px;
  --rail:224px; --topbar:58px;
}
""" + h[end:]

# ── 3. shell geometry: a full-width app bar with the rail beneath it ───────
sub1(
    ".rail{position:fixed;inset:0 auto 0 0;",
    ".rail{position:fixed;inset:var(--topbar) auto 0 0;",
    "rail offset",
)
old_topbar = h[h.index(".topbar{position:fixed"):h.index("\n", h.index(".topbar{position:fixed"))]
h = h.replace(old_topbar, """.appbar{position:fixed;top:0;left:0;right:0;height:var(--topbar);background:rgba(255,255,255,.86);backdrop-filter:blur(16px) saturate(1.4);border-bottom:1px solid var(--line);display:flex;align-items:center;gap:13px;padding:0 16px;z-index:45}""", 1)

# ── 4. brand lockup: Ferrata Labs, then the product, then the tenant ───────
sub1(
    '  <div class="brand"><span class="mark"></span><b>LocalPulse</b></div>\n',
    "",
    "rail brand removal",
)
old_header = h[h.index('<header class="topbar">'):h.index("</header>") + len("</header>")]
h = h.replace(old_header, f"""<header class="appbar">
  <button class="iconbtn menu mobonly" onclick="document.getElementById('rail').classList.toggle('on')" aria-label="Menu"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
  <a class="lvl brand" href="https://ferratalabs.ai" title="Ferrata Labs">{MARK.format(s=20)}<span>Ferrata Labs</span></a>
  <span class="lvl-sep" aria-hidden="true"></span>
  <span class="lvl product">LocalPulse</span>
  <span class="lvl-sep hide-sm" aria-hidden="true"></span>
  <span class="lvl ws hide-sm"><span class="ws-name">Sunspot Tire Company</span><span class="ws-meta">47 locations · Meta · GBP</span></span>
  <div class="spacer"></div>
  <span class="chip hide-sm">Illustrative data</span>
  <button class="iconbtn" onclick="document.getElementById('drawer').classList.toggle('on')" aria-label="Notifications"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 10-12 0c0 6-2 7-2 7h16s-2-1-2-7M13.7 20a2 2 0 01-3.4 0"/></svg><span class="dot"></span></button>
  <button class="who" onclick="go('users')"><span class="avi" id="avi">DW</span><span class="wn" id="uname">Dana</span><span class="pill plain" id="urole">Admin</span></button>
</header>""", 1)

# The crumb moves into the content column. go() writes to both ids, so they stay.
sub1(
    '<main class="main">\n',
    '<main class="main">\n<div class="crumb"><b id="crumb">Overview</b><span id="crumb2"></span></div>\n',
    "crumb relocation",
)

# ── 5. sign-in: the same lockup, on a light field ──────────────────────────
# The mark is a dark field with white strokes and does not reverse (§4), so the
# gradient hero it used to sit on had to go regardless of the no-gradient rule.
sub1('    <div class="rings" aria-hidden="true"></div>\n', "", "login rings")
sub1(
    '    <div class="lbrand"><span class="m2"></span> LocalPulse</div>',
    f'    <div class="lbrand">{MARK.format(s=22)}<span>Ferrata Labs</span>'
    f'<span class="lvl-sep" aria-hidden="true"></span><b>LocalPulse</b></div>',
    "login brand",
)
sub1(
    '<h1 style="margin-top:6px">Sign in to LocalPulse</h1>',
    '<h1 style="margin-top:6px">Sign in to LocalPulse</h1>',
    "login h1",
)

# ── 6. everything the token swap cannot reach ─────────────────────────────
OVERRIDES = """
/* ════ Ferrata Labs overrides ═══════════════════════════════════════════
   The token block above carries most of the re-theme. What is left is the
   structural half of §4: radius, shadow, gradient and the absence of blue.
   Nothing here changes behaviour. */

/* Type. Archivo for display and figures, Plex Sans for copy, Plex Mono for
   labels. Headings are 800 at display size per §4. */
h1,h2,h3,h4{font-family:var(--font-display);font-weight:600;letter-spacing:-.02em}
.phead h1,.lform h1{font-weight:800;letter-spacing:-.03em}
.num,.kpi .v,.mcard .v,.wx .t,.p-txt .hl{font-family:var(--font-display);font-weight:800}
body{font-family:var(--font-ui)}

/* Links are ink, not a coloured accent. */
a{color:var(--ink)}
a:hover{text-decoration:underline}
:focus-visible{outline:2px solid var(--ink);outline-offset:2px;border-radius:0}

/* Radius. Content is sharp; only floating chrome and small status objects
   are pill. A rounded element that is neither is a bug (§4). */
.pill,.tag,.cnt,.nav .cnt,.who,.lsig,.nav a{border-radius:var(--r-pill)}
.pill::before{border-radius:0}
.msg.u,.msg.s{border-radius:0}
.poster,.vthumb .poster,.gen,.sheet,.pv,.avi,.glyph,.ntf .ic,.why .n2,
.meter,.meter i,.bar .tr,.bar .tr i,.leg i,.sk,.iconbtn,.toast,.spend{border-radius:0}
/* A rotating square rather than a ring: rotated squares are in the icon
   vocabulary, round caps are not. */
.spin{border-radius:0;border-color:var(--line);border-top-color:var(--ink)}

/* Shadow and blur. The app bar keeps the one permitted blur; nothing else does. */
.card,.kpis,.sheet,.drawer,.toast{box-shadow:none}
.gen{background:rgba(255,255,255,.86);backdrop-filter:none}
.scrim{background:rgba(17,17,17,.42);backdrop-filter:none}

/* Gradients out. The skeleton loses its shimmer and becomes a flat tint. */
.sk{background:var(--surface-3);animation:none}

/* No blue anywhere. The channel glyphs were Facebook blue, an Instagram
   gradient and Google blue; they are now neutral mono initials, which also
   avoids implying a partnership or certification (§8). */
.glyph{background:var(--surface-3)!important;color:var(--ink-2);border:1px solid var(--line);font-family:var(--font-mono);font-weight:500;font-size:9px;letter-spacing:.04em}

/* Status dots are square. */
.srcbar .s i,.iconbtn .dot{border-radius:0}
.avi{background:var(--surface-3);color:var(--ink-2);font-family:var(--font-mono);font-weight:500}

/* Rail. Matches the product rail: hairline items, ink fill when active. */
.nav a.sel{background:var(--ink);color:#fff;font-weight:600}
.nav a.sel svg{color:#fff;stroke:#fff}
.nav .cnt{background:var(--advisory-soft);color:var(--advisory);border:1px solid var(--advisory-line);font-weight:500}

/* Tabs and segmented controls read in ink rather than a colour. */
.tabs button.sel{color:var(--ink);border-bottom-color:var(--ink)}
.seg button.sel{background:var(--ink);color:#fff}
.vthumb.sel .poster{border-color:var(--ink)}
.vthumb.sel .vl{color:var(--ink)}
.chan.sel{border-color:var(--ink);background:var(--surface-2)}
.banner.info{background:var(--surface-2);border-color:var(--line-strong)}
.toast{border-left-color:var(--ink)}

/* ── the three-level app bar ─────────────────────────────────────────── */
.lvl{display:flex;align-items:center;gap:9px}
.lvl.brand{font-family:var(--font-display);font-weight:800;font-size:14px;letter-spacing:.02em;text-transform:uppercase;color:var(--ink);text-decoration:none}
.lvl.brand:hover{text-decoration:none}
.lvl.brand svg{flex:0 0 auto}
.lvl.product{font-family:var(--font-display);font-weight:600;font-size:15px;letter-spacing:-.01em;color:var(--ink)}
.lvl.ws{flex-direction:column;align-items:flex-start;gap:0}
.lvl.ws .ws-name{font-family:var(--font-ui);font-weight:600;font-size:13px;color:var(--ink);line-height:1.25}
.lvl.ws .ws-meta{font-family:var(--font-mono);font-size:9.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3)}
.lvl-sep{width:1px;height:22px;background:var(--line);flex:0 0 auto}
/* Ink on a warm wash, matching .chip in the Ferrata product chrome exactly.
   Oxide is left to the rail counts, which are the one place on this screen that
   §4's "small mono labels that number a thing" applies. */
.chip{font-family:var(--font-mono);font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--accent);border:1px solid var(--accent-line);background:var(--accent-soft);border-radius:var(--r-pill);padding:3px 10px}
.who .wn{font-size:12.5px;font-weight:600}
.who .pill.plain{background:var(--surface-2);color:var(--ink-3);border-color:var(--line)}

/* The breadcrumb sits with the content now that the bar carries the brand. */
.main>.crumb{font-family:var(--font-mono);font-size:10.5px;letter-spacing:.13em;text-transform:uppercase;color:var(--ink-3);margin-bottom:14px}
.main>.crumb b{font-weight:500;color:var(--ink-2)}
@media (max-width:860px){.hide-sm{display:none!important}}

/* ── sign-in ─────────────────────────────────────────────────────────── */
.lhero{background:var(--bg);color:var(--ink);border-right:1px solid var(--line)}
.lhero h2{font-family:var(--font-display);font-weight:800;font-size:33px;letter-spacing:-.03em;color:var(--ink)}
.lhero p{color:var(--ink-2)}
.lbrand{font-family:var(--font-display);font-weight:800;font-size:15px;letter-spacing:.02em;text-transform:uppercase;color:var(--ink);gap:10px}
.lbrand b{font-weight:600;font-size:16px;letter-spacing:-.01em;text-transform:none}
.lsig{border:1px solid var(--line);background:var(--surface);color:var(--ink-2);border-radius:var(--r-pill)}
.lform{background:var(--surface)}
"""
# There are two style blocks; the overrides belong at the end of the first.
assert h.count("</style>") == 2, "expected exactly two style blocks"
h = h.replace("</style>", OVERRIDES + "</style>", 1)

# ── 6b. kill the colour literals at source ────────────────────────────────
# These sit in rules the override block already beats, but leaving teal and two
# blues in the file invites someone to reinstate them by deleting an !important.
sub1(
    '.g-fb{background:#1877F2}.g-ig{background:linear-gradient(135deg,#F9CE34,#EE2A7B 55%,#6228D7)}.g-gbp{background:#1A73E8}',
    "/* channel glyphs are neutral: see .glyph in the overrides */",
    "channel glyphs",
)
sub1(
    "background:#0B6B67;display:block}",
    "background:var(--ink);display:block}",
    "poster logo dot",
)
sub1(
    ".lhero{background:linear-gradient(150deg,#08302E,#0B6B67 52%,#125E52 100%);color:#EAF4F3;",
    ".lhero{",
    "login hero gradient",
)
# The .m2 dot was replaced by the mark itself.
sub1('.lbrand .m2::after{content:"";position:absolute;inset:7px;border-radius:50%;background:#0B6B67}', "", "m2 inner")
sub1('.lbrand .m2{width:24px;height:24px;border-radius:50%;background:#EAF4F3;position:relative}', "", "m2")
# The pulsing ring logo is replaced by the Ferrata mark.
sub1('.mark{width:22px;height:22px;border-radius:50%;background:var(--accent);position:relative;flex:0 0 auto}', "", "mark base")

# ── 7. §7 copy rule: no em dashes ─────────────────────────────────────────
h = h.replace("&mdash;", "—")
before = h.count("—")
# Order matters. A dash alone in a table cell is a "no value" marker rather than
# punctuation, and an en dash is the right glyph for that: §7 retires the em dash
# but keeps en dashes. Catch those first, or the separator rule below eats them.
h = h.replace(">—<", ">–<")
# A dash before a capital, a digit or a tag is separating two labels, which is
# what the middle dot does everywhere else on the site. Before lowercase it is
# joining a clause, which takes a colon.
h = re.sub(r"\s*—\s*(?=[A-Z0-9<])", " · ", h)
h = re.sub(r"\s*—\s*(?=[a-z])", ": ", h)
assert "—" not in h, f"{h.count('—')} em dashes survived"

DST.parent.mkdir(parents=True, exist_ok=True)
DST.write_text(h, encoding="utf-8")
print(f"wrote {DST}  ({len(h)} bytes, {before} em dashes replaced)")
