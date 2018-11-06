<?php

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_calendar_sub_events'] = 'HeimrichHannot\EventsBundle\Model\CalendarSubEventsModel';

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['calendar_subevents'] = [
    'tables' => ['tl_calendar_sub_events', 'tl_content']
];

/**
 * Assets
 */
if (System::getContainer()->get('huh.utils.container')->isBackend())
{
    $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotcontaoevents/css/contao-events-bundle.min.css|static';
}