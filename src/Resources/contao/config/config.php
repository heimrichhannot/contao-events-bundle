<?php

System::getContainer()->get('huh.events.manager.events_manager')->initCalendarSubEventsConfig();

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePreActions']['events-bundle'] = ['huh.events.event_listener.hook.hook_listener', 'executePreActions'];

/*
 * Assets
 */
if (System::getContainer()->get('huh.utils.container')->isBackend()) {
    $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotcontaoevents/css/contao-events-bundle.be.min.css|static';
}