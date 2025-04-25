<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Config;
use HeimrichHannot\EventsBundle\DataContainer\CalendarSubEventsContainer;
use HeimrichHannot\EventsBundle\Model\CalendarSubEventsModel;

if (Config::get('subEventMode') === CalendarSubEventsContainer::SUB_EVENT_MODE_ENTITY)
{
    /* Models */
    $GLOBALS['TL_MODELS']['tl_calendar_sub_events'] = CalendarSubEventsModel::class;

    /* Backend modules */
    $GLOBALS['BE_MOD']['content']['calendar_subevents'] = [
        'tables' => ['tl_calendar_sub_events', 'tl_content'],
    ];

    $GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_sub_events';
}
