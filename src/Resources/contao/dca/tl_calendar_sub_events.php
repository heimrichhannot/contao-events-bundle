<?php

use Contao\Controller;
use Contao\System;
use HeimrichHannot\EventsBundle\DataContainer\CalendarSubEventsContainer;
use HeimrichHannot\EventsBundle\Enum\SubEventMode;

if (SubEventMode::is(SubEventMode::ENTITY))
    // only load the dca if we are in entity sub event mode
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
    $dca['list']['operations']['toggle']['button_callback'] = [CalendarSubEventsContainer::class, 'toggleIcon'];
    $dca['list']['operations']['feature']['button_callback'] = [CalendarSubEventsContainer::class, 'iconFeatured'];
    $dca['list']['sorting']['child_record_callback'] = [CalendarSubEventsContainer::class, 'listEvents'];

    /*
     * Callbacks
     */
    // Reset callbacks to core default
    $dca['config']['onload_callback'] = [[CalendarSubEventsContainer::class, 'checkPermission']];
    $dca['config']['onsubmit_callback'] = [[CalendarSubEventsContainer::class, 'adjustTime']];

    /*
     * Fields
     */
    $dca['fields']['pid']['foreignKey'] = 'tl_calendar_events.title';
    $dca['fields']['alias']['save_callback'] = [];  // reset
    $dca['fields']['source']['options_callback'] = [CalendarSubEventsContainer::class, 'getSourceOptions'];
    $dca['fields']['articleId']['options_callback'] = [CalendarSubEventsContainer::class, 'getArticleAlias'];
    $dca['fields']['serpPreview']['eval']['url_callback'] = [CalendarSubEventsContainer::class, 'getSerpUrl'];
    $dca['fields']['serpPreview']['eval']['title_tag_callback'] = [CalendarSubEventsContainer::class, 'getTitleTag'];
}
