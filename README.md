# Pulse Analytics for Joomla

This system plugin adds privacy-first analytics to a Joomla site. Pulse
Analytics sets no cookies and never stores IP addresses. The script the plugin
adds to your pages is about 3 KB.

[Pulse Analytics](https://pulse.ciphera.net) is built by
[Ciphera](https://ciphera.net), a company in Belgium, and hosted in Switzerland.

## Requirements

The plugin requires Joomla 5 or 6 and PHP 8.1 or later. It has no database
tables, Composer dependencies or external libraries. You need a Pulse Analytics
account with a site registered for the same domain. The free plan is enough to
start.

## Install

Go to **System → Install → Extensions** and upload the zip. Then go to
**System → Plugins**, search for *Pulse Analytics* and **enable it**. A freshly
installed plugin is disabled, and "I installed it and nothing happened" is
almost always this.

Open the plugin and set **Domain** to the domain your site is registered under
in Pulse. You can leave it empty: the tracker then uses the browser's own
hostname, which is correct for a site served on one domain.

## Settings

| Setting | Default | What it does |
|---|---|---|
| Domain | empty | The domain this site is registered under in Pulse. Empty means auto-detect. |
| Custom API origin | empty | Route events through your own proxy origin. Enter the origin only, without a path. |

Scroll depth, outbound links, file downloads, and clicks, copies and form submits are each switched on or off in
your site's settings in Pulse (Settings → General). The tag stays the same, so there is nothing to change here.

## Three things worth knowing

**It runs on the site only and does nothing in the administrator.** The plugin
does not subscribe to the event at all outside the site application, rather
than returning early inside the handler, so the administrator pays nothing for
having it installed. Counting your own backend clicks would inflate every
number on the dashboard.

**What lands in your pages is the literal tag:**

```html
<script src="https://js.ciphera.net/script.js" defer data-domain="example.com"></script>
```

The plugin adds no inline JavaScript, so **a strict Content-Security-Policy does
not need a nonce or `unsafe-inline` for this plugin**. The policy only has to
allow `script-src https://js.ciphera.net` and
`connect-src https://pulse-api.ciphera.net`. (Joomla's renderer puts `src`
first; the Pulse docs show `defer` first. Attribute order is not meaningful in
HTML, so it is the same tag.)

**The plugin does not append a cache-busting query to the script URL.** Joomla
adds `?<version>` to assets by default, and doing that to a third-party CDN URL
would make every Joomla site request a distinct URL from ours. The plugin
registers its assets with `version => false` to prevent it, and
`scripts/verify.sh` asserts this against a real install.

## A mistyped domain is dropped instead of injected

If the Domain field holds something that cannot be a hostname (an imported
configuration, a hand-edited value), the plugin omits `data-domain` and lets the
tracker auto-detect, instead of injecting an invalid value into your pages.
That is a working install one attribute short, which is strictly better than a
broken one.

## Licence

The plugin is licensed under **GPL-2.0-or-later**, and that is not a choice we
made.

Every other public Pulse integration (Astro, Docusaurus, Framer, Google Tag
Manager) is Apache-2.0. This plugin is not Apache-2.0, because the Joomla
Extensions Directory requires GPL: *"Extensions must be licensed as GPL in order
to be listed. Additional restrictions may not be placed on top of the GPL."*
Apache-2.0 is compatible with GPLv3 but not GPLv2, so it cannot satisfy that.
The Drupal and TYPO3 integrations are GPL for the same kind of reason. The
divergence is deliberate and unavoidable.

## Trademark

<!-- VERBATIM from tm.joomla.org/disclaimers.html. The only substitution
     permitted is the business name for "[Business name]". Corrected 16-09-2026:
     this previously opened "This product is not affiliated…", which is a
     PARAPHRASE of "[Business name] and this site is not affiliated…", and
     RELEASING.md said in the same breath not to paraphrase it. Do not reword. -->

Ciphera BV and this site is not affiliated with or endorsed by The Joomla!
Project™. Any products and services provided through this site are not
supported or warrantied by The Joomla! Project or Open Source Matters, Inc. Use
of the Joomla!® name, symbol, logo and related trademarks is permitted under a
limited license granted by Open Source Matters, Inc.
