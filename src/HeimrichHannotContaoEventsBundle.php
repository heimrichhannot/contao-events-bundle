<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle;

use HeimrichHannot\EventsBundle\DependencyInjection\EventsExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoEventsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new EventsExtension();
    }
}
