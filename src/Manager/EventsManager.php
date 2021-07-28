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
use HeimrichHannot\EventsBundle\Event\BeforeGetSubEventsEvent;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarEventsListener;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(ModelUtil $modelUtil, ArrayUtil $arrayUtil, EventDispatcherInterface $eventDispatcher)
    {
        $this->modelUtil = $modelUtil;
        $this->arrayUtil = $arrayUtil;
        $this->eventDispatcher = $eventDispatcher;
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

                $GLOBALS['BE_MOD']['content']['calendar']['tables'][] = 'tl_calendar_sub_events';

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
        $dca['list']['operations']['toggle']['button_callback'] = [CalendarSubEventsListener::class, 'toggleIcon'];
        $dca['list']['operations']['feature']['button_callback'] = [CalendarSubEventsListener::class, 'iconFeatured'];
        $dca['list']['sorting']['child_record_callback'] = [CalendarSubEventsListener::class, 'listEvents'];

        /*
         * Callbacks
         */
        // Reset callbacks to core default
        $dca['config']['onload_callback'] = [
            [CalendarSubEventsListener::class, 'checkPermission'],
        ];

        $dca['config']['onsubmit_callback'] = [
            [CalendarSubEventsListener::class, 'adjustTime'],
        ];

        /*
         * Fields
         */
        $dca['fields']['pid']['foreignKey'] = 'tl_calendar_events.title';
        $dca['fields']['alias']['save_callback'] = [[CalendarSubEventsListener::class, 'generateAlias']];
        $dca['fields']['startTime']['load_callback'] = [[CalendarSubEventsListener::class, 'loadTime']];
        $dca['fields']['endTime']['load_callback'] = [[CalendarSubEventsListener::class, 'loadTime']];
        $dca['fields']['endTime']['save_callback'] = [[CalendarSubEventsListener::class, 'setEmptyEndTime']];
        $dca['fields']['source']['options_callback'] = [CalendarSubEventsListener::class, 'getSourceOptions'];
        $dca['fields']['articleId']['options_callback'] = [CalendarSubEventsListener::class, 'getArticleAlias'];
        $dca['fields']['serpPreview']['eval']['url_callback'] = [CalendarSubEventsListener::class, 'getSerpUrl'];
        $dca['fields']['serpPreview']['eval']['title_tag_callback'] = [CalendarSubEventsListener::class, 'getTitleTag'];
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

    /**
     * Checks a given event has sub events.
     */
    public function hasSubEvents(int $eventId): bool
    {
        return null !== $this->getSubEvents($eventId);
    }

    /**
     * Retrieves the sub events for a given event.
     *
     * @return mixed
     */
    public function getSubEvents(int $eventId, array $options = [])
    {
        if (CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY === Config::get('subEventMode')) {
            $table = 'tl_calendar_sub_events';
            $parentProperty = 'tl_calendar_events.pid';
        } elseif (CalendarSubEventsListener::SUB_EVENT_MODE_RELATION === Config::get('subEventMode')) {
            $table = 'tl_calendar_events';
            $parentProperty = 'tl_calendar_events.parentEvent';
        } else {
            return null;
        }

        $event = $this->eventDispatcher->dispatch(BeforeGetSubEventsEvent::class, new BeforeGetSubEventsEvent($table, [$parentProperty.'=?'], [$eventId], $options));

        return $this->modelUtil->findModelInstancesBy(
            $event->getTable(), $event->getColumns(), $event->getValues(), $event->getOptions()
        );
    }
}
