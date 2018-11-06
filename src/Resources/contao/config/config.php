<?php

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_calendar_sub_events'] = 'HeimrichHannot\EventsBundle\Model\CalendarSubEventsModel';

/**
 *
 */
$GLOBALS['BE_MOD']['content']['calendar_subevents'] = array
(
    'tables'      => array('tl_calendar_sub_events', 'tl_content'),
    'table'       => array('TableWizard', 'importTable'),
    'list'        => array('ListWizard', 'importList')
);