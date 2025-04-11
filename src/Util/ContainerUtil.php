<?php

/*
 * @copyright Heimrich & Hannot GmbH, 2025
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Util;

use Symfony\Component\Yaml\Yaml;

/**
 * Class ContainerUtil.
 *
 * Polyfill from HuH Contao Utils Bundle v2.
 *
 * {@see https://github.com/heimrichhannot/contao-utils-bundle/blob/6a31af05b67fcf42f96a863645d5e50fc0693e64/src/Container/ContainerUtil.php#L234}
 *
 * @deprecated use utils service instead
 */
class ContainerUtil
{
    /**
     * Recursively merges a config.yml with a $extensionConfigs array.
     * Must be static because on Plugin::getExtensionConfig(...) call, the service container has not yet been initialised.
     *
     * @codeCoverageIgnore
     * @deprecated Use ConfigPluginInterface with class_exist instead
     */
    public static function mergeConfigFile(
        string $activeExtensionName,
        string $extensionName,
        array $extensionConfigs,
        string $configFile
    ): array {
        if ($activeExtensionName !== $extensionName || \file_exists($configFile)) {
            return $extensionConfigs;
        }

        $config = Yaml::parseFile($configFile);

        return \array_merge_recursive($extensionConfigs, \is_array($config) ? $config : []);
    }
}
