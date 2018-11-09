<?php

if (\Contao\Config::get('subEventMode') === \HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY)
{
    System::getContainer()->get('huh.events.manager.events_manager')->initCalendarSubEventsDca();
}