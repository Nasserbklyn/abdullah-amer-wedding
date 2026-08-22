# Joomla side of cuseclouds.com

The live site is **Joomla 6.1.3** on the **JoomShaper Flex** template (Helix
Framework) with **SP Page Builder 6.8.0**, hosted on GoDaddy cPanel at
`/home/egxbikjjcp2o/cuseclouds.com`, behind a Cloudflare edge cache.

## Compliance: already in place, do not duplicate

The Joomla site was **already OCM-compliant before any of this work**. It
carries its own furniture, and all of it renders on every front-end route:

| Element | Where it lives |
| --- | --- |
| 21+ age gate | `mod_agegate` module (`#agegate-…`), assigned to all pages, `z-index: 99999` |
| OCM license number | Inside the age gate card |
| NYS HOPEline (call / text / website) | Inside the age gate card |
| `#FFFF00` consumer warning box | `#ocm-warning-band` custom module, inside `#sp-top-bar` |
| All four Part 129 warnings | Present in the page body |
| OCM verification link | "Verify with NYS OCM" button (`#plf-ocm .o-btn`) |
| Location map | SP Page Builder `openstreetmap` addon |

An earlier `cc-compliance.php` template include added a *second* age gate and a
*second* yellow box on top of these. It has been removed from the server and
deleted from this repo. **Do not reintroduce it.** Anything added here must
check what the site already renders first.

## What is installed: `flex-theme.css`

The new design's palette and typography, applied site-wide so every Joomla page
matches the new homepage.

**Install — as served today.** The file lives at
`templates/flex/css/cc-theme.css` and is linked from the template's `<head>`,
one line inserted before `</head>` in `templates/flex/index.php`:

```php
<link rel="stylesheet" href="<?php echo Joomla\CMS\Uri\Uri::root(true); ?>/templates/flex/css/cc-theme.css?v=2" />
```

That link sits after every other stylesheet, so it wins on ordering. The
original template is backed up on the server as
`templates/flex/index.php.bak-theme`.

**Why a separate file rather than `custom.css`.** The theme was originally
appended to `custom.css`, which the Flex template already loads last. That
works, but the CDN in front of the site holds `custom.css` with a 4-hour TTL
and would not release it — a purge from the hosting panel left every asset's
`age` untouched (`custom.css`, `template.css`, `preset5.css` and the images
were all still ~4,950 s old afterwards), so visitors kept getting the
pre-theme copy. A filename that has never been requested cannot be stale:
`cc-theme.css` came back `cf-cache-status: MISS` and the theme was live
immediately.

The appended block is still present in `custom.css` between
`CC-THEME-BEGIN` / `CC-THEME-END` markers. It is harmless — the same rules
resolving twice — and acts as a fallback if the `<head>` link is ever removed.
To tidy it up, delete everything between the markers or restore
`templates/flex/css/custom.css.bak-claude` (32,413 bytes).

**Uninstall** — remove the `<link>` line from `index.php` (or restore
`index.php.bak-theme`), and restore `custom.css.bak-claude`.

**Bump the `?v=` number** whenever `cc-theme.css` changes, or the CDN will
serve the old copy for four hours. It is at `v=2` today.

### What it changes

- Dark-blue palette (`#05080d` ground, `#2196f3` / `#5ec1ff` accents) and
  Exo 2 italic headings over Inter body text, loaded from Google Fonts.
- Helix header, megamenu, dropdowns and off-canvas drawer.
- SP Page Builder sections, addons, cards and pill buttons.
- The SP slider: adds a scrim over each slide and removes the translucent
  white box that sat behind the headline.
- `mod_agegate` restyled to match — **wording and behaviour untouched**.
- Footer, forms and selection colours.
- Replaces the JoomShaper Cloudinary stock photography still sitting behind
  `#contact-us` and `#section-id-1481572543` with the brand gradient. SPPB
  writes section backgrounds into inline `<style>` blocks rather than style
  attributes, so these are targeted by id. Only the imagery changes — the
  section content, including the staff copy under the "OUR TEAM" heading,
  is left alone.

### What it deliberately does not change

The site's OCM furniture keeps its existing appearance, because recolouring it
would make the statutory warnings less conspicuous:

- `#sp-top-bar` keeps its cream background; the theme only pins its text colour
  (the new dark body colour was being inherited into it and dropping it below
  WCAG AA).
- `#ocm-warning-band` stays `#FFFF00` with true black text — it renders inside
  `#sp-top-bar`, so the rule carries the parent id to win on specificity.
- The OpenStreetMap addon and its attribution are left alone.

### Verified on the live site

A WCAG pass over the themed page went from 8 failures below AA to **0** across
49 text nodes. Age gate still shows and dismisses, warning band still
`rgb(255,255,0)` on `rgb(0,0,0)`, all four required warnings present, map tiles
loading, no horizontal overflow, no JavaScript errors.

## Temporary: `cc-heading-patch.js`

**This is a cover, not a fix. Remove it once the real edit lands.**

The last section's heading should read "Knowledgeable Staff"; the database
still holds "OUR TEAM". Two attempts to change it in SP Page Builder did not
persist, and nothing is caching it — the page is served `no-store` and
re-rendered from the database on every request, so the old value is genuinely
still stored.

`templates/flex/js/cc-patch.js` rewrites the heading in the DOM on load,
linked from the template head after the stylesheet:

```php
<script src="<?php echo Joomla\CMS\Uri\Uri::root(true); ?>/templates/flex/js/cc-patch.js?v=1" defer></script>
```

It is scoped to `#section-id-1481572543`, matches the exact heading text, and
skips any heading that wraps other markup, so it cannot affect anything else.

What it does and does not achieve: the live DOM carries the new wording, so
screen readers and JavaScript-executing crawlers see it. The HTML leaving the
server still contains "OUR TEAM", so `curl`, View Source, and any crawler that
does not run JavaScript will see the old text.

**The real fix.** The home menu item is Itemid 101 — open it under Menus →
Main Menu to confirm which SP Page Builder page it points at, edit that page's
heading, Apply, then Save. Then delete `templates/flex/js/cc-patch.js` and its
`<script>` tag.

## The 404 page

Unknown paths used to return Apache's bare 13-byte `404 Not Found`: the
`.htaccess` had no Joomla rewrite block, so requests never reached Joomla and
`templates/flex/error.php` never rendered. Joomla's own shipped block now sits
in `.htaccess` between `CC-404-BEGIN` / `CC-404-END`. It excludes real files
and directories, so only non-existent paths are affected — `/administrator/`
still returns 403, static assets still serve directly.

A missing path now returns a genuine HTTP 404 rendering the Flex error page:
"404 — Oops... Page Not Found!" with a link home, in the site theme.

### The age gate is not on the error page

This was attempted and reverted. `jdoc:` tags are not parsed in `error.php`,
so the module has to be rendered programmatically — but
`ModuleHelper::getModule('mod_agegate')` returned nothing usable in Joomla's
error-rendering context. The page ended up with the module's CSS and JS links
and no gate markup at all. It was rolled back.

It matters less than it sounds. The error layout renders standalone — no
header, no menu, no modules — so the page shows no cannabis content
whatsoever: a heading, one sentence, and "Go Back to Homepage", which lands on
`/` where the gate is. There is nothing for a gate to protect.

The only reliable way to put it there is to hand-copy the gate's markup into
`error.php`. That creates a second copy of statutory warning wording that
would silently drift from the module whenever the module is edited — the same
duplication that caused the double-gate problem earlier in this project. Not
recommended.

### Bugs found and fixed in `error.php`

Routing 404s into Joomla made the Flex error page render for the first time,
which exposed pre-existing faults in it:

- It enqueues `jquery.easing`, `main.js` and `jquery.countdown` but never
  jQuery, so every error page threw `jQuery is not defined` three times.
  `HTMLHelper::_('jquery.framework')` is now called before the asset chain.
- With jQuery loading, `main.js` then ran and threw on three globals that
  `index.php` declares and `error.php` did not: `sp_preloader`,
  `sp_offanimation` and `stickyHeaderVar`. `main.js` only `typeof`-guards them
  before assigning, which throws under strict mode. All three are now declared
  in the head before `$header_contents`, with values suited to a page that has
  no header or preloader.

The 404 page now renders with zero JavaScript errors. The original file is
backed up as `templates/flex/error.php.bak-theme`.

## Cloudflare cache

Static assets are served with `cache-control: max-age=14400` behind Cloudflare,
so an edited CSS file takes up to **4 hours** to reach visitors. A purge from
the hosting panel did not clear it — every asset's `age` was unchanged
afterwards — so do not rely on purging. Ship CSS changes under a new filename
or a bumped `?v=` query instead.

When verifying, request the file with a cache-busting query string. The plain
URL hands back the stale copy and makes a good deploy look like a failure —
that happened once here and triggered a needless rollback.

## Server changes made outside the template

- `.htaccess` — `frame-src 'self'` widened to
  `frame-src 'self' https://www.google.com` so the static homepage's Google
  Maps embed renders. Every other directive, including `frame-ancestors 'self'`,
  is unchanged. Original backed up as `.htaccess.bak-claude`.
- `templates/flex/index.php` — a `<link>` for `cc-theme.css` and a `<script>`
  for `cc-patch.js`, both added before `</head>`. Backed up as
  `templates/flex/index.php.bak-theme` (that backup predates both lines).
- `templates/flex/error.php` — a `<link>` for `cc-theme.css` before `</head>`,
  so error pages carry the theme in their own right. Backed up as
  `templates/flex/error.php.bak-theme`.
- `.htaccess` — Joomla's front-controller rewrite added between
  `CC-404-BEGIN` / `CC-404-END` markers, so a path that does not exist reaches
  Joomla instead of Apache. Backed up as `.htaccess.bak-404`.
- `index.html` — renamed to `index.html.bak-claude` so it no longer shadows
  `index.php`. `/` now serves the Joomla SPPB home page. Rename it back to
  `index.html` to restore the static page.
- `privacy.html` and `assets/` — still served from the doc root.

## Verified after the switch

`/` returns HTTP 200, 215 KB, Joomla title, Flex template, zero markers from the
static page. In a browser: age gate shows and dismisses, body `rgb(5,8,13)`,
Exo 2 headings, 4 slider items, the full menu (HOME / PRODUCTS / BRANDS / ABOUT /
LEARN / DELIVERY & SPECIALS), 6 map tiles, warning band `rgb(255,255,0)` on
`rgb(0,0,0)`, all four Part 129 warnings, licence number present, no horizontal
overflow, no JavaScript errors.

Note `/` is served `cache-control: no-store` and `cf-cache-status: DYNAMIC`, so
the page switch reaches visitors immediately — but `custom.css` is a cache HIT
with a 4-hour TTL, so the new theme only appears once that expires or the CDN
cache is purged.
