<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotEventsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     * @return class-string<ExtensionInterface>
     */
    public function getContainerExtensionClass(): string
    {
        return DependencyInjection\HeimrichHannotEventsExtension::class;
    }
}