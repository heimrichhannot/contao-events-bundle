<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

System::getContainer()->get('huh.events.manager.events_manager')->initCalendarSubEventsConfig();

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePreActions']['events-bundle'] = ['huh.events.event_listener.hook.hook_listener', 'executePreActions'];
$GLOBALS['TL_HOOKS']['loadDataContainer']['huh_events'] = [
    \HeimrichHannot\EventsBundle\EventListener\LoadDataContainerListener::class,
    'onLoadDataContainer',
];
/*
 * Assets
 */
if (System::getContainer()->get('huh.utils.container')->isBackend()) {
    $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotcontaoevents/css/contao-events-bundle.be.css|static';
}
