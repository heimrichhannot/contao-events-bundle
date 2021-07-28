<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Model;

use Contao\System;
use HeimrichHannot\EventsBundle\Manager\EventsManager;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;

class CalendarEventsModel extends \Contao\CalendarEventsModel
{
    /**
     * Checks a given event has sub events.
     *
     * @deprecated Deprecated since 1.7.0, to be removed in 2.0.
     *             Use EventsManager::hasSubEvents instead.
     */
    public static function hasSubEvents(int $event): bool
    {
        return null !== System::getContainer()->get(EventsManager::class)->getSubEvents($event);
    }

    /**
     * Retrieves the sub events for a given event.
     *
     * @return mixed
     *
     * @deprecated Deprecated since Contao 1.7.0, to be removed in 2.0.
     *             Use EventsManager::getSubEvents instead.
     */
    public static function getSubEvents(int $event, array $options = [])
    {
        return System::getContainer()->get(EventsManager::class)->getSubEvents($event, $options);
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
