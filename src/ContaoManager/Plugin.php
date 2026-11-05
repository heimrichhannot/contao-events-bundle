<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\ContaoManager;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use HeimrichHannot\EventsBundle\HeimrichHannotContaoEventsBundle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements BundlePluginInterface, ConfigPluginInterface, ExtensionPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(HeimrichHannotContaoEventsBundle::class)->setLoadAfter([
                ContaoCoreBundle::class,
                ContaoCalendarBundle::class,
                \HeimrichHannot\UtilsBundle\HeimrichHannotUtilsBundle::class,
            ]),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig)
    {
        $loader->load(__DIR__.'/../Resources/config/services.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        $extensionConfigs = $this->mergeConfigFile(
            'huh_list',
            $extensionName,
            $extensionConfigs,
            __DIR__.'/../Resources/config/config_list.yml'
        );

        $extensionConfigs = $this->mergeConfigFile(
            'huh_reader',
            $extensionName,
            $extensionConfigs,
            __DIR__.'/../Resources/config/config_reader.yml'
        );

        return $extensionConfigs;
    }

    private function mergeConfigFile(string $alias, string $extensionName, array $configs, string $file): array
    {
        if ($alias === $extensionName) {
            $configs = array_merge_recursive($configs, Yaml::parseFile($file));
        }

        return $configs;
    }
}
