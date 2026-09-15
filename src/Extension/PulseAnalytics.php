<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.pulseanalytics
 *
 * @copyright   (C) 2026 Ciphera BV <https://ciphera.net>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\PulseAnalytics\Extension;

use Joomla\CMS\Event\Application\BeforeCompileHeadEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\PulseAnalytics\TagBuilder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Adds the Pulse Analytics tracking tag to every front-end page.
 *
 * @since  1.0.0
 */
final class PulseAnalytics extends CMSPlugin implements SubscriberInterface
{
    /**
     * Subscribe to certain events.
     *
     * 🔑 Returning an empty array outside the site application is how a system
     * plugin stays off the administrator, and it is what Joomla's own
     * plg_system_jooa11y does. It is better than an early return inside the
     * handler: the listener is never registered at all, so the administrator
     * pays nothing.
     *
     * @return  string[]  An array of event mappings.
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        if (!Factory::getApplication()->isClient('site')) {
            return [];
        }

        return ['onBeforeCompileHead' => 'injectTag'];
    }

    /**
     * Register the tracker with the document's asset manager.
     *
     * Why the WebAssetManager rather than a raw string appended to the head:
     * the asset manager is the CSP- and pipeline-aware path, it deduplicates by
     * asset name, and it accepts arbitrary attributes — which is the whole
     * reason the literal Pulse tag is expressible here at all.
     *
     * 🔴 `'version' => false` is LOAD-BEARING, and omitting it is not the same
     * thing. `WebAssetItem::$version` is declared `protected $version = 'auto'`
     * — the default is 'auto', not empty — so an asset registered with no
     * version option gets one, and ScriptsRenderer appends `?<mediaVersion>` to
     * the src.
     *
     * Measured on a real Joomla 6.1.3 install: without this the page shipped
     * `src="https://js.ciphera.net/script.js?9b26ab"`. That is wrong for a
     * third-party CDN asset in a way that would never have been noticed — the
     * tracker still works, because it finds itself with
     * `script[src*="js.ciphera.net/script"]` — while every Joomla site requests
     * a distinct URL from our edge, and the query changes on every Joomla
     * update. Cache fragmentation, silently, per install.
     *
     * @param   BeforeCompileHeadEvent  $event  The event object.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function injectTag(BeforeCompileHeadEvent $event): void
    {
        $assets = $event->getDocument()->getWebAssetManager();

        $assets->registerAndUseScript(
            'pulseanalytics.tracker',
            TagBuilder::SCRIPT_URL,
            TagBuilder::ASSET_OPTIONS,
            TagBuilder::trackerAttributes(
                (string) $this->params->get('domain', ''),
                (string) $this->params->get('api', '')
            )
        );

        if ((int) $this->params->get('companion', 0) !== 1) {
            return;
        }

        $assets->registerAndUseScript(
            'pulseanalytics.companion',
            TagBuilder::COMPANION_URL,
            TagBuilder::ASSET_OPTIONS,
            TagBuilder::companionAttributes(),
            ['pulseanalytics.tracker']
        );
    }
}
