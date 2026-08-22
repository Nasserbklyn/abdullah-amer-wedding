# Cuse Clouds Cannabis — cuseclouds.com

Website for **Cuse Clouds** (registered d/b/a of **On The Bus Inc.**), a New York
State licensed adult-use cannabis retail dispensary at 900 E Fayette St, Syracuse,
NY 13210. License # OCM-RETL-26-000487 (Adult-Use Retail Dispensary License;
licensed activity: in-person retail sales with delivery). Built as a single static
page (`index.html`) so it can be hosted on GitHub Pages (the `CNAME` file points
the site at `cuseclouds.com`).

## NY OCM website compliance

This site implements the website items from the OCM inspection correspondence:

| OCM requirement | Where it is implemented |
| --- | --- |
| Age gate confirming visitors are 21+ | `#age-gate` overlay — blocks all content until "Yes, I am 21 or older" is clicked; "No" redirects away; remembered for the browser session only |
| OCM license number displayed | Age gate, header chip, hero, "Fully Licensed & Verified" section, footer |
| NYS HOPEline phone, text number, and website | Age gate, "Support & Resources" section, footer — Call 1‑877‑8‑HOPENY (1‑877‑846‑7369) · Text HOPENY (467369) · oasas.ny.gov/hopeline |
| Consumer warning in a bright yellow text box, conspicuous | Full-width `#FFFF00` band at the very top of the page, inside the age gate, and in the footer: “For use only by persons 21 years of age and older. Keep out of reach of children and pets. If someone accidentally consumes cannabis, contact the Poison Center. Consume responsibly.” |
| One of the four required warnings, in a rotating manner | `#rotating-warning` inside the yellow band — a different warning is selected on each page load (distributed evenly via a stored counter) and the display also cycles through all four while the page is open |
| No prohibited content | No depictions of smoking/consumption, no cartoons, no health or medical claims, no pricing or promotional offers |

The table above describes the static pages (`index.html`, `privacy.html`). The
same items are applied to every page of the underlying Joomla site by
`joomla/cc-compliance.php` — see "Hosting and the existing Joomla site" below.

The four rotating warnings (9 NYCRR Part 129):

1. “Cannabis can be addictive.”
2. “Cannabis can impair concentration and coordination. Do not operate a vehicle or machinery under the influence of cannabis.”
3. “There may be health risks associated with consumption of this product.”
4. “Cannabis is not recommended for use by persons who are pregnant or nursing.”

## Business details on the site

All business details are filled in (merged from the previous cuseclouds.com
Joomla homepage): address 900 E Fayette St, Syracuse, NY 13210 · phone
(315) 214-4017 · email cs@cuseclouds.com · hours Mon–Fri 9:00 AM–11:00 PM,
Sat–Sun 9:00 AM–10:00 PM · licensee On The Bus Inc. d/b/a Cuse Clouds ·
license `OCM-RETL-26-000487`.

The homepage also carries the old site's content in the new design: product
categories (flower, pre-rolls, edibles, vapes &amp; cartridges, topicals,
tinctures, concentrates, accessories), the brands lineup, About/First-Time
Visitors, the Cannabis Learning Center topics, In-Store &amp; Delivery services,
and the OCM verification links (dispensary-location-verification and
buylegal.cannabis.ny.gov). `privacy.html` reproduces the Privacy Policy
(effective Aug 17, 2026).

Notes: the old site's contact form was replaced with direct phone/email links
(a static site has no form backend), and the "Delivery &amp; Specials" menu item
was renamed to "Services"/"Delivery" — advertising promotions or "specials" is
restricted under Part 129.

## Assets

- `assets/cuse-clouds-logo.svg` — vector recreation of the logo, self-contained
  (Exo 2 font embedded), safe to use in `<img>` tags, print, or other sites.
- `assets/cuse-clouds-logo.png` — 1500×1000 raster export of the same logo.
- `assets/og-image.png` — 1200×630 social-share image (referenced by Open Graph tags).
- `assets/favicon.svg` — browser tab icon.

The logo is used on the website itself; note that per the MRTA, the **physical
storefront sign** may not carry a logo — signage may only show the licensee/DBA name,
address, phone, email, website URL, directions, and the licensed activity.

## Hosting and the existing Joomla site

cuseclouds.com runs on GoDaddy shared hosting (cPanel), doc root
`/home/egxbikjjcp2o/cuseclouds.com`. The site there is **Joomla**, built on the
**JoomShaper Helix Framework** with the **Flex** template and **SP Page
Builder**.

The static `index.html` in this repo is uploaded to that doc root, where it
shadows Joomla's `index.php` and serves as the homepage at `/`. The Joomla site
itself is untouched and still reachable — `/index.php`, every menu item, every
SEF URL, and every SP Page Builder page.

That split matters for compliance: shadowing the front door does **not** make
the rest of the Joomla site compliant. `joomla/cc-compliance.php` closes that
gap — a single PHP include, added once to the Flex template's `index.php`, that
adds the age gate, the yellow rotating warning band, and the license/HOPEline
footer to **every** Joomla page. See `joomla/INSTALL.md` for the two-step
install, verification checklist, and how to undo it. It needs no database row,
no extension, and no template override, and it is cache-safe.

`CNAME` and `.nojekyll` are left in place only so the repo can also be served
from GitHub Pages as a staging preview; they are ignored by the cPanel host.

## Items from the inspection email that are *not* website items

These need to be answered in your reply to the OCM directly (not on the site):
proof the security system is installed/operational/monitored by a third party,
crowd-control description, hemp product intent + license number, storefront photo,
POS system name, labeled quarantine area photo (on camera), delivery intent +
vehicle make/model/VIN/plate, insurance, and the revised (logo-free) sign design.
