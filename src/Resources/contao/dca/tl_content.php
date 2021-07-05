<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

if ('calendar_subevents' == Input::get('do')) {
    $dca = &$GLOBALS['TL_DCA']['tl_content'];

    $dca['config']['ptable'] = 'tl_calendar_sub_events';
    $dca['config']['onload_callback'][] = [\HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener::class, 'checkPermission'];
    $dca['list']['operations']['toggle']['button_callback'] = [\HeimrichHannot\EventsBundle\EventListener\DataContainer\ContentListener::class, 'toggleIcon'];
}
