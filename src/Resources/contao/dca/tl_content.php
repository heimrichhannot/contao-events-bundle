<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Input;
use HeimrichHannot\EventsBundle\DataContainer\ContentContainer;
use HeimrichHannot\EventsBundle\DataContainer\CalendarSubEventsContainer;

if (Input::get('do') === 'calendar_subevents')
{
    $dca = &$GLOBALS['TL_DCA']['tl_content'];

    $dca['config']['ptable'] = 'tl_calendar_sub_events';
    $dca['config']['onload_callback'][] = [CalendarSubEventsContainer::class, 'checkPermission'];
    $dca['list']['operations']['toggle']['button_callback'] = [ContentContainer::class, 'toggleIcon'];
}
