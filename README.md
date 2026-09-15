# Pulse Analytics for Joomla

Privacy-first analytics for a Joomla site, in one system plugin. No cookies, no
personal data, and the script it adds to your pages is under 3 KB.

[Pulse Analytics](https://pulse.ciphera.net) is built by
[Ciphera](https://ciphera.net), a company in Belgium, and hosted in Europe.

## Requirements

Joomla 5 or 6, PHP 8.1 or later. No database tables, no Composer dependencies,
no external libraries. You need a Pulse Analytics account with a site registered
for the same domain; the free plan is enough to start.

## Install

**System → Install → Extensions**, upload the zip, then **System → Plugins**,
search for *Pulse Analytics* and **enable it** — a freshly installed plugin is
disabled, and "I installed it and nothing happened" is almost always this.

Open the plugin and set **Domain** to the domain your site is registered under
in Pulse. You can leave it empty: the tracker then uses the browser's own
hostname, which is correct for a site served on one domain.

## Settings

| Setting | Default | What it does |
|---|---|---|
| Domain | empty | The domain this site is registered under in Pulse. Empty means auto-detect. |
| Record clicks, copies and form submits | off | Loads the companion script. A second, separate request — see below. |
| Custom API origin | empty | Route events through your own proxy origin. A bare origin, not a full URL. |

## Four things worth knowing

**It runs on the site only, never in the administrator.** Not by an early return
inside the handler — the plugin does not subscribe to the event at all outside
the site application, so the administrator pays nothing for having it installed.
Counting your own backend clicks would inflate every number on the dashboard.

**What lands in your pages is the literal tag:**

```html
<script src="https://js.ciphera.net/script.js" defer data-domain="example.com"></script>
```

No inline JavaScript, so **a strict Content-Security-Policy needs no nonce and
no `unsafe-inline` for this plugin** — only `script-src https://js.ciphera.net`
and `connect-src https://pulse-api.ciphera.net`. (Joomla's renderer puts `src`
first; the Pulse docs show `defer` first. Attribute order is not meaningful in
HTML — it is the same tag.)

**No cache-busting query is appended to the script URL.** Joomla adds
`?<version>` to assets by default, and doing that to a third-party CDN URL would
make every Joomla site request a distinct URL from ours. The plugin registers
its assets with `version => false` specifically to prevent it, and that is
asserted by `scripts/verify.sh` against a real install.

**The interaction capture is a second request on purpose.** The core script's
size is a published claim, and nothing is folded into it to make a feature look
free.

## A mistyped domain is dropped, never injected

If the Domain field holds something that cannot be a hostname — an imported
configuration, a hand-edited value — the plugin omits `data-domain` and lets the
tracker auto-detect, rather than injecting rubbish into your pages. That is a
working install one attribute short, which is strictly better than a broken one.

## Licence

**GPL-2.0-or-later**, and that is not a choice we made.

Every other public Pulse integration — Astro, Docusaurus, Framer, Google Tag
Manager — is Apache-2.0. This plugin is not, because the Joomla Extensions
Directory requires it: *"Extensions must be licensed as GPL in order to be
listed. Additional restrictions may not be placed on top of the GPL."*
Apache-2.0 is compatible with GPLv3 but not GPLv2, so it cannot satisfy that.
The Drupal and TYPO3 integrations are GPL for the same kind of reason. The
divergence is deliberate and unavoidable, not drift.

## Trademark

This product is not affiliated with or endorsed by The Joomla! Project™. Any
products and services provided through this site are not supported or
warrantied by The Joomla! Project or Open Source Matters, Inc. Use of the
Joomla!® name, symbol, logo and related trademarks is permitted under a limited
license granted by Open Source Matters, Inc.
