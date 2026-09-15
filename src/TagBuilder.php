<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.pulseanalytics
 *
 * @copyright   (C) 2026 Ciphera BV <https://ciphera.net>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\PulseAnalytics;

\defined('_JEXEC') or die;

/**
 * Decides what the Pulse tag says.
 *
 * Pure logic: no Joomla services, no application, no document. Everything comes
 * in as arguments and plain arrays come out, so the part that decides what the
 * tag says is testable without booting Joomla, and can be read side by side with
 * the same decisions in the Drupal, Astro, Framer and Docusaurus integrations.
 *
 * The URLs, the domain grammar and the rule that the companion script is a
 * SECOND request are copied from those rather than re-derived. Two Pulse install
 * surfaces disagreeing about what the tag looks like is a drift bug waiting to
 * happen.
 *
 * @since  1.0.0
 */
final class TagBuilder
{
    /**
     * The Pulse tracker.
     *
     * @since  1.0.0
     */
    public const SCRIPT_URL = 'https://js.ciphera.net/script.js';

    /**
     * The companion script: clicks, copies and form submits.
     *
     * @since  1.0.0
     */
    public const COMPANION_URL = 'https://js.ciphera.net/script.interactions.js';

    /**
     * Options for every asset this plugin registers.
     *
     * 🔴 `version => false` is the whole point of this constant, and it is not
     * the same as passing no options at all. `WebAssetItem::$version` is
     * declared as `protected $version = 'auto'`, so an asset registered without
     * a version option still HAS one, and Joomla appends `?<mediaVersion>` to
     * the script src. On a third-party CDN URL that means every Joomla site
     * requests a distinct URL from our edge, changing on every Joomla update —
     * measured live as `script.js?9b26ab` before this was added.
     *
     * @since  1.0.0
     */
    public const ASSET_OPTIONS = ['version' => false];

    /**
     * A registrable hostname.
     *
     * Labels of letters, digits and hyphens, at least one dot, a letter-only
     * TLD. Deliberately strict — the value lands inside an HTML attribute, and
     * this shape cannot carry a quote, a space or a bracket. Joomla's
     * ScriptsRenderer runs every attribute value through htmlspecialchars(), so
     * this is the second of two guards rather than the only one; it is kept
     * because a domain that cannot be a hostname is a typo, and a typo is an
     * install that reports nothing and looks fine.
     *
     * @since  1.0.0
     */
    public const DOMAIN_PATTERN = '/^(?=.{1,253}$)(?!-)[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*\.[a-z]{2,63}$/';

    /**
     * Lower-case a domain and strip a scheme, path, port and trailing dot.
     *
     * Keeps "www." — the Pulse site may be registered either way and the site
     * owner can edit it.
     *
     * @param   string  $input  Whatever the site owner typed.
     *
     * @return  string  The normalised domain.
     *
     * @since   1.0.0
     */
    public static function normalizeDomain(string $input): string
    {
        $value = mb_strtolower(trim($input));
        $value = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $value);
        $value = preg_replace('#[/?\#].*$#', '', $value);
        $value = preg_replace('/:.*$/', '', $value);

        return rtrim($value, '.');
    }

    /**
     * Whether a normalised domain is a registrable hostname.
     *
     * @param   string  $domain  A domain, already through normalizeDomain().
     *
     * @return  boolean  True when it is safe to put in the tag.
     *
     * @since   1.0.0
     */
    public static function isValidDomain(string $domain): bool
    {
        return (bool) preg_match(self::DOMAIN_PATTERN, $domain);
    }

    /**
     * The attributes for the core tracker tag.
     *
     * 🔑 `defer` is a PHP boolean true, never the string 'defer'. Measured in
     * Joomla's own ScriptsRenderer::renderAttributes(): a `true` value renders
     * as a bare attribute on HTML5, a `false` value is dropped entirely, and
     * every other value is htmlspecialchars()'d into `name="value"`.
     *
     * ⚠️ The same rule is a trap in the other direction — `$value === true`
     * makes ANY attribute no-value, so a boolean sneaking into `data-domain`
     * would render a bare `data-domain` and silently lose the domain. That is
     * why this method only ever puts strings there.
     *
     * @param   string  $domain  The domain, or '' to let the tracker auto-detect.
     * @param   string  $api     A custom API origin, or '' for the default.
     *
     * @return  array  Attributes for WebAssetManager::registerAndUseScript().
     *
     * @since   1.0.0
     */
    public static function trackerAttributes(string $domain = '', string $api = ''): array
    {
        $attributes = ['defer' => true];

        $domain = self::normalizeDomain($domain);

        if ($domain !== '' && self::isValidDomain($domain)) {
            $attributes['data-domain'] = $domain;
        }

        $api = rtrim(trim($api), '/');

        if ($api !== '') {
            $attributes['data-api'] = $api;
        }

        return $attributes;
    }

    /**
     * The attributes for the companion tag.
     *
     * No data-domain: the tracker resolves the domain once, from the core
     * script, and a second copy is a second thing to keep in step.
     *
     * @return  array  Attributes for WebAssetManager::registerAndUseScript().
     *
     * @since   1.0.0
     */
    public static function companionAttributes(): array
    {
        return ['defer' => true];
    }
}
