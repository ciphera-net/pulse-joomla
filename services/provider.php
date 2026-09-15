<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.pulseanalytics
 *
 * @copyright   (C) 2026 Ciphera BV <https://ciphera.net>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\PulseAnalytics\Extension\PulseAnalytics;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * The shape is copied from Joomla's own plg_system_jooa11y rather than from
     * documentation, because that plugin ships with core and is therefore an
     * artefact Joomla itself accepts. Nothing derives the class name from the
     * plugin element, so the namespace only has to match the manifest's
     * <namespace path="src"> entry.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(PulseAnalytics::class, function (Container $container) {
                $plugin = new PulseAnalytics(
                    (array) PluginHelper::getPlugin('system', 'pulseanalytics')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDispatcher($container->get(DispatcherInterface::class));

                return $plugin;
            })
        );
    }
};
