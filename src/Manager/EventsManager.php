<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Manager;

use Contao\Config;
use Contao\Controller;
use Contao\DataContainer;
use Contao\System;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarEventsListener;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;

class EventsManager
{
    /**
     * @var ModelUtil
     */
    protected $modelUtil;
    /**
     * @var ArrayUtil
     */
    protected $arrayUtil;

    public function __construct(ModelUtil $modelUtil, ArrayUtil $arrayUtil)
    {
        $this->modelUtil = $modelUtil;
        $this->arrayUtil = $arrayUtil;
    }

    public function initCalendarSubEventsConfig()
    {
        switch (Config::get('subEventMode')) {
            case CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY:
                /*
                 * Models
                 */
                $GLOBALS['TL_MODELS']['tl_calendar_sub_events'] = 'HeimrichHannot\EventsBundle\Model\CalendarSubEventsModel';

                /*
                 * Backend modules
                 */
                $GLOBALS['BE_MOD']['content']['calendar_subevents'] = [
                    'tables' => ['tl_calendar_sub_events', 'tl_content'],
                ];

                break;
        }
    }

    public function initCalendarSubEventsDca()
    {
        if (CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY !== Config::get('subEventMode')) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_calendar_sub_events'];

        // use the dca of tl_calendar_events as a base
        Controller::loadDataContainer('tl_calendar_events');
        System::loadLanguageFile('tl_calendar_events');
        $dca = $GLOBALS['TL_DCA']['tl_calendar_events'];
        $GLOBALS['TL_LANG']['tl_calendar_sub_events'] = $GLOBALS['TL_LANG']['tl_calendar_events'];

        /*
         * Config
         */
        $dca['config']['ptable'] = 'tl_calendar_events';

        /*
         * List
         */
        // there are no sub-sub-events
        unset($dca['list']['operations']['subevents']);

        $dca['list']['operations']['edit']['href'] = 'do=calendar_subevents&table=tl_content';
        $dca['list']['operations']['toggle']['button_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'toggleIcon'];
        $dca['list']['operations']['feature']['button_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'iconFeatured'];
        $dca['list']['sorting']['child_record_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'listEvents'];

        /*
         * Callbacks
         */
        // Reset callbacks to core default
        $dca['config']['onload_callback'] = [
            ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'checkPermission'],
        ];

        $dca['config']['onsubmit_callback'] = [
            ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'adjustTime'],
        ];

        /*
         * Fields
         */
        $dca['fields']['pid']['foreignKey'] = 'tl_calendar_events.title';
        $dca['fields']['alias']['save_callback'] = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'generateAlias']];
        $dca['fields']['startTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'loadTime']];
        $dca['fields']['endTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'loadTime']];
        $dca['fields']['endTime']['save_callback'] = [['huh.events.event_listener.data_container.calendar_sub_events_listener', 'setEmptyEndTime']];
        $dca['fields']['source']['options_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'getSourceOptions'];
        $dca['fields']['articleId']['options_callback'] = ['huh.events.event_listener.data_container.calendar_sub_events_listener', 'getArticleAlias'];
    }

    public function initCalendarEventsDcaForSubEvents()
    {
        $dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

        if (CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY === Config::get('subEventMode')) {
            /*
             * Operations
             */
            $dca['list']['operations']['subevents'] = [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['subevents'],
                'href' => 'table=tl_calendar_sub_events',
                'button_callback' => [CalendarEventsListener::class, 'iconSubEvents'],
            ];

            /**
             * Callbacks.
             */
            $callbacks = [];

            foreach ($dca['config']['onload_callback'] as $callback) {
                if ('checkPermission' === $callback[1]) {
                    $callbacks[] = [CalendarEventsListener::class, 'checkPermission'];

                    continue;
                }

                $callbacks[] = $callback;
            }

            $dca['config']['onload_callback'] = $callbacks;
        } elseif (CalendarSubEventsListener::SUB_EVENT_MODE_RELATION === Config::get('subEventMode')) {
            /*
             * List
             */
            // hide child elements
            $dca['config']['onload_callback'][] = function (DataContainer $dc) use (&$dca) {
                if ('calendar' === \Input::get('do') && null !== ($subEvents = $this->modelUtil->findModelInstancesBy(
                        'tl_calendar_events', ['tl_calendar_events.parentEvent = 0'], []))) {
                    foreach ($subEvents->fetchEach('id') as $id) {
                        $dca['list']['sorting']['root'][] = $id;
                    }
                }
            };

            // `child_record_callback` doesn't resolve plain service title so use System::getContainer or class name
            $dca['list']['sorting']['child_record_callback'] = [CalendarEventsListener::class, 'listEvents'];

            /*
             * Operations
             */
            $this->arrayUtil->insertInArrayByName(
                $dca['list']['operations'],
                'copy', [
                'create_sub_event' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['create_sub_event'],
                    'icon' => 'new.svg',
                    'href' => 'act=create&mode=2',
                    'button_callback' => [CalendarEventsListener::class, 'iconCreateSubEvent'],
                ],
            ], 1
            );

            /*
             * Callbacks
             */
            $dca['config']['onload_callback'][] = [CalendarEventsListener::class, 'checkForSubEvents'];

            /*
             * Palettes
             */
            $dca['palettes']['default'] = str_replace('alias,', 'alias,parentEvent,', $dca['palettes']['default']);

            /**
             * Fields.
             */
            $fields = [
                'parentEvent' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['parentEvent'],
                    'exclude' => true,
                    'filter' => true,
                    'default' => \Input::get('parentEvent') ?: 0,
                    'inputType' => 'select',
                    'options_callback' => function (\Contao\DataContainer $dc) {
                        $options = [];

                        // only allow events which are neither children nor parents
                        // at first, get all parent event ids
                        $columns = ['tl_calendar_events.id != ?', 'tl_calendar_events.parentEvent = 0'];

                        if (null !== ($events = $this->modelUtil->findModelInstancesBy(
                                'tl_calendar_events', $columns, [$dc->id], ['order' => 'tl_calendar_events.startTime DESC']))) {
                            while ($events->next()) {
                                $options[$events->id] = $events->title.' ('.date(\Contao\Config::get('dateFormat'), $events->startTime).', ID '.$events->id.')';
                            }
                        }

                        return $options;
                    },
                    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
                    'sql' => "int(10) unsigned NOT NULL default '0'",
                ],
            ];

            $dca['fields'] += $fields;

            $dca['fields']['alias']['eval']['tl_class'] = 'w50';
        }
    }
}
