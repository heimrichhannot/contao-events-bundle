<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Manager;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\System;
use HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class EventsManager implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    public function initCalendarSubEventsConfig()
    {
        if (CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY === Config::get('subEventMode')) {
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

            /*
             * Assets
             */
            if (System::getContainer()->get('huh.utils.container')->isBackend()) {
                $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotcontaoevents/css/contao-events-bundle.be.min.css|static';
            }
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
                'button_callback' => ['huh.events.event_listener.data_container.calendar_events_listener', 'iconSubEvents'],
            ];

            /**
             * Callbacks.
             */
            $callbacks = [];

            foreach ($dca['config']['onload_callback'] as $callback) {
                if ('checkPermission' === $callback[1]) {
                    $callbacks[] = ['huh.events.event_listener.data_container.calendar_events_listener', 'checkPermission'];

                    continue;
                }

                $callbacks[] = $callback;
            }

            $dca['config']['onload_callback'] = $callbacks;

            /*
             * Fields
             */
            $dca['fields']['startTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
            $dca['fields']['endTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
        } elseif (CalendarSubEventsListener::SUB_EVENT_MODE_RELATION === Config::get('subEventMode')) {
            /*
             * List
             */
            $dca['list']['sorting']['child_record_callback'] = function ($arrRow) {
                die('LLL');

                $span = Calendar::calculateSpan($arrRow['startTime'], $arrRow['endTime']);

                if ($span > 0) {
                    $date = Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['startTime']).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['endTime']);
                } elseif ($arrRow['startTime'] == $arrRow['endTime']) {
                    $date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']).($arrRow['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $arrRow['startTime']) : '');
                } else {
                    $date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']).($arrRow['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $arrRow['startTime']).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get('timeFormat'), $arrRow['endTime']) : '');
                }

                // retrieve sub events
                $subEvents = '';

                /* @var CalendarEventsModel $adapter */
                if (null !== ($adapter = $this->framework->getAdapter(CalendarEventsModel::class))) {
                    if (null !== ($events = $adapter->getSubEvents($arrRow['id']))) {
                    }
                }

                return '<div class="tl_content_left">'.$arrRow['title'].' <span style="color:#999;padding-left:3px">['.$date.']</span></div>';
            };

            /*
             * Callbacks
             */
            $dca['config']['onload_callback'][] = ['huh.events.event_listener.data_container.calendar_events_listener', 'checkForSubEvents'];

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
                    'inputType' => 'select',
                    'options_callback' => function (\Contao\DataContainer $dc) {
                        $options = [];

                        // only allow events which are neither children nor parents
                        // at first, get all parent event ids
                        $columns = ['id != ?', 'parentEvent = 0'];

                        if (null !== ($events = System::getContainer()->get('huh.utils.model')->findModelInstancesBy(
                                'tl_calendar_events', $columns, [$dc->id], ['order' => 'startTime DESC']))) {
                            while ($events->next()) {
                                $options[$events->id] = $events->title.' ('.date(\Contao\Config::get('dateFormat'), $events->startTime).', ID '.$events->id.')';
                            }
                        }

                        return $options;
                    },
                    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
                    'sql' => "varchar(64) NOT NULL default ''",
                ],
            ];

            $dca['fields'] += $fields;

            $dca['fields']['alias']['eval']['tl_class'] = 'w50';
        }
    }
}
