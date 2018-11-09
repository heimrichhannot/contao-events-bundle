<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Manager;

use Contao\Controller;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class EventsManager implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    public function initCalendarSubEventsConfig()
    {
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
         * Hooks
         */
        $GLOBALS['TL_HOOKS']['executePreActions']['events-bundle'] = ['huh.events.event_listener.hook.hook_listener', 'executePreActions'];

        /*
         * Assets
         */
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotcontaoevents/css/contao-events-bundle.be.min.css|static';
        }
    }

    public function initCalendarSubEventsDca()
    {
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
    }
}
