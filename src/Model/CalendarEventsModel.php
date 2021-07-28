<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Model;

use Contao\Config;
use Contao\System;
use HeimrichHannot\EventsBundle\Event\BeforeGetSubEventsEvent;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CalendarEventsModel extends \Contao\CalendarEventsModel
{
    /**
     * Checks a given event has sub events.
     */
    public static function hasSubEvents(int $event): bool
    {
        return null !== static::getSubEvents($event);
    }

    /**
     * Retrieves the sub events for a given event.
     *
     * @return mixed
     */
    public static function getSubEvents(int $eventId, array $options = [])
    {
        if (CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY === Config::get('subEventMode')) {
            $table = 'tl_calendar_sub_events';
            $parentProperty = 'tl_calendar_events.pid';
        } elseif (CalendarSubEventsListener::SUB_EVENT_MODE_RELATION === Config::get('subEventMode')) {
            $table = 'tl_calendar_events';
            $parentProperty = 'tl_calendar_events.parentEvent';
        } else {
            return null;
        }

        $eventDispatcher = System::getContainer()->get(EventDispatcher::class);

        /** @var BeforeGetSubEventsEvent $event */
        $event = $eventDispatcher->dispatch(BeforeGetSubEventsEvent::NAME, new BeforeGetSubEventsEvent([$parentProperty.'=?'], [$eventId], $options));

        return System::getContainer()->get(ModelUtil::class)->findModelInstancesBy(
            $table, $event->getColumns(), $event->getValues(), $event->getOptions()
        );
    }

    public static function findPublishedByIdOrAlias($varId, array $options = [])
    {
        $t = static::$strTable;

        $columns = !is_numeric($varId) ? ["$t.alias=?"] : ["$t.id=?"];

        if (!static::isPreviewMode($options)) {
            $time = \Date::floorToMinute();
            $columns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'".($time + 60)."') AND $t.published='1'";
        }

        return System::getContainer()->get(ModelUtil::class)->callModelMethod('tl_calendar_events', 'findOneBy', $columns, $varId, $options);
    }
}
