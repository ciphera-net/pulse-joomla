# Releasing plg_system_pulseanalytics

## Verified against real installs, 15-09-2026

`./scripts/verify.sh <version>` downloads Joomla, installs it against a real
database, deploys this plugin, enables it, and reads the tag back out of the
served HTML. Five cases, both supported branches:

| Joomla | Result |
|---|---|
| **6.1.3** (current stable) | ✅ all five |
| **5.4.8** (older stable line) | ✅ all five |

The cases: the tag with a domain and **no `?version` on the CDN url**; domain +
API origin + companion, with the companion carrying no domain of its own; no
domain configured meaning auto-detect rather than an empty attribute; an invalid
domain dropped rather than injected; and **zero** references to `ciphera` in the
administrator.

Joomla core's own `php-cs-fixer` rule set — taken verbatim from
`joomla-cms/.php-cs-fixer.dist.php` with only the finder repointed — reports
**0 of 3 files** needing changes.

```bash
./scripts/verify.sh 6.1.3
HTTP_PORT=18081 ./scripts/verify.sh 5.4.8
```

Needs `php`, a MariaDB/MySQL with a passwordless root (default
`127.0.0.1:13306`, override with `DB_HOST`/`DB_PORT`/`DB_USER`) and `curl`.

## 🔴 The defect the live install caught, which reading the source did not

The first run shipped `src="https://js.ciphera.net/script.js?9b26ab"`.

`WebAssetItem` declares **`protected $version = 'auto';`** — the *default* is
`'auto'`, not empty — so an asset registered with no version option still has
one, and `ScriptsRenderer` appends `?<mediaVersion>` to the src. Reading the
constructor is not enough; the property declaration is where it lives.

Why it mattered, and why it would never have been reported: the tracker still
worked, because it finds itself with `script[src*="js.ciphera.net/script"]` and
a query does not break that substring. Meanwhile every Joomla site on earth
would have requested a **distinct** URL from our edge, changing on every Joomla
core update — cache fragmentation, per install, silently.

The fix is `TagBuilder::ASSET_OPTIONS = ['version' => false]`, and the first
assertion in `verify.sh` is that no `script.js?` appears. **Do not relax it.**

🔑 The same session found the same shape twice more: a Docusaurus HTML minifier
re-serialising `defer` into `defer="defer"`, and a Drupal renderer leaving it
bare. **Three platforms, one intent, three different emitted byte-strings.**
Only a rendered page shows which you got.

## Two corrections to what was previously recorded about Joomla

- ⚠️ **An "Extension Name Request" ticket is NOT required for this extension.**
  Earlier notes said a separate trademark ticket was needed for the name. That
  applies only when the extension's *name contains the word "Joomla"* — JED
  naming rules forbid a name *starting* with "Joomla" and require an Open Source
  Matters licence for the word otherwise. **"Pulse Analytics" contains no
  Joomla mark**, so name approval happens inline in the submission form,
  first-come-first-served.
- ⚠️ **The "7 MB upload cap" is unverified.** No JED page states a numeric
  package size limit. Treat it as unknown rather than as a constraint.

## JED submission

1. **Register on `extensions.joomla.org`** and confirm the email, then use
   *Submit extension* from your profile.
2. 🔴 **`<updateservers>` is mandatory** for every extension submitted since
   **10 January 2017**. The manifest points at
   `https://pulse.ciphera.net/joomla/pulse-update.xml`, and **that file must
   actually be serving before submitting** — `pulse-update.xml` in this
   repository is it. It is a static file with no backend, but until it is
   deployed the manifest names a URL that 404s, every installed site reports
   "no updates available" forever, and a reviewer fails the listing on it.
   ⚠️ **`targetplatform` lives in that file, not in the install manifest.** The
   install manifest has no compatibility gate at all — Joomla will install this
   on any version. The regex in `pulse-update.xml` is the only thing that stops
   an update being offered to a core we have not tested. Widen it only after
   running `verify.sh` against the new version.
3. **The download link must not sit behind a login or a paywall**, and a forum
   or shared-hosting link is explicitly disallowed. Give it a landing page.
4. **Review is by volunteers with no published SLA.** One published rule: a
   listing has **60 days** from submission to correct flagged errors before it
   is rejected, and a correction re-queues you for another pass — also with no
   fixed turnaround.

### The disclaimer is verbatim and non-negotiable

Joomla's trademark policy requires this exact text on a listing page that uses
the Joomla name or logo, in the site's own language *and* in English, at 8pt
sans-serif or larger with sufficient contrast:

> "[Business name] and this site is not affiliated with or endorsed by The
> Joomla! Project™. Any products and services provided through this site are not
> supported or warrantied by The Joomla! Project or Open Source Matters, Inc.
> Use of the Joomla!® name, symbol, logo and related trademarks is permitted
> under a limited license granted by Open Source Matters, Inc."

It is already in `README.md`. Do not paraphrase it.

🔑 **No Joomla logo on the Pulse listing card.** Joomla's Conditional Use Logos
may not be used "as a trademark to promote your own products or services"; the
extension carve-out re-opens it only if **our** name and logo are "always larger
and more prominent" — subordinate, never co-equal. The card is `svg: null` in
`pulse-framer/listing/platforms.mjs`, like every other platform in this queue.

## Licence — forced, not chosen

**GPL-2.0-or-later.** The JED states: *"Extensions must be licensed as GPL in
order to be listed. Additional restrictions may not be placed on top of the
GPL."* JED guidance notes GPLv3 is "preferable", but v2-or-later is accepted and
is what Joomla core itself declares in every manifest — so this matches core
rather than diverging from it, and it matches the Drupal and TYPO3 integrations.

Apache-2.0 is GPLv3-compatible but **not** GPLv2-compatible, so it cannot be
used here. `LICENSE.txt` is the GPL-2.0 text verbatim.

## Building the package

The installable zip is the repository contents with the manifest at the root:

```bash
zip -r plg_system_pulseanalytics-1.0.0.zip \
  pulseanalytics.xml services src language LICENSE.txt \
  -x '*.DS_Store'
```

⚠️ **Every file must be named in the manifest's `<files>` block.** The installer
silently drops anything not listed even when it is physically in the zip — a
forgotten `<folder>src</folder>` means the PHP is never copied and nothing
errors visibly.

⚠️ **Four names must match exactly** or the DI container never wires the plugin
up, with no error anywhere: the manifest filename (`pulseanalytics.xml`), the
`plugin="pulseanalytics"` attribute on the `services` folder, the `element`
Joomla stores in `#__extensions`, and the string passed to
`PluginHelper::getPlugin('system', 'pulseanalytics')` in `services/provider.php`.

## Release steps

1. Bump `<version>` in `pulseanalytics.xml`.
2. Run `./scripts/verify.sh 6.1.3` and `HTTP_PORT=18081 ./scripts/verify.sh 5.4.8`.
3. Build the zip (above).
4. Upload the zip somewhere public and update `pulse-update.xml` — its
   `<version>` and `<downloadurl>` — then deploy that file to
   `https://pulse.ciphera.net/joomla/pulse-update.xml`.
5. Tag: `git tag -a v1.0.0 -m "Release 1.0.0" && git push origin v1.0.0`.
6. First release only: submit to the JED.

## Notes for the next person

- **`$this->params->get('x', 'default')` does not do what you expect.**
  `Registry::get()` only applies the default when the key is *absent*. Once an
  administrator has saved the plugin with an empty field, the empty string is
  what comes back — the manifest's `default` is never re-applied. Treat empty
  and unset as the same thing in code, which `TagBuilder` does.
- **`#__extensions.custom_data` has no database default.** Inserting a row
  without it is an error, not a NULL. `verify.sh` carries it.
- **Registering assets later than `onBeforeCompileHead` is too late** — the head
  is already compiled. `onAfterRender` does not work.
- **PHP attributes are not used for plugin registration** in any current Joomla.
  Registration is `SubscriberInterface::getSubscribedEvents()` plus
  `services/provider.php`, full stop.
- **`defer => true` renders as a bare `defer`.** Documentation-derived advice to
  pass the string `'defer'` is unnecessary; measured in
  `ScriptsRenderer::renderAttributes()`, a `true` value renders as a bare
  attribute on HTML5, `false` is dropped entirely, and anything else is
  `htmlspecialchars()`'d. ⚠️ The trap is the other direction: `$value === true`
  makes **any** attribute no-value, so a boolean in `data-domain` would render a
  bare `data-domain` and silently lose the domain.
