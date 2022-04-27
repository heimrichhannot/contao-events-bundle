<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\Util\PackageUtil;

$dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

System::getContainer()->get(\HeimrichHannot\EventsBundle\Manager\EventsManager::class)->initCalendarEventsDcaForSubEvents();

if (!class_exists(PackageUtil::class) || version_compare(PackageUtil::getContaoVersion(), '4.10', '<')) {
    System::getContainer()->get(\HeimrichHannot\UtilsBundle\Arrays\ArrayUtil::class)->insertInArrayByName(
        $dca['list']['operations'],
        'show', [
        'feature' => [
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['feature'],
            'icon' => 'featured.svg',
            'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleFeatured(this,%s)"',
            'button_callback' => [\HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarEventsListener::class, 'iconFeatured'],
        ],
    ], 0
    );
}

/*
 * Callbacks
 */
$dca['config']['onload_callback'][] = [\HeimrichHannot\EventsBundle\DataContainer\CalendarEventsContainer::class, 'modifyPalette'];
$dca['config']['onsubmit_callback'][] = ['huh.utils.dca', 'setDateAdded'];
$dca['config']['oncopy_callback'][] = ['huh.utils.dca', 'setDateAddedOnCopy'];

/**
 * Fields.
 */
$fields = [
    'dateAdded' => [
        'label' => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
        'sorting' => true,
        'flag' => 6,
        'eval' => ['rgxp' => 'datim', 'doNotCopy' => true, 'noSubmissionField' => true],
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ],
    'subTitle' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['subTitle'],
        'exclude' => true,
        'search' => true,
        'inputType' => 'text',
        'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
    'featured' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['featured'],
        'exclude' => true,
        'filter' => true,
        'inputType' => 'checkbox',
        'eval' => ['tl_class' => 'w50 m12'],
        'sql' => "char(1) NOT NULL default ''",
    ],
    'shortTitle' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['shortTitle'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
    'locationAdditional' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['locationAdditional'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
    'street' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['street'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(128) NOT NULL default ''",
    ],
    'postal' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['postal'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(64) NOT NULL default ''",
    ],
    'city' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['city'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(128) NOT NULL default ''",
    ],
    'coordinates' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['coordinates'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'save_callback' => [\HeimrichHannot\EventsBundle\DataContainer\CalendarEventsContainer::class, 'onSaveCoordinates'],
        'sql' => "varchar(64) NOT NULL default ''",
    ],
    'website' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['website'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50', 'rgxp' => 'url', 'maxlength' => 255],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
];

$dca['fields'] = array_merge(is_array($dca['fields']) ? $dca['fields'] : [], $fields);

// avoid backend handling of timestamps for frontend
$dca['fields']['startTime']['load_callback'] = [
    [
        \HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarEventsListener::class,
        'loadTime',
    ],
];

$dca['fields']['endTime']['load_callback'] = [
    [
        \HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarEventsListener::class,
        'loadTime',
    ],
];

$dca['fields']['location']['eval']['tl_class'] = 'w50';
