# JED listing — Pulse Analytics

The text the Joomla Extensions Directory submission form gets. The form has no
version control; this file does. Keep it in step with the extension.

**Status:** 🟢 **SUBMITTED 16-09-2026 — extension id `17699`, state `Pending`.**
Confirmation: *"Listing Submission Received. Your submission has been
successfully received."* Visible under Profile → Extensions as Pending / Free.

⚠️ `extensions.joomla.org` returns **200 with zero bytes** to curl — it
fingerprints non-browser clients. A real browser loads it fine, so the account
work and the submission were both automated with Playwright; "curl gets nothing"
is a fact about curl, not about whether the site can be driven.

🔑 **The account is gated before it can submit.** The Submit button reads
*"Submit extension - Complete the Account profile"* and is inert until Profile →
**Account** has a **Developer Name**. That is the entire profile — one field. Set
to `Ciphera`, matching `<author>` in the manifest and the drupal.org maintainer.

🔴 **Three file inputs, in DOM order: [0] Extensions File (the install zip),
[1] Logo, [2] Images.** Putting the logo in slot [0] fails with BOTH
*"'Extensions File' is required"* AND *"'Logo' is required"* — one wrong slot,
two errors, neither naming the cause.

⚠️ **A Funding Choices consent overlay (`.fc-consent-root`) intercepts the Save
click** and must be dismissed first.

**Logo:** `pulse-framer/listing/out/joomla-jed.png` — the JED wants **1200×525
(16:7)**, a shape no other store uses. `listing/render.mjs` has a `jed` format
for it.

## Prerequisites, all met as of 16-09-2026

| | State |
|---|---|
| GPL licence | ✅ GPL-2.0-or-later, `LICENSE.txt` verbatim |
| `<updateservers>` — mandatory since 10 Jan 2017 | ✅ `https://cdn.ciphera.net/joomla/pulse-update.xml`, **serving and verified by body** |
| Download URL not behind a login or paywall | ✅ `https://cdn.ciphera.net/joomla/plg_system_pulseanalytics-1.0.0.zip` |
| Verified on supported cores | ✅ Joomla 6.1.3 and 5.4.8, five cases each |
| **JED Checker** | ✅ run against the PUBLISHED zip — see below |
| Name contains no Joomla mark | ✅ so no Extension Name Request ticket — approval is inline in the form |

## Listing fields

**Name:** Pulse Analytics

**Short description (under 200 characters):**

> Privacy-first web analytics. No cookies, no personal data, and the script it adds to your pages is under 3 KB.

**Description:**

Pulse Analytics adds privacy-first web analytics to a Joomla site with one
system plugin. It sets no cookies, stores no personal data, and the script it
adds to your pages is under 3 KB — so it needs no consent banner of its own.

How it works

1. Install the plugin, then go to System → Plugins, find *Pulse Analytics* and
   enable it. A freshly installed plugin is disabled; "I installed it and
   nothing happened" is almost always this.
2. Enter the domain your site is registered under in Pulse, or leave it empty
   and the tracker uses the browser's own hostname — correct for a site served
   on one domain.
3. Publish. Your dashboard at pulse.ciphera.net shows visitors as they arrive.

What it measures

Pageviews, referrers, countries, devices, time on page and scroll depth, plus
the goals, funnels and campaigns you set up, in one dashboard hosted in Europe.
Visitors whose browser sends Do Not Track or Global Privacy Control are not
counted at all. Administration pages are never tagged.

Options

A companion script for clicks, copies and form submits, loaded as a second
request so you only pay for it if you want it; and a custom API origin, if you
route events through your own proxy.

You need

Joomla 5 or 6, PHP 8.1 or later, and a Pulse Analytics account with a site
registered for the same domain. The free plan is enough to start. No database
tables, no Composer dependencies, no external libraries.

**Licence:** GNU General Public License version 2 or later

**Type:** Plugin · **Group:** System

**Links:** Website `https://pulse.ciphera.net` · Documentation
`https://docs.ciphera.net/pulse/framework-guides` · Demo
`https://pulse.ciphera.net/demo` · Support `support@ciphera.net` ·
Source `https://github.com/ciphera-net/pulse-joomla`

**Download:** `https://cdn.ciphera.net/joomla/plg_system_pulseanalytics-1.0.0.zip`

## JED Checker — run, and it found a bug in JED Checker

The submission form's required checkbox bundles three claims, one of which is
*"The extension has been verified with JED Checker"*. It was, against the
**published** artefact fetched from the CDN (sha `ae0924929fc280b5da95…`).

**Result: clean.** 13 of 14 rules pass outright; the 14th failure is not ours.

🔴 **`XmlLicenseRule` in the unreleased 3.0.0 `develop` build reads
`$xml->licence` — British spelling.** Joomla's manifest schema uses
`<license>`, so the rule fails every schema-correct extension, **including JED
Checker's own `jedchecker.xml`**. Released **2.4.4** reads `$xml->license`
correctly, so the spelling flipped in the refactor. Proven three ways: Joomla
core's manifests all use `<license>`; adding a `<licence>` element takes the run
to **0 errors of 14**; and 2.4.4's exact conditions applied to our manifest
return TRUE on both checks. Reported upstream as
[jedchecker#278](https://github.com/joomla-extensions/jedchecker/issues/278).

⚠️ **The JED's own submission confirmation says reviewers use JED Checker.** If
they run `develop`, expect this false positive and point them at the issue.

### 🔴 How to run it, because the admin UI does not work in a local harness

Three false greens came before a real result, each caught only by a **control** —
the same package with its `<license>` tag deleted, which MUST fail:

1. All 14 ✓ — but the upload never landed; those ticks are the page's default
   state. The control showed 14/14 ✓ too, which is how the lie was caught.
2. The built package 404s its own JS (`jedchecker.js` requested, `script.js`
   shipped), so the upload button is never wired.
3. Under `php -S` every JS module fails (`MIME type text/html`) and the form
   action doubles to `/administrator/administrator/…`. The admin UI is unusable.

So bypass it and drive the rule classes directly. `cli/pulse-jedcheck.php` in the
harness does this: `RuleDiscovery::getRules()` → `new $rule($folder)` →
`check()` → `getReport()->getData()`.

⚠️ `getData()` returns `['count' => …, 'issues' => …]` — **two keys**. Counting
the top-level array gives every rule `items=2` and no failures ever. Read
`$data['count']['error']`.

🔑 **Never trust a checker run that has not been shown able to fail.**

## 🔴 The disclaimer is verbatim and non-negotiable

From `tm.joomla.org/disclaimers.html`. Required in the site's own language **and**
in English, at **8pt sans-serif or larger** with sufficient contrast, on any
listing page using the Joomla name or logo. The only substitution permitted is
the business name for `[Business name]`:

> Ciphera BV and this site is not affiliated with or endorsed by The Joomla!
> Project™. Any products and services provided through this site are not
> supported or warrantied by The Joomla! Project or Open Source Matters, Inc.
> Use of the Joomla!® name, symbol, logo and related trademarks is permitted
> under a limited license granted by Open Source Matters, Inc.

⚠️ **Do not reword it.** `README.md` carried a paraphrase until 16-09-2026 —
it opened *"This product is not affiliated…"* — while `RELEASING.md` said in the
same breath not to paraphrase. Both are corrected.

## 🔴 No Joomla logo on the listing card

Joomla's Conditional Use Logos may not be used *"as a trademark to promote your
own products or services"*. The extension carve-out re-opens it only if **our**
name and logo are *"always larger and more prominent"* — subordinate, never
co-equal. The card is therefore `svg: null` in
`pulse-framer/listing/platforms.mjs`, the Pulse mark alone with "for Joomla" in
text, like every other platform in this queue.

## After submission

- **No published SLA.** Review is by volunteers.
- 🔴 **60 days** from submission to correct anything flagged, or the listing is
  rejected — and a correction re-queues you for another pass, also untimed.
