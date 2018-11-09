<?php

System::getContainer()->get('huh.events.manager.events_manager')->initCalendarSubEventsConfig();

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePreActions']['events-bundle'] = ['huh.events.event_listener.hook.hook_listener', 'executePreActions'];