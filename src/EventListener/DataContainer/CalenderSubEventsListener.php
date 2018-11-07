<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\Calendar;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class CalenderSubEventsListener implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    /**
     * Check permissions to edit table tl_calendar_sub_events.
     *
     * @throws AccessDeniedException
     */
    public function checkPermission()
    {
        $bundles = $this->container->getParameter('kernel.bundles');

        // HOOK: comments extension required
        if (!isset($bundles['ContaoCommentsBundle'])) {
            $key = array_search('allowComments', $GLOBALS['TL_DCA']['tl_calendar_sub_events']['list']['sorting']['headerFields']);
            unset($GLOBALS['TL_DCA']['tl_calendar_sub_events']['list']['sorting']['headerFields'][$key]);
        }

        $user = BackendUser::getInstance();
        $db = Database::getInstance();

        if ($user->isAdmin) {
            return;
        }

        // Set root IDs
        if (empty($user->calendars) || !\is_array($user->calendars)) {
            $root = [0];
        } else {
            $root = $user->calendars;
        }

        $id = \strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

        // Check current action
        switch (Input::get('act')) {
            case 'paste':
                // Allow
                break;

            case 'create':
                if (!\strlen(Input::get('pid')) || !\in_array(Input::get('pid'), $root)) {
                    throw new AccessDeniedException('Not enough permissions to create events in calendar ID '.Input::get('pid').'.');
                }

                break;

            case 'cut':
            case 'copy':
                if (!\in_array(Input::get('pid'), $root)) {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' event ID '.$id.' to calendar ID '.Input::get('pid').'.');
                }
            // no break STATEMENT HERE

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
                $objCalendar = $db->prepare('SELECT pid FROM tl_calendar_sub_events WHERE id=?')
                    ->limit(1)
                    ->execute($id);

                if ($objCalendar->numRows < 1) {
                    throw new AccessDeniedException('Invalid event ID '.$id.'.');
                }

                if (!\in_array($objCalendar->pid, $root)) {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' event ID '.$id.' of calendar ID '.$objCalendar->pid.'.');
                }

                break;

            case 'select':
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!\in_array($id, $root)) {
                    throw new AccessDeniedException('Not enough permissions to access calendar ID '.$id.'.');
                }

                $objCalendar = $db->prepare('SELECT id FROM tl_calendar_sub_events WHERE pid=?')
                    ->execute($id);

                if ($objCalendar->numRows < 1) {
                    throw new AccessDeniedException('Invalid calendar ID '.$id.'.');
                }

                /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
                $objSession = $this->container->get('session');

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCalendar->fetchEach('id'));
                $objSession->replace($session);

                break;

            default:
                if (\strlen(Input::get('act'))) {
                    throw new AccessDeniedException('Invalid command "'.Input::get('act').'".');
                } elseif (!\in_array($id, $root)) {
                    throw new AccessDeniedException('Not enough permissions to access calendar ID '.$id.'.');
                }

                break;
        }
    }

    /**
     * Auto-generate the event alias if it has not been set yet.
     *
     * @param mixed         $varValue
     * @param DataContainer $dc
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
        if ($this->container->get('huh.utils.container')->isFrontend()) {
            return $value;
        }

        return strtotime('1970-01-01 '.date('H:i:s', $value));
    }

    /**
     * Automatically set the end time if not set.
     *
     * @param mixed         $varValue
     * @param DataContainer $dc
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
     * @param DataContainer $dc
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
     * @param DataContainer $dc
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
     *
     * @param DataContainer $dc
     */
    public function adjustTime(DataContainer $dc)
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

        // Update the database
        $db->prepare("UPDATE tl_calendar_sub_events SET tstamp=$time, published='".($blnVisible ? '1' : '')."' WHERE id=?")
            ->execute($intId);

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
