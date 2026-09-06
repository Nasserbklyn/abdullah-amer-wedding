# Blackridge Brokerage, Inc. — Insurance Agency Website

Website for **Blackridge Brokerage, Inc.** (legal name **Blackridge Insurance
Brokerage Inc.**, NY DOS ID 4505581, filed Dec 26, 2013), an independent
insurance brokerage at **9128 4th Avenue, Brooklyn, NY 11209** (Bay Ridge) —
**(718) 836-0500** · fax (718) 836-2020 · BlackridgeIns@outlook.com.

The agency specializes in **TLC / black car & livery insurance** (it is a
listed broker in American Transit Insurance Company's broker directory) plus
**personal auto** and **homeowners** coverage. Built as a single static page
(`index.html`) so it can be hosted on GitHub Pages.

The research behind the content and the full build plan are in
[`PLAN.md`](PLAN.md).

## Features

| Feature | Where |
| --- | --- |
| Click-to-call conversion | Top strip, hero, coverage cards, contact card, mobile menu, footer |
| Quote-request form (no backend) | `#quote` — composes a `mailto:` to BlackridgeIns@outlook.com |
| TLC driver resource | `#tlc` — required coverages (incl. PIP "currently $100,000" per the TLC rule effective 3/1/2026), FH-1 explainer, document checklist, 4-step timeline |
| Local SEO | `InsuranceAgency` JSON-LD (address, geo, hours Mo–Fr 08:00–18:00, sameAs), OG tags, semantic HTML |
| Accessibility | Skip link, focus styles, `prefers-reduced-motion` support, content fully visible without JavaScript (`html.js` gating) |

## NY compliance guardrails baked into the copy

- Licensed **legal name and broker status** displayed (footer + JSON-LD); the
  page states plainly that Blackridge is a brokerage, **not an insurer**
  (NY Ins. Law §2122 advertising practice).
- **No insurer-financial claims** anywhere — American Transit is referenced
  only as "listed broker in ATIC's broker directory / one of the specialist
  markets we work with" (Ins. Law §1313; ATIC's financial condition has been
  the subject of public DFS action since 2024).
- No mention of the NY P/C Insurance **Security Fund** (Ins. Law §7718).
- Coverage descriptions are framed as summaries subject to policy terms.

## ⚠️ Before going live — required

Search `index.html` for `TODO` and resolve:

1. **Domain** — set the canonical URL + `og:url`, make `og:image` absolute,
   and add a `CNAME` file (the previous repo content's CNAME pointed at an
   unrelated business and was removed). The agency's old WordPress subdomain
   suggests **blackridgeins.com** was the intended domain; it appears
   unregistered.
2. **Confirm the quote-form inbox** — BlackridgeIns@outlook.com must be
   monitored.
3. **Verify with the client / NY DFS** that "Blackridge Brokerage, Inc." is a
   licensed name or registered DBA; optionally display the DFS producer
   license number.
4. Confirm hours still hold (Mon–Fri 8–6 per the agency's own legacy site and
   Waze).
5. Verify the Nextdoor "Neighborhood Favorite" badge is current before keeping
   that chip, and confirm the ATIC broker listing with the client.

The full sourced research dossier (180 facts, incl. an owner-interview
checklist of unknowns) is in [`RESEARCH.md`](RESEARCH.md).

## OpenDesign (design tooling for coding agents)

[OpenDesign](https://github.com/nexu-io/open-design) is wired into this repo
as a project-scoped MCP server via [`.mcp.json`](.mcp.json). When Claude Code
(or any MCP-aware agent that reads `.mcp.json`) opens this folder, it can call
OpenDesign's 139 design skills and 150 `DESIGN.md` design systems, read/write
OpenDesign projects, and drive the live preview.

OpenDesign itself runs as a local daemon (`od`) on your machine; the config in
this repo only points the agent at it. One-time setup on your computer:

1. Install the desktop app (macOS / Windows) from
   <https://open-design.ai/> — it bundles the `od` CLI and daemon — **or** run
   the daemon from source / Docker:

   ```bash
   git clone https://github.com/nexu-io/open-design.git
   cd open-design
   bash deploy/scripts/install.sh        # Docker, listens on http://127.0.0.1:7456
   # — or, from source (Node 24 + pnpm 10.33 via `corepack enable`) —
   pnpm install && pnpm tools-dev start web
   ```

2. Make sure `od` on your `PATH` is OpenDesign's CLI, not the system octal-dump
   tool (`/usr/bin/od` on macOS / Linux). `od --help` should mention "Start the
   local daemon". If it doesn't, replace `"command": "od"` in `.mcp.json` with
   the absolute path shown under **Settings → MCP server** in the app.

3. Open the repo in Claude Code and approve the `open-design` project MCP
   server when prompted. Alternatively install it user-wide with
   `od mcp install claude`, or as a plugin with
   `/plugin marketplace add nexu-io/open-design` then
   `/plugin install open-design@open-design`.

Then, inside the agent: *"Use open-design to redesign the hero section with
the Linear design system."*

## Assets

- `assets/blackridge-mark.svg` — roundel logo (gold ridge line + dashed road
  on ink), self-contained vector.
- `assets/favicon.svg` — simplified mark for browser tabs.
- `assets/og-image.png` — 1200×630 social-share image (referenced by OG tags).
