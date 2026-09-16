# JED listing — Pulse Analytics

The text the Joomla Extensions Directory submission form gets. The form has no
version control; this file does. Keep it in step with the extension.

**Status:** not yet submitted. `extensions.joomla.org` needs an account and
returns **200 with zero bytes** to a non-browser client, so the submission is a
browser job.

## Prerequisites, all met as of 16-09-2026

| | State |
|---|---|
| GPL licence | ✅ GPL-2.0-or-later, `LICENSE.txt` verbatim |
| `<updateservers>` — mandatory since 10 Jan 2017 | ✅ `https://cdn.ciphera.net/joomla/pulse-update.xml`, **serving and verified by body** |
| Download URL not behind a login or paywall | ✅ `https://cdn.ciphera.net/joomla/plg_system_pulseanalytics-1.0.0.zip` |
| Verified on supported cores | ✅ Joomla 6.1.3 and 5.4.8, five cases each |
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
