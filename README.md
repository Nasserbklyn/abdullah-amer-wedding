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

The four rotating warnings (9 NYCRR Part 129):

1. “Cannabis can be addictive.”
2. “Cannabis can impair concentration and coordination. Do not operate a vehicle or machinery under the influence of cannabis.”
3. “There may be health risks associated with consumption of this product.”
4. “Cannabis is not recommended for use by persons who are pregnant or nursing.”

## ⚠️ Before going live — required

**Contact details.** Search `index.html` for `TODO` and fill in, when ready:
phone number, store hours, and confirm `info@cuseclouds.com` is a live mailbox.

(Already in place: the OCM license number `OCM-RETL-26-000487` — age gate, header,
hero, licensed section, footer — plus the operating address and the
On The Bus Inc. d/b/a Cuse Clouds licensee line.)

## Assets

- `assets/cuse-clouds-logo.svg` — vector recreation of the logo, self-contained
  (Exo 2 font embedded), safe to use in `<img>` tags, print, or other sites.
- `assets/cuse-clouds-logo.png` — 1500×1000 raster export of the same logo.
- `assets/og-image.png` — 1200×630 social-share image (referenced by Open Graph tags).
- `assets/favicon.svg` — browser tab icon.

The logo is used on the website itself; note that per the MRTA, the **physical
storefront sign** may not carry a logo — signage may only show the licensee/DBA name,
address, phone, email, website URL, directions, and the licensed activity.

## Hosting

- GitHub Pages with a custom domain: keep `CNAME` (`cuseclouds.com`) and `.nojekyll`.
- For the domain to resolve, DNS for `cuseclouds.com` must point to GitHub Pages
  (apex A records `185.199.108.153` … `185.199.111.153`, or per current GitHub docs),
  and Pages must be enabled for this repository with HTTPS enforced.

## Items from the inspection email that are *not* website items

These need to be answered in your reply to the OCM directly (not on the site):
proof the security system is installed/operational/monitored by a third party,
crowd-control description, hemp product intent + license number, storefront photo,
POS system name, labeled quarantine area photo (on camera), delivery intent +
vehicle make/model/VIN/plate, insurance, and the revised (logo-free) sign design.
