# Blackridge Brokerage Inc. — Website Plan

**Client:** Blackridge Insurance Brokerage Inc. (d/b/a Blackridge Brokerage, Inc.)
**Location:** 9128 4th Avenue, Brooklyn, NY 11209 (Bay Ridge) · (718) 836-0500 · fax (718) 836-2020 · BlackridgeIns@outlook.com
**Prepared:** August 2026, from an online research sweep (corporate filings, directories, the agency's legacy WordPress site, TLC/DMV insurance regulations, American Transit context, and a competitor scan).

---

## 1. What the research established

### Verified business facts
| Fact | Detail | Confidence |
| --- | --- | --- |
| Legal name | Blackridge Insurance Brokerage Inc. (NY DOS ID 4505581, filed Dec 26, 2013, Active) | High |
| Public name | "Blackridge Brokerage, Inc." (used on its own site, LinkedIn, directories) | High |
| Address | 9128 4th Ave, Brooklyn, NY 11209 — Bay Ridge, between 91st & 92nd St, ~4 blocks from the 95th St R station | High |
| Phone / fax / email | (718) 836-0500 / (718) 836-2020 / BlackridgeIns@outlook.com — identical across every directory and the agency's own contact page | High |
| Services | Black Car / Car Service (TLC) insurance, personal auto, homeowners | High |
| People | Hakim Benaissa, Insurance Broker (LinkedIn) | High |
| Carrier tie | Listed in American Transit Insurance Co. (ATIC) broker directory | High |
| Reputation | Nextdoor "Neighborhood Favorite" (serves Bay Ridge, Dyker Heights, Sunset Park East); no star reviews found anywhere | Medium |
| Hours | **Resolved:** Mon–Fri 8:00 AM–6:00 PM, Sat/Sun closed — per the agency's own WordPress footer, corroborated by Waze (Yellowpages' 9–5 appears stale) | High |

### From the agency's own legacy site (blackridgeinsdotcom.wordpress.com)
- Tagline: **"FULL DMV & TLC SERVICES"** (reused as a hero chip)
- Vehicle classes they service: **boro taxis, car service, black cars, luxury limousines** (folded into the flagship coverage card)
- About page claims **"20 years in the Taxi & Limousine industry"** (reused as "more than 20 years" — the client's own claim)
- The legacy site's footer address has a typo (9125); every other source and their contact page say **9128** — the site uses 9128
- The intended domain was evidently **blackridgeins.com** (encoded in the WordPress subdomain); it does not resolve today — a candidate to register

### Domain facts the copy must get right
- TLC-licensed FHVs (livery / black car / luxury limousine, class set by the base) must carry **$100,000/$300,000 bodily-injury liability, $10,000 property damage, uninsured-motorist, and no-fault (PIP)**. PIP was **verified against primary sources** (Local Law 90 of 2025 → TLC adopted rule amending 35 RCNY §§ 58-13/59A-12/82-14, effective March 1, 2026, confirmed in the live rulebook Aug 2026): **$100,000 per person** (down from $200,000). The site states it as "currently $100,000" so a future rule change doesn't silently falsify the page.
- Proof of coverage is the **DMV FH-1 certificate**; a lapse triggers ~30-day suspension/revocation exposure for TC ("diamond") plates.
- New-driver journey: TLC driver license → base affiliation letter → insurance binder + FH-1 from a broker → TLC application → DMV TC plates → TLC inspection. Docs a broker asks for: DMV license, TLC license, registration/title/bill of sale, proof of address, base letter, prior-coverage/loss history.
- **ATIC caution:** American Transit is publicly reported insolvent and under DFS oversight (2024–2026). ATIC sells only through ~140 "approved brokers" — validating Blackridge's positioning — but the site says only that Blackridge is a *listed broker* in ATIC's directory and "works with New York's specialist TLC/for-hire markets": no carrier-financial claims, no single-carrier dependence.
- **NY producer-advertising rules** (NY Ins. Law §2122, §1313; unverified-this-session model knowledge — spot-check before launch): the site displays the licensed legal name and broker status (footer), makes no claims about any insurer's financial condition, and never mentions the NY P/C Insurance Security Fund. One open item: confirm with NY DFS that "Blackridge Brokerage, Inc." is a licensed name/DBA — otherwise feature the full legal name even more prominently.

### Audience
Bay Ridge / southwest Brooklyn TLC and black-car drivers (the neighborhood hosts NYC's largest Arab-American community), plus local families for personal auto & home. Phone-first, mobile-first users; many prefer Arabic.

## 2. Goals
1. Make a 12-year-old storefront brokerage look as established online as it is on 4th Avenue.
2. Convert: click-to-call everywhere, a working quote-request form (mailto → agency inbox), directions.
3. Own the niche: a genuinely useful TLC/black-car insurance explainer + document checklist.
4. Serve the neighborhood: full English/Arabic bilingual toggle (RTL-aware).
5. Rank locally: `InsuranceAgency` JSON-LD, OG/meta, semantic HTML, fast single-file static page.

## 3. Sitemap (single page, GitHub-Pages-ready)
1. **Top strip** — phone, address, Mon–Fri, EN/العربية toggle
2. **Nav (floating pill)** — Coverage · TLC & Black Car · Why Us · FAQ · Contact + "Get a Quote" CTA
3. **Hero** — positioning: insurance for the people who keep New York moving; trust chips (Since 2013 · Licensed NY brokerage · Bay Ridge · Nextdoor Neighborhood Favorite)
4. **Coverage bento** — TLC / Black Car & Livery (flagship) · Personal Auto · Homeowners · "…and whatever else you drive or own" card
5. **TLC deep-dive** — what TLC coverage includes, FH-1, document checklist, 4-step "plates to road" timeline
6. **Why Blackridge** — independent broker, storefront service, since 2013, we shop specialist markets
7. **FAQ** — 6–8 real questions (FH-1, lapse, requirements, new drivers, ATIC, personal lines)
8. **Contact / Quote** — form (name, phone, coverage type, message → mailto), map link, hours, fax/email
9. **Footer** — legal name, NY-licensed-broker disclaimer ("brokerage, not an insurer; coverage subject to policy terms"), sameAs links

## 4. Design direction
- **Vibe:** editorial-luxury meets black-car livery: warm paper (#F7F4EE) + deep ink (#0C0F14) alternating bands, amber/gold accent (#D9A13B), hairline borders, film-grain restraint.
- **Type:** Fraunces (display serif) + Plus Jakarta Sans (UI/body); Arabic: IBM Plex Sans Arabic.
- **Motion:** IntersectionObserver fade-ups, custom cubic-bezier, hover physics on CTAs — all transform/opacity only.
- **Assets:** self-contained SVG logo (ridge monogram + wordmark), favicon, 1200×630 OG image.
- **A11y/perf:** semantic landmarks, visible focus, reduced-motion support, no JS dependencies, no trackers.

## 5. Before go-live (client to confirm)
- [ ] Domain (CNAME removed on this branch — previous cuseclouds.com pointer must not serve this site)
- [ ] Confirm office hours (9–5 vs 8–6 conflict)
- [ ] Confirm BlackridgeIns@outlook.com is monitored for quote-form mail
- [ ] Optional: storefront photo, staff photo, Google Business Profile link
- [ ] Licensed-broker license number(s) if the client wants them displayed (NY DFS producer lookup)
