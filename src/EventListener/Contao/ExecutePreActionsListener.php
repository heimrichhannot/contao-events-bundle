<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\Contao;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\Input;
use Contao\System;
use HeimrichHannot\EventsBundle\DataContainer\CalendarEventsContainer;
use HeimrichHannot\EventsBundle\DataContainer\CalendarSubEventsContainer;

#[AsHook('executePreActions')]
class ExecutePreActionsListener
{
    public function __invoke(string $action): void
    {
        if ($action !== 'toggleFeatured') {
            return;
        }

        if (Input::get('do') !== 'calendar') {
            return;
        }

        $table = Input::get('table');

        $listener = match ($table) {
            'tl_calendar_events' => CalendarEventsContainer::class,
            'tl_calendar_sub_events' => CalendarSubEventsContainer::class,
            default => null,
        };

        if (!$listener || !\class_exists($listener)) {
            return;
        }

        $id = Input::post('id');
        $state = '1' === (string) Input::post('state');

        System::getContainer()->get($listener)->toggleFeatured($id, $state);

        throw new NoContentResponseException();
    }
}