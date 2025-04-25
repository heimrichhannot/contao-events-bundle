<?php

/*
 * @copyright Heimrich & Hannot GmbH, 2025
 * @license LGPL-3.0-or-later
 */

use HeimrichHannot\EventsBundle\Enum\SubEventMode;
use HeimrichHannot\EventsBundle\Model\CalendarSubEventsModel;

if (SubEventMode::is(SubEventMode::ENTITY))
{
    /* Models */
    $GLOBALS['TL_MODELS']['tl_calendar_sub_events'] = CalendarSubEventsModel::class;

    /* Backend modules */
    $GLOBALS['BE_MOD']['content']['calendar_subevents'] = [
        'tables' => ['tl_calendar_sub_events', 'tl_content'],
    ];

    $GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_sub_events';
}
