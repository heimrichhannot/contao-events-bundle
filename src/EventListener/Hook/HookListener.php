<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\Hook;

use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class HookListener implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    public function executePreActions(string $action)
    {
        if ('toggleFeatured' !== $action || 'calendar' !== \Input::get('do')) {
            return;
        }

        if ('tl_calendar_events' === \Input::get('table')) {
            $listener = 'huh.events.event_listener.data_container.calendar_events_listener';
        } elseif ('tl_calendar_sub_events' === \Input::get('table')) {
            $listener = 'huh.events.event_listener.data_container.calendar_sub_events_listener';
        } else {
            return;
        }

        $this->container->get($listener)->toggleFeatured(\Input::post('id'), ((1 == \Input::post('state')) ? true : false));

        throw new NoContentResponseException();
    }
}
