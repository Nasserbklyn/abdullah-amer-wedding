# Joomla compliance layer — install guide

`cc-compliance.php` puts the three things the OCM inspection asked for on
**every page** of the Joomla site — the Helix Framework template, the Flex
template, and every SP Page Builder page alike:

1. a **21+ age gate** that blocks the page until the visitor confirms;
2. the **bright-yellow (`#FFFF00`) consumer-warning box**, at the very top of
   the page, carrying one of the **four required warnings, rotated** between
   page loads;
3. a **compliance footer** with the OCM license number, the licensee name, the
   address/phone/email, and the **NYS HOPEline**.

## Why this is needed

The new static homepage sits at `/` and shadows Joomla's `index.php`. That
covers the front door only. Every other address on the site still goes to
Joomla and still renders the old, non-compliant page — for example:

- `https://cuseclouds.com/index.php`
- every menu item and SEF URL (`/about-us`, `/products`, `/contact`, …)
- every SP Page Builder page
- article, category, and search result pages

An inspector who opens any of those sees no age gate and no yellow warning
box. This file closes that gap at the template level, so it applies
everywhere at once and keeps applying to any new page built in SP Page
Builder later.

## Install (two steps, cPanel File Manager)

### Step 1 — upload the file

Upload `cc-compliance.php` into the **site root** — the folder that contains
`configuration.php`, i.e. `/home/egxbikjjcp2o/cuseclouds.com/`.

### Step 2 — add one line to the active template

Find the active template folder under `/templates/`. It is the JoomShaper
Flex template — the folder name is usually `flex`, `shaper_flex`, or
`shaper_helixultimate`. (In Joomla admin: **System → Site Templates
Styles** shows which one is default. Or open `/index.php` in the browser,
View Source, and look for a `/templates/<name>/` path in the stylesheet
links.)

Open `/templates/<that-folder>/index.php`, find the opening `<body …>` tag,
and add this **immediately after it**:

```php
<?php include_once JPATH_BASE . '/cc-compliance.php'; ?>
```

So it ends up looking like:

```php
<body class="<?php echo $bodyClass; ?>">
<?php include_once JPATH_BASE . '/cc-compliance.php'; ?>
  <div class="body-wrapper">
  ...
```

**Back the file up first** — copy `index.php` to `index.php.bak` in the same
folder before editing, so it is a one-click revert.

### Step 3 — clear caches

Joomla admin → **System → Clear Cache** (and **Clear Expired Cache**). If SP
Page Builder caching or a CDN is on, purge those too.

## Verify

Open each of these in a **private/incognito window** (the gate remembers your
answer for the rest of the browser session, so a normal window will not show
it a second time):

| Check | Expected |
| --- | --- |
| `https://cuseclouds.com/index.php` | Age gate appears and blocks the page |
| Click "Yes, I am 21 or older" | Gate disappears, site works normally |
| Top of the page | Full-width yellow bar, black text, with the general warning + one required warning |
| Reload a few times | The second line cycles through all four required warnings |
| Bottom of the page | License #, address, phone, email, HOPEline |
| Any menu item / SEF URL | Same gate and same yellow bar |
| Joomla administrator | **No** gate — the layer excludes the admin app |

## Undo

Delete the one `include_once` line from the template's `index.php` (or
restore `index.php.bak`). Nothing else in Joomla is modified — no database
row, no extension, no module, no template override.

## Design notes

- **Cache-safe.** The warning rotation runs in the browser, not in PHP, so a
  cached page still rotates correctly. Joomla's page cache, SP Page Builder
  cache, and any CDN in front of the site can stay on.
- **No dependencies.** All CSS and JS is inline and namespaced `cc-`. It does
  not rely on — or interfere with — Bootstrap, jQuery, Helix, or SPPB styles.
  The gate uses `z-index: 2147483000` so it sits above sticky headers,
  off-canvas menus, and cookie banners.
- **Fails closed.** The gate is real markup in the HTML and is removed by
  JavaScript on confirm. With JavaScript disabled it stays up, which is the
  safe direction.
- **Admin and AJAX excluded.** The layer returns early for the administrator
  app and for `format=` / `tmpl=component|raw` requests, so Joomla's admin,
  SPPB's editor, and AJAX/JSON endpoints are untouched.
- **Accessible.** `role="dialog"` + `aria-modal`, focus moves to the confirm
  button, Tab is trapped inside the gate, visible focus rings, and the
  rotation pauses for `prefers-reduced-motion`.
