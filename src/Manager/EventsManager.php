<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Manager;

use Contao\ArrayUtil;
use Contao\Config;
use Contao\DataContainer;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use HeimrichHannot\EventsBundle\Enum\SubEventMode;
use HeimrichHannot\EventsBundle\Event\BeforeGetSubEventsEvent;
use HeimrichHannot\EventsBundle\DataContainer\CalendarEventsContainer;
use HeimrichHannot\EventsBundle\DataContainer\CalendarSubEventsContainer;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class EventsManager
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Utils $utils,
    ) {}

    public function initCalendarEventsDcaForSubEvents(): void
    {
        match (SubEventMode::fromConfig()) {
            SubEventMode::ENTITY   => $this->initCalendarEventsDcaForSubEventModeEntity(),
            SubEventMode::RELATION => $this->initCalendarEventsDcaForSubEventModeRelation(),
            default => null,
        };
    }

    public function initCalendarEventsDcaForSubEventModeEntity(): void
    {
        $dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

        /*
         * Operations
         */
        $dca['list']['operations']['subevents'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['subevents'],
            'href' => 'table=tl_calendar_sub_events',
            'button_callback' => [CalendarEventsContainer::class, 'iconSubEvents'],
        ];

        /*
         * Callbacks
         */
        $callbacks = [];

        foreach ($dca['config']['onload_callback'] as $callback) {
            if ('checkPermission' === $callback[1]) {
                $callbacks[] = [CalendarEventsContainer::class, 'checkPermission'];

                continue;
            }

            $callbacks[] = $callback;
        }

        $dca['config']['onload_callback'] = $callbacks;
    }

    public function initCalendarEventsDcaForSubEventModeRelation(): void
    {
        $dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

        /*
         * List
         */
        // hide child elements
        $dca['config']['onload_callback'][] = function (DataContainer $dc) use (&$dca) {
            if (Input::get('do') !== 'calendar') {
                return;
            }

            if (!$subEvents = $this->utils->model()->findModelInstancesBy('tl_calendar_events', ['tl_calendar_events.parentEvent = 0'], [])) {
                return;
            }

            foreach ($subEvents->fetchEach('id') as $id) {
                $dca['list']['sorting']['root'][] = $id;
            }
        };

        // `child_record_callback` doesn't resolve plain service title so use System::getContainer or class name
        $dca['list']['sorting']['child_record_callback'] = [CalendarEventsContainer::class, 'listEvents'];

        if (false !== ($index = \array_search('copy', \array_keys($dca['list']['operations']))))
        {
            ArrayUtil::arrayInsert($dca['list']['operations'], $index + 1, [
                'create_sub_event' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['create_sub_event'],
                    'icon' => 'new.svg',
                    'href' => 'act=create&mode=2',
                    'button_callback' => [CalendarEventsContainer::class, 'iconCreateSubEvent'],
                ],
            ]);
        }

        /*
         * Callbacks
         */
        $dca['config']['onload_callback'][] = [CalendarEventsContainer::class, 'checkForSubEvents'];

        /*
         * Palettes
         */
        $dca['palettes']['default'] = str_replace('alias,', 'alias,parentEvent,', $dca['palettes']['default']);

        /*
         * Fields.
         */
        $dca['fields']['parentEvent'] ??= [
            'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['parentEvent'],
            'exclude' => true,
            'filter' => true,
            'default' => \Input::get('parentEvent') ?: 0,
            'inputType' => 'select',
            'options_callback' => function (DataContainer $dc) {
                $options = [];

                // only allow events which are neither children nor parents
                // at first, get all parent event ids
                $columns = ['tl_calendar_events.id != ?', 'tl_calendar_events.parentEvent = 0'];

                if (!$events = $this->utils->model()->findModelInstancesBy('tl_calendar_events', $columns, [$dc->id], ['order' => 'tl_calendar_events.startTime DESC'])) {
                    return $options;
                }

                $dateFormat = Config::get('dateFormat');

                while ($events->next())
                {
                    $options[$events->id] = \sprintf(
                        '%s (%s, ID %s)',
                        $events->title,
                        date($dateFormat, $events->startTime),
                        $events->id,
                    );
                }

                return $options;
            },
            'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ];

        $dca['fields']['alias']['eval']['tl_class'] = 'w50';
    }

    /**
     * Whether a given event has sub events.
     */
    public function hasSubEvents(int $eventId): bool
    {
        return null !== $this->getSubEvents($eventId);
    }

    /**
     * Retrieves the sub events of a given event.
     *
     * @return Collection<Model>|Model|Model[]|null
     */
    public function getSubEvents(int $eventId, array $options = []): Collection|Model|array|null
    {
        $subEventMode = SubEventMode::fromConfig();

        $table = match ($subEventMode) {
            SubEventMode::ENTITY   => 'tl_calendar_sub_events',
            SubEventMode::RELATION => 'tl_calendar_events',
            default => null,
        };

        $parentProperty = match ($subEventMode) {
            SubEventMode::ENTITY   => 'tl_calendar_events.id',
            SubEventMode::RELATION => 'tl_calendar_events.parentEvent',
            default => null,
        };

        if (!$table || !$parentProperty) {
            return null;
        }

        $event = $this->eventDispatcher->dispatch(
            new BeforeGetSubEventsEvent($table, [$parentProperty.'=?'], [$eventId], $options),
            BeforeGetSubEventsEvent::class,
        );

        return $this->utils->model()->findModelInstancesBy(
            $event->getTable(), $event->getColumns(), $event->getValues(), $event->getOptions(),
        );
    }
}
