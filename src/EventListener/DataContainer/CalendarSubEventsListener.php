<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\Calendar;
use Contao\Config;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Events;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\EventsBundle\Model\CalendarSubEventsModel;
use HeimrichHannot\UtilsBundle\Util\ContainerUtil;
use HeimrichHannot\UtilsBundle\Util\ModelUtil;

class CalendarSubEventsListener
{
    const SUB_EVENT_MODE_ENTITY = 'entity';
    const SUB_EVENT_MODE_RELATION = 'relation';
    /**
     * @var ContainerUtil
     */
    protected $containerUtil;
    /**
     * @var ModelUtil
     */
    protected $modelUtil;

    public function __construct(ContainerUtil $containerUtil, ModelUtil $modelUtil)
    {
        $this->containerUtil = $containerUtil;
        $this->modelUtil = $modelUtil;
    }

    /**
     * Return the SERP URL.
     *
     * @return string
     */
    public function getSerpUrl(CalendarSubEventsModel $model)
    {
        // A sub-event belongs to an event, not directly to a calendar.
        $parent = $this->modelUtil->findModelInstanceByPk('tl_calendar_events', $model->pid);

        return $parent ? Events::generateEventUrl($parent, true) : '';
    }

    /**
     * Return the title tag from the associated page layout.
     *
     * @return string
     */
    public function getTitleTag(CalendarSubEventsModel $model)
    {
        if (null === ($event = $this->modelUtil->findModelInstanceByPk('tl_calendar_events', $model->pid))) {
            return '';
        }

        if (null === ($calendar = $this->modelUtil->findModelInstanceByPk('tl_calendar', $event->pid))) {
            return '';
        }

        /** @var PageModel $page */
        if (!$page = $calendar->getRelated('jumpTo')) {
            return '';
        }

        $page->loadDetails();

        /** @var LayoutModel $layout */
        if (!$layout = $page->getRelated('layout')) {
            return '';
        }

        global $objPage;

        // Set the global page object so we can replace the insert tags
        $originalPage = $objPage;
        $objPage = $page;
        try {
            return System::getContainer()->get('contao.insert_tag.parser')->replace(str_replace('{{page::pageTitle}}', '%s', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}'));
        } finally {
            $objPage = $originalPage;
        }
    }

    /**
     * Auto-generate the event alias if it has not been set yet.
     *
     * @param mixed $varValue
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;
        $db = Database::getInstance();

        // Generate alias if there is none
        if ('' == $varValue) {
            $autoAlias = true;
            $varValue = StringUtil::generateAlias($dc->activeRecord->title);
        }

        $objAlias = $db->prepare('SELECT id FROM tl_calendar_sub_events WHERE alias=? AND id!=?')
            ->execute($varValue, $dc->id);

        // Check whether the event alias exists
        if ($objAlias->numRows) {
            if (!$autoAlias) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-'.$dc->id;
        }

        return $varValue;
    }

    /**
     * Set the timestamp to 1970-01-01 (see #26).
     *
     * @param int $value
     *
     * @return int
     */
    public function loadTime($value)
    {
        if ($this->containerUtil->isFrontend()) {
            return $value;
        }

        return strtotime('1970-01-01 '.date('H:i:s', (int) $value));
    }

    /**
     * Automatically set the end time if not set.
     *
     * @param mixed $varValue
     *
     * @return string
     */
    public function setEmptyEndTime($varValue, DataContainer $dc)
    {
        if (null === $varValue) {
            $varValue = $dc->activeRecord->startTime;
        }

        return $varValue;
    }

    /**
     * Add the type of input field.
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listEvents($arrRow)
    {
        $span = Calendar::calculateSpan($arrRow['startTime'], $arrRow['endTime']);

        if ($span > 0) {
            $date = Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['startTime']).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get(($arrRow['addTime'] ? 'datimFormat' : 'dateFormat')), $arrRow['endTime']);
        } elseif ($arrRow['startTime'] == $arrRow['endTime']) {
            $date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']).($arrRow['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $arrRow['startTime']) : '');
        } else {
            $date = Date::parse(Config::get('dateFormat'), $arrRow['startTime']).($arrRow['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $arrRow['startTime']).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get('timeFormat'), $arrRow['endTime']) : '');
        }

        return '<div class="tl_content_left">'.$arrRow['title'].' <span style="color:#999;padding-left:3px">['.$date.']</span></div>';
    }

    /**
     * Get all articles and return them as array.
     *
     * @return array
     */
    public function getArticleAlias(DataContainer $dc)
    {
        $arrPids = [];
        $arrAlias = [];
        $user = BackendUser::getInstance();
        $db = Database::getInstance();

        if (!$user->isAdmin) {
            foreach ($user->pagemounts as $id) {
                $arrPids[] = $id;
                $arrPids = array_merge($arrPids, $db->getChildRecords($id, 'tl_page'));
            }

            if (empty($arrPids)) {
                return $arrAlias;
            }

            $objAlias = $db->prepare('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN('.implode(',', array_map('\intval', array_unique($arrPids))).') ORDER BY parent, a.sorting')
                ->execute($dc->id);
        } else {
            $objAlias = $db->prepare('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting')
                ->execute($dc->id);
        }

        if ($objAlias->numRows) {
            System::loadLanguageFile('tl_article');

            while ($objAlias->next()) {
                $arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title.' ('.($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?: $objAlias->inColumn).', ID '.$objAlias->id.')';
            }
        }

        return $arrAlias;
    }

    /**
     * Add the source options depending on the allowed fields (see #5498).
     *
     * @return array
     */
    public function getSourceOptions(DataContainer $dc)
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return ['default', 'internal', 'article', 'external'];
        }

        $arrOptions = ['default'];

        // Add the "internal" option
        if ($user->hasAccess('tl_calendar_sub_events::jumpTo', 'alexf')) {
            $arrOptions[] = 'internal';
        }

        // Add the "article" option
        if ($user->hasAccess('tl_calendar_sub_events::articleId', 'alexf')) {
            $arrOptions[] = 'article';
        }

        // Add the "external" option
        if ($user->hasAccess('tl_calendar_sub_events::url', 'alexf')) {
            $arrOptions[] = 'external';
        }

        // Add the option currently set
        if ($dc->activeRecord && '' != $dc->activeRecord->source) {
            $arrOptions[] = $dc->activeRecord->source;
            $arrOptions = array_unique($arrOptions);
        }

        return $arrOptions;
    }

    /**
     * Adjust start end end time of the event based on date, span, startTime and endTime.
     */
    public function adjustTime(DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord?->startDate) {
            return;
        }

        $db = Database::getInstance();

        $arrSet['startTime'] = $dc->activeRecord->startDate;
        $arrSet['endTime'] = $dc->activeRecord->startDate;

        // Set end date
        if ($dc->activeRecord->endDate) {
            if ($dc->activeRecord->endDate > $dc->activeRecord->startDate) {
                $arrSet['endDate'] = $dc->activeRecord->endDate;
                $arrSet['endTime'] = $dc->activeRecord->endDate;
            } else {
                $arrSet['endDate'] = $dc->activeRecord->startDate;
                $arrSet['endTime'] = $dc->activeRecord->startDate;
            }
        }

        // Add time
        if ($dc->activeRecord->addTime) {
            $arrSet['startTime'] = strtotime(date('Y-m-d', $arrSet['startTime']).' '.date('H:i:s', $dc->activeRecord->startTime));
            $arrSet['endTime'] = strtotime(date('Y-m-d', $arrSet['endTime']).' '.date('H:i:s', $dc->activeRecord->endTime));
        } // Adjust end time of "all day" events
        elseif (($dc->activeRecord->endDate && $arrSet['endDate'] == $arrSet['endTime']) || $arrSet['startTime'] == $arrSet['endTime']) {
            $arrSet['endTime'] = (strtotime('+ 1 day', $arrSet['endTime']) - 1);
        }

        $arrSet['repeatEnd'] = 0;

        // Recurring events
        if ($dc->activeRecord->recurring) {
            // Unlimited recurrences end on 2038-01-01 00:00:00 (see #4862)
            if (0 == $dc->activeRecord->recurrences) {
                $arrSet['repeatEnd'] = min(4294967295, PHP_INT_MAX);
            } else {
                $arrRange = StringUtil::deserialize($dc->activeRecord->repeatEach);

                if (\is_array($arrRange) && isset($arrRange['unit']) && isset($arrRange['value'])) {
                    $arg = $arrRange['value'] * $dc->activeRecord->recurrences;
                    $unit = $arrRange['unit'];

                    $strtotime = '+ '.$arg.' '.$unit;
                    $arrSet['repeatEnd'] = strtotime($strtotime, $arrSet['endTime']);
                }
            }
        }

        $db->prepare('UPDATE tl_calendar_sub_events %s WHERE id=?')->set($arrSet)->execute($dc->id);
    }

}
