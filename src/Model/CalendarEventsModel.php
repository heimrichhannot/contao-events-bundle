<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Model;

use Contao\CalendarEventsModel as ContaoCalendarEventsModel;
use Contao\System;
use HeimrichHannot\EventsBundle\Manager\EventsManager;

class CalendarEventsModel extends ContaoCalendarEventsModel
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

    public static function findPublishedByIdOrAlias(string|int $id_or_alias, array $options = []): ?static
    {
        $t = static::getTable();

        $id_or_alias = \is_numeric($id_or_alias) ? (int) $id_or_alias : (string) $id_or_alias;
        $columns = \is_int($id_or_alias) ? ["$t.id=?"] : ["$t.alias=?"];

        if (!static::isPreviewMode($options))
        {
            $time = \Date::floorToMinute();
            $columns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'".($time + 60)."') AND $t.published='1'";
        }

        return static::findOneBy($columns, $id_or_alias, $options);
    }
}
