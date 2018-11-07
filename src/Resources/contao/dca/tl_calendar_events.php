<?php

$dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

/**
 * Operations
 */
$dca['list']['operations']['subevents'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['subevents'],
    'href'  => 'table=tl_calendar_sub_events',
    'icon'  => 'bundles/heimrichhannotcontaoevents/img/icon-subevents.png'
];

System::getContainer()->get('huh.utils.array')->insertInArrayByName(
    $dca['list']['operations'],
    'show', [
    'feature' => [
        'label'           => &$GLOBALS['TL_LANG']['tl_calendar_events']['feature'],
        'icon'            => 'featured.svg',
        'attributes'      => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleFeatured(this,%s)"',
        'button_callback' => ['huh.events.event_listener.data_container.calendar_events_listener', 'iconFeatured']
    ]
], 0
);

/**
 * Callbacks
 */
$callbacks = [];

foreach ($dca['config']['onload_callback'] as $callback) {
    if ($callback[1] === 'checkPermission') {
        $callbacks[] = ['huh.events.event_listener.data_container.calendar_events_listener', 'checkPermission'];
        continue;
    }

    $callbacks[] = $callback;
}

$dca['config']['onload_callback'] = $callbacks;

$dca['config']['onsubmit_callback'][] = ['huh.utils.dca', 'setDateAdded'];
$dca['config']['oncopy_callback'][]   = ['huh.utils.dca', 'setDateAddedOnCopy'];

/**
 * Palettes
 */
$dca['palettes']['default'] = str_replace('noComments', 'noComments,featured', $dca['palettes']['default']);

/**
 * Fields
 */
$fields = [
    'dateAdded' => [
        'label'   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
        'sorting' => true,
        'flag'    => 6,
        'eval'    => ['rgxp' => 'datim', 'doNotCopy' => true, 'noSubmissionField' => true],
        'sql'     => "int(10) unsigned NOT NULL default '0'",
    ],
    'featured'  => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['featured'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''"
    ],
];

$dca['fields'] += $fields;

$dca['fields']['startTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
$dca['fields']['endTime']['load_callback']   = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];