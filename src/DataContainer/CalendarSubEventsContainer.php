<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\BackendUser;
use Contao\Calendar;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Events;
use Contao\Image;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use Doctrine\DBAL\Connection;
use Exception;
use HeimrichHannot\EventsBundle\Manager\EventsManager;
use HeimrichHannot\EventsBundle\Model\CalendarSubEventsModel;
use HeimrichHannot\EventsBundle\Util\DcaHelper;
use HeimrichHannot\EventsBundle\Util\Helper;
use HeimrichHannot\EventsBundle\Util\LocationUtil;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CalendarSubEventsContainer extends CalendarEventsContainer
{
    public const TABLE = 'tl_calendar_sub_events';

    public function __construct(
        Connection             $connection,
        ContaoCsrfTokenManager $csrfTokenManager,
        EventsManager          $eventsManager,
        Helper                 $helper,
        LocationUtil           $locationUtil,
        ParameterBagInterface  $parameterBag,
        RequestStack           $requestStack,
        private Utils          $utils,
    ) {
        parent::__construct(
            connection: $connection,
            csrfTokenManager: $csrfTokenManager,
            eventsManager: $eventsManager,
            helper: $helper,
            locationUtil: $locationUtil,
            parameterBag: $parameterBag,
            requestStack: $requestStack,
            utils: $utils,
        );
    }

    /**
     * Return the SERP URL.
     *
     * @return string
     */
    public function getSerpUrl(CalendarSubEventsModel $model)
    {
        return Events::generateEventUrl($model, true);
    }

    /**
     * Return the title tag from the associated page layout.
     *
     * @return string
     * @throws Exception
     */
    public function getTitleTag(CalendarSubEventsModel $model): string
    {
        if (null === ($event = $this->utils->model()->findModelInstanceByPk('tl_calendar', $model->pid))) {
            return '';
        }

        if (null === ($calendar = $this->utils->model()->findModelInstanceByPk('tl_calendar', $event->pid))) {
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
        $objPage = $page;

        return Controller::replaceInsertTags(str_replace('{{page::pageTitle}}', '%s', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}'));
    }

    /**
     * Auto-generate the event alias if it has not been set yet.
     *
     * @throws \Exception If the alias already exists
     */
    #[AsCallback(self::TABLE, 'fields.alias.save', priority: 255)]
    public function generateAlias(string $value, DataContainer $dc): string
    {
        $autoAlias = false;
        $db = Database::getInstance();

        // Generate alias if there is none
        if (!$value)
        {
            $record = DcaHelper::activeRecord($dc);
            $autoAlias = true;
            $value = StringUtil::generateAlias($record->title ?: $dc->id);
        }

        $subEvent = $db->prepare('SELECT id FROM tl_calendar_sub_events WHERE alias=? AND id!=?')->execute($value, $dc->id);

        // Check whether the event alias exists
        if ($subEvent->numRows)
        {
            if (!$autoAlias) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $value));
            }

            $value .= '-' . $dc->id;
        }

        return $value;
    }

    #[AsCallback(self::TABLE, 'fields.startTime.load')]
    #[AsCallback(self::TABLE, 'fields.endTime.load')]
    public function loadTime(int $value): int
    {
        return parent::loadTime($value);
    }

    /**
     * Automatically set the end time if not set.
     */
    #[AsCallback(self::TABLE, 'fields.endTime.save')]
    public function setEmptyEndTime(mixed $value, DataContainer $dc): string
    {
        if (null === $value && ($activeRecord = DcaHelper::activeRecord($dc)))
        {
            $value = $activeRecord->startTime ?? '';
        }

        return (string) ($value ?: '');
    }

    /**
     * Add the type of input field.
     *
     * @param array $row
     *
     * @return string
     */
    public function listEvents(array $row): string
    {
        $span = Calendar::calculateSpan($row['startTime'], $row['endTime']);

        if ($span > 0)
        {
            $date = Date::parse(Config::get(($row['addTime'] ? 'datimFormat' : 'dateFormat')), $row['startTime']). $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get(($row['addTime'] ? 'datimFormat' : 'dateFormat')), $row['endTime']);
        }
        elseif ($row['startTime'] == $row['endTime'])
        {
            $date = Date::parse(Config::get('dateFormat'), $row['startTime']).($row['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $row['startTime']) : '');
        }
        else
        {
            $date = Date::parse(Config::get('dateFormat'), $row['startTime']).($row['addTime'] ? ' '.Date::parse(Config::get('timeFormat'), $row['startTime']). $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse(Config::get('timeFormat'), $row['endTime']) : '');
        }

        return '<div class="tl_content_left">'. $row['title'].' <span style="color:#999;padding-left:3px">['.$date.']</span></div>';
    }

    /**
     * Get all articles and return them as array.
     *
     * @return array
     */
    public function getArticleAlias(DataContainer $dc): array
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
    public function getSourceOptions(DataContainer $dc): array
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
     * Adjust start and end time of the event based on date, span, startTime and endTime.
     */
    public function adjustTime(DataContainer $dc): void
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord) {
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
                $arrSet['repeatEnd'] = 2145913200;
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

    /**
     * Return the "toggle visibility" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (\strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (1 == Input::get('state')), (@func_get_arg(12) ?: null));
            Controller::redirect(Controller::getReferer());
        }

        $user = BackendUser::getInstance();

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$user->hasAccess('tl_calendar_sub_events::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.Controller::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? 1 : 0).'"').'</a> ';
    }

    /**
     * Disable/enable a user group.
     *
     * @param int           $intId
     * @param bool          $blnVisible
     * @param DataContainer $dc
     *
     * @throws AccessDeniedException
     */
    public function toggleVisibility($intId, $blnVisible, DataContainer $dc = null)
    {
        $user = BackendUser::getInstance();
        $db = Database::getInstance();

        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc) {
            $dc->id = $intId; // see #8043
        }

        // Trigger the onload_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_calendar_sub_events']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_calendar_sub_events']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $obj = System::importStatic($callback[0]);
                    $obj->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$user->hasAccess('tl_calendar_sub_events::published', 'alexf')) {
            throw new AccessDeniedException('Not enough permissions to publish/unpublish event ID '.$intId.'.');
        }

        // Set the current record
        if ($dc) {
            $objRow = $db->prepare('SELECT * FROM tl_calendar_sub_events WHERE id=?')
                ->limit(1)
                ->execute($intId);

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Versions('tl_calendar_sub_events', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_calendar_sub_events']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_calendar_sub_events']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $obj = System::importStatic($callback[0]);
                    $blnVisible = $obj->{$callback[1]}($blnVisible, $dc);
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();
        $published = ($blnVisible ? '1' : '');

        // Update the database
        $db->prepare("UPDATE tl_calendar_sub_events SET tstamp=?, published=? WHERE id=?")
            ->execute($time, $published, $intId);

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_calendar_sub_events']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_calendar_sub_events']['config']['onsubmit_callback'] as $callback) {
                if (\is_array($callback)) {
                    $obj = System::importStatic($callback[0]);
                    $obj->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();
    }
}
