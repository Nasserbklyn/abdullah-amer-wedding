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

## Removed: `cc-heading-patch.js`

**Gone from the server and from this repo as of 24 Aug 2026. Do not reinstate it.**

While admin saves were silently failing (see *The write-rejecting database*
below), the staff heading could not be corrected at source, so a small script
rewrote it in the DOM on load. That was always a cover, not a fix — the HTML
leaving the server still read "OUR TEAM" for View Source and non-JavaScript
crawlers.

Once saves worked again the heading was corrected properly in SP Page Builder,
and the patch was removed:

- `templates/flex/js/cc-patch.js` — deleted
- its `<script>` tag stripped from `templates/flex/index.php`
  (20,494 → 20,383 bytes; the `cc-theme.css` `<link>` on the preceding line was
  deliberately preserved)
- original kept on the server as `templates/flex/index.php.bak-ccpatch`

Verified after the edit: HTTP 200, no PHP errors, zero `cc-patch` references,
`Knowledgeable Staff` present in the raw HTML, `OUR TEAM` absent.

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

**One untested alternative, found 24 Aug.** `error.php` already renders a
module position called `404`, on its second-to-last line:

    <?php echo $doc->getBuffer('modules', '404', array('style' => 'sp_xhtml')); ?>

Publishing the gate module to position `404` would need no template edit and no
duplicated warning wording, which is what made the hand-copy approach
unattractive. Whether it actually works is unknown — `$doc->getBuffer()`
resolves modules through the same helper that returned nothing usable in the
error context, so it may well fail identically. Worth ten minutes before anyone
hand-copies markup.

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

## ⚠ Those backup files are publicly downloadable

Found on 24 August by requesting them over HTTPS. Apache on this host protects
`.htaccess` **by exact filename only**, and hands `*.php.bak-*` to the PHP
handler as a *download* rather than executing it. So every backup this project
left in the document root is world-readable:

| URL | Response | What it exposes |
| --- | --- | --- |
| `/.htaccess.bak-404` | 200 · 2,218 B | The full CSP allowlist, the API-blocking rewrite, every hardening rule |
| `/.htaccess.bak-claude` | 200 · 2,195 B | The same, one revision older |
| `/templates/flex/index.php.bak-ccpatch` | 200 · 20,494 B | Template PHP source, as plain text |
| `/templates/flex/index.php.bak-theme` | 200 · 20,264 B | Template PHP source |
| `/templates/flex/error.php.bak-theme` | 200 · 6,377 B | Template PHP source |
| `/index.html.bak-claude` | 200 · 53,005 B | A complete, crawlable copy of the old static homepage |

`configuration.php` itself is correctly 403. The danger is the *pattern*: a
`configuration.php.bak` left by any future edit would publish the database
username, password and Joomla secret to anyone who guesses the URL. Backing up
a config file in place is a routine admin reflex, so this is not hypothetical.

Publishing our own CSP and rewrite rules is the smaller problem, though it does
tell an attacker exactly which sources are allowed and what is filtered.
`/index.html.bak-claude` is the compliance-relevant one: a full second homepage
on the licensee's domain, crawlable — nothing in `robots.txt` excludes it — and
frozen, so it drifts further from the live site with every change made to the
live site.

**These backups were created by this project. The exposure is ours.**

### The fix

One additive block, mirroring the `langmetadata.xml` pattern already in the
file, denies the whole class by extension including any future
`configuration.php.bak`:

```apache
<IfModule mod_authz_core.c>
  <FilesMatch "\.(bak|old|orig|save|swp|swo|tmp|dist|log|sql)(-[A-Za-z0-9_]+)?$">
    Require all denied
  </FilesMatch>
</IfModule>
<IfModule !mod_authz_core.c>
  <FilesMatch "\.(bak|old|orig|save|swp|swo|tmp|dist|log|sql)(-[A-Za-z0-9_]+)?$">
    Order allow,deny
    Deny from all
  </FilesMatch>
</IfModule>
```

Checked against every filename in the document root: it blocks the six URLs
above plus `robots.txt.dist`, and matches nothing the site serves — not
`index.php`, `configuration.php`, `cc-theme.css`, `agegate.js`, `robots.txt`,
`sitemap.xml`, or any asset. Append it to `.htaccess`, keep a copy of the
previous file first, and confirm `/` still returns 200 before walking away: a
malformed `.htaccess` returns 500 for the whole site.

The stray file `old` (809 bytes, a copy of Joomla's default `robots.txt`) is
also served but holds nothing sensitive. It has no extension, so the block does
not catch it.

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

## The write-rejecting database — root cause of everything

For roughly two days, **every** change made through the Joomla admin reported
success and silently vanished. Four menu renames, six edits in one sitting, a
brand-new article — all green, none persisted.

**Cause: the MySQL user `waleed` held `SELECT` but no `INSERT` / `UPDATE` /
`DELETE` on `cusecloudycannabis`.** Reads worked, so the site rendered and the
admin displayed accurate data; writes were refused, and Joomla — running with
error reporting off — swallowed the error and showed the success message
anyway. Re-ticking **ALL PRIVILEGES** in cPanel → MySQL Databases fixed it.

**The diagnostic that settles this class of fault in 30 seconds:** create a new
article with a throwaway name and see whether it appears in the list. It
isolates "can this install write at all" from every content-specific theory. It
should have been the first thing tried, not the twentieth.

Three theories were investigated and **disproved** — recorded so nobody
re-treads them:

| Theory | Why it was wrong |
| --- | --- |
| Admin and front end are separate installs | `index.html.bak-claude` — a file created only in the live document root — is present in `public_html/cuseclouds.com`. One install. |
| A demo-protection / read-only plugin | All 41 folders in `plugins/system` are recognizable stock or commercial extensions. No such plugin exists. |
| Database or disk quota exhausted | Database 117.95 MB, disk far from its limit. |

A fourth red herring cost real time: after privileges were restored, a report
that the test article "didn't show up" was almost certainly a search for the
*original* never-persisted article rather than a fresh save.

## The site, accurately

| | |
| --- | --- |
| Document root | `/home/egxbikjjcp2o/public_html/cuseclouds.com` |
| Database / prefix | `cusecloudycannabis` / `azl_` |
| Template | **Flex 4.3 by Aplikko.com** (Nov 2025, GPLv2) |
| Framework / builder | JoomShaper `helix3` / `helixultimate` and `sppagebuilder` |
| FTP | `p3plzcpnl509615.prod.phx3.secureserver.net` (`50.63.176.16`), port 21, explicit FTPS — `AUTH TLS` works, `AUTH SSL` refused |

**Earlier revisions of this file called the template "JoomShaper Flex". That was
wrong** — `templateDetails.xml` names Aplikko.com. JoomShaper supplies the
separate framework and page-builder extensions, and the demo imagery came from
JoomShaper's CDN, which is what caused the confusion.

`ftp.aazal.com` has **no DNS record** and can never connect; both domains
resolve only to Cloudflare, which does not proxy port 21. This account also
hosts `aazal.com` (AAZAL Print & Graphics) — the install this one was cloned
from, which is where the `azl_` prefix comes from.

## The menu

The rendered navigation is **Main Menu**, 43 items: 6 at top level
(101 HOME, 110 PRODUCTS, 108 BRANDS, 107 ABOUT, 1781 LEARN, **109 Delivery**)
and 37 children. A separate 6-item menu duplicated that top level and rendered
nowhere — four rename attempts edited *that* copy by mistake before the
database fault was even understood. It is now titled `AAZAL - UNUSED - do not
edit`.

## Fixed on 24 August

| Item | How |
| --- | --- |
| Menu label "DELIVERY & SPECIALS" → "Delivery" | `UPDATE azl_menu SET title='Delivery' WHERE id=109` via phpMyAdmin, while admin saves were still failing |
| HOPEline text number `467369` missing from the page body | It lives in the **site-wide footer disclaimer** (Helix3 template style → Copyright), not SP Page Builder — so the fix covers every page |
| 54 demo and junk pages publicly reachable | Trashed in SP Page Builder; trash deliberately not emptied. Includes the two "MILES STONED" pages |
| "OUR TEAM" heading | Corrected at source; `cc-patch.js` removed |
| Template vendor CDN references | Were SP Page Builder's **lazy-load placeholder**, not section backgrounds. Fixed by disabling Lazy Loading — clearing the backgrounds would have destroyed the real local artwork |
| Admin labels white-labelled to AAZAL | Template styles, menus and modules — **titles only** |

## ⚠ Load-bearing components with meaningless names

Custom module **ID 215**, position `top3`, renders the `#FFFF00` Part 129
consumer warning box. Until 24 Aug its title was the single word **"Name"**,
and it was very nearly unpublished as debris — which would have stripped the
statutory warning from every page. It is now titled
`AAZAL - NYS OCM Warning Band (DO NOT UNPUBLISH)`.

**This site is a clone carrying inherited naming, demo remnants, and
load-bearing parts with unhelpful labels. Things here are frequently not what
they look like.** Three separate instructions during this work looked purely
mechanical and would each have broken something real. Every one was caught by a
confirm-before-executing branch — *"check X is present first; if not, stop and
report"* — rather than by the diagnosis behind it. Keep writing that branch.

A related trap worth naming: **inspecting served HTML with a narrow `grep`
window**. Twice a module looked empty because the window ended just after the
opening tag. Widen the window before concluding anything is empty.

## Do not rename these

Renaming display titles is free. Renaming identifiers is not — Joomla matches
these against database rows:

- **`templates/flex/`** — requires editing `azl_template_styles`,
  `azl_extensions` and every asset path; Aplikko updates then no longer match,
  so security updates stop arriving
- **`helix3`, `helixultimate`, `sppagebuilder`** — loaded by folder+element
  from `azl_extensions`; renaming takes the site down
- **the `azl_` prefix** — ~70 tables plus `configuration.php`, and it is
  already AAZAL-derived

## Open items

| Item | Owner |
| --- | --- |
| Rotate the FTP password for `waleed@cuseclouds.com` | Account owner — credentials were shared in working sessions |
| 43 menu links all resolve to `/` because no destination pages exist | Deferred by decision until after the OCM response |
| Backup files in the document root are publicly downloadable | Fix written and verified against the file list; not yet deployed |
| Who altered the MySQL grants, and when | Not recoverable from the filesystem — see below |

## The "9 August lockdown" — withdrawn

An earlier revision of this file proposed that the mode-444 `configuration.php`
and the SELECT-only database user were one deliberate act. A filesystem
timeline taken on 24 Aug does not support it:

```
Aug  8 22:10   components/                                (site)
Aug  9 00:41   administrator/components/com_virtuemart
Aug  9 00:54   configuration.php                          (rewritten, mode 444)
```

That is the ordinary signature of **an extension install** — VirtueMart, around
midnight — with the configuration file rewritten thirteen minutes later and left
read-only, which is a common and frequently deliberate post-install state.
The install carries traces of several e-commerce extensions tried and abandoned
(VirtueMart, EasyStore, HikaShop), so an install that night is unremarkable.

**The privilege change leaves no filesystem trace, by design.** MySQL grants
live in cPanel, not in the site directory. Nothing reachable over FTP can
establish who changed them. The two events are probably unrelated.

Where the answer would actually live, in order of likelihood:

1. **Joomla → Users → User Actions Log.** The `actionlogs` plugin is installed;
   if it was enabled on 9 Aug it records admin logins and changes with usernames.
2. **cPanel → Metrics → Raw Access Logs**, filtered to `/administrator/` around
   8–9 Aug — shows which IPs reached the admin.
3. **GoDaddy account → Delegate Access** — who else can sign in at all.

One usable handle already exists: `administrator/logs/joomla_update.php` records
the Joomla 6.1.3 update on **22 Aug 07:31 UTC** from IPv6
`2600:4041:7a8f:3900:cd0c:63e5:5e0e:77b6`. That log only captures core updates,
so it says nothing about 9 Aug — but comparing that address against your own
tells you whether admin access that week was yours.

## Compliance status

All fourteen website items required by the OCM inspection correspondence are
verified against the live site, with none outstanding. The full record, with
per-item evidence, is the authoritative document.
