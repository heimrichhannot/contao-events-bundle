<?php

$dca = &$GLOBALS['TL_DCA']['tl_calendar_sub_events'];

// use the dca of tl_calendar_events as a base
\Contao\Controller::loadDataContainer('tl_calendar_events');
\Contao\System::loadLanguageFile('tl_calendar_events');
$dca                                          = $GLOBALS['TL_DCA']['tl_calendar_events'];
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

$dca['list']['operations']['edit']['href']              = 'do=calendar_subevents&table=tl_content';
$dca['list']['operations']['toggle']['button_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'toggleIcon'];
$dca['list']['operations']['feature']['button_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'iconFeatured'];


$dca['list']['sorting']['child_record_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'listEvents'];


/**
 * Callbacks
 */
// Reset callbacks to core default
$dca['config']['onload_callback'] = [
    ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'checkPermission']
];

$dca['config']['onsubmit_callback'] = [
    ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'adjustTime']
];

/**
 * Fields
 */
$dca['fields']['pid']['foreignKey']             = 'tl_calendar_events.title';
$dca['fields']['alias']['save_callback']        = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'generateAlias']];
$dca['fields']['startTime']['load_callback']    = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'loadTime']];
$dca['fields']['endTime']['load_callback']      = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'loadTime']];
$dca['fields']['endTime']['save_callback']      = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'setEmptyEndTime']];
$dca['fields']['source']['options_callback']    = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'getSourceOptions'];
$dca['fields']['articleId']['options_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'getArticleAlias'];