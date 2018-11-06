<?php

$dca = &$GLOBALS['TL_DCA']['tl_calendar_sub_events'];

// use the dca of tl_calendar_events as a base
\Contao\Controller::loadDataContainer('tl_calendar_events');
\Contao\System::loadLanguageFile('tl_calendar_events');
$dca = $GLOBALS['TL_DCA']['tl_calendar_events'];
$GLOBALS['TL_LANG']['tl_calendar_sub_events'] = $GLOBALS['TL_LANG']['tl_calendar_events'];

/**
 * Config
 */
$dca['config']['ptable'] = 'tl_calendar_events';

/**
 * List
 */
// there are no sub-sub-events
unset($dca['list']['operations']['subevents']);

$dca['list']['operations']['edit']['href'] = 'do=calendar_subevents&table=tl_content';

/**
 * Callbacks
 */
// Reset callbacks to core default
$dca['config']['onload_callback'] = [
    ['tl_calendar_events', 'checkPermission'],
    ['tl_calendar_events', 'generateFeed']
];

$dca['config']['oncut_callback'] = [
    ['tl_calendar_events', 'scheduleUpdate']
];

$dca['config']['ondelete_callback'] = [
    ['tl_calendar_events', 'scheduleUpdate']
];

$dca['config']['onsubmit_callback'] = [
    ['tl_calendar_events', 'adjustTime'],
    ['tl_calendar_events', 'scheduleUpdate']
];

/**
 * Fields
 */
$dca['fields']['pid']['foreignKey'] = 'tl_calendar_events.title';