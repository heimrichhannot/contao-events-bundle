<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\Hook;

use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\System;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarEventsListener;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener;

class HookListener
{
    public function executePreActions(string $action)
    {
        if ('toggleFeatured' !== $action || 'calendar' !== \Input::get('do')) {
            return;
        }

        if ('tl_calendar_events' === \Input::get('table')) {
            $listener = CalendarEventsListener::class;
        } elseif ('tl_calendar_sub_events' === \Input::get('table')) {
            $listener = CalendarSubEventsListener::class;
        } else {
            return;
        }

        System::getContainer()->get($listener)->toggleFeatured(\Input::post('id'), ((1 == \Input::post('state')) ? true : false));

        throw new NoContentResponseException();
    }
}
