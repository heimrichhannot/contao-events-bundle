<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Model;

use Contao\Config;
use Contao\System;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener;

class CalendarEventsModel extends \Contao\CalendarEventsModel
{
    /**
     * Checks a given event has sub events.
     *
     * @param int $event
     *
     * @return bool
     */
    public static function hasSubEvents(int $event): bool
    {
        return null !== static::getSubEvents($event);
    }

    /**
     * Retrieves the sub events for a given event.
     *
     * @param int $event
     *
     * @return mixed
     */
    public static function getSubEvents(int $event)
    {
        if (CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY === Config::get('subEventMode')) {
            $table = 'tl_calendar_sub_events';
            $parentProperty = 'pid';
        } elseif (CalendarSubEventsListener::SUB_EVENT_MODE_RELATION === Config::get('subEventMode')) {
            $table = 'tl_calendar_events';
            $parentProperty = 'parentEvent';
        } else {
            return null;
        }

        return System::getContainer()->get('huh.utils.model')->findModelInstancesBy(
            $table, [$parentProperty.'=?'], [$event]
        );
    }
}
