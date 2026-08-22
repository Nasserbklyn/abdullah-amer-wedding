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

**Install** — appended to `templates/flex/css/custom.css`, which the Flex
template already loads last:

```
…existing custom.css…

/* CC-THEME-BEGIN */
…contents of flex-theme.css…
/* CC-THEME-END */
```

The append is idempotent: re-running strips any previous `CC-THEME-BEGIN…END`
block before adding the new one. The original file is backed up on the server as
`templates/flex/css/custom.css.bak-claude` (32,413 bytes).

**Uninstall** — delete everything between the two markers, or restore the
`.bak-claude` copy.

### What it changes

- Dark-blue palette (`#05080d` ground, `#2196f3` / `#5ec1ff` accents) and
  Exo 2 italic headings over Inter body text, loaded from Google Fonts.
- Helix header, megamenu, dropdowns and off-canvas drawer.
- SP Page Builder sections, addons, cards and pill buttons.
- The SP slider: adds a scrim over each slide and removes the translucent
  white box that sat behind the headline.
- `mod_agegate` restyled to match — **wording and behaviour untouched**.
- Footer, forms and selection colours.

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

## Cloudflare cache

`custom.css` is served with `cache-control: max-age=14400` and
`cf-cache-status: HIT`, so a CSS change takes up to **4 hours** to reach
visitors. Purge the CDN cache for it to appear immediately. When verifying a
change, always request the file with a cache-busting query string — the plain
URL will hand back the stale copy and make a good deploy look like a failure.

## Server changes made outside the template

- `.htaccess` — `frame-src 'self'` widened to
  `frame-src 'self' https://www.google.com` so the static homepage's Google
  Maps embed renders. Every other directive, including `frame-ancestors 'self'`,
  is unchanged. Original backed up as `.htaccess.bak-claude`.
- `index.html` / `privacy.html` — the static new-design pages, which shadow
  Joomla's `index.php` at `/`.
