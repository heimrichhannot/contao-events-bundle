<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\DataContainer;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Date;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class CalenderEventsListener implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    /**
     * Set the timestamp to 1970-01-01 (see #26).
     *
     * @param int $value
     *
     * @return int
     */
    public function loadTime($value)
    {
        if ($this->container->get('huh.utils.container')->isFrontend()) {
            return $value;
        }

        return strtotime('1970-01-01 '.date('H:i:s', $value));
    }
}
