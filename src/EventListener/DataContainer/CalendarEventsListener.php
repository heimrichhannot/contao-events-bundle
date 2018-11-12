<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\Calendar;
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
use HeimrichHannot\EventsBundle\Model\CalendarEventsModel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class CalendarEventsListener implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    public function checkForSubEvents(DataContainer $dc)
    {
        /* @var CalendarEventsModel $adapter */
        if (null === ($adapter = $this->framework->getAdapter(CalendarEventsModel::class))) {
            return;
        }

        if ($adapter->hasSubEvents($dc->id)) {
            $dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

            unset($dca['fields']['parentEvent']);
        }
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
     * Return the "subevents" button.
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
    public function iconSubEvents($row, $href, $label, $title, $icon, $attributes)
    {
        $subEvents = $this->container->get('huh.utils.model')->findModelInstancesBy('tl_calendar_sub_events', ['pid=?'], [$row['id']]);

        $icon = 'bundles/heimrichhannotcontaoevents/img/icon-subevents'.(null !== $subEvents ? '-existing' : '').'.png';

        $href .= '&id='.$row['id'];

        return '<a href="'.Controller::addToUrl($href, true, ['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Return the "create sub event" button.
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
    public function iconCreateSubEvent($row, $href, $label, $title, $icon, $attributes)
    {
        /* @var CalendarEventsModel $adapter */
        if ($row['parentEvent'] > 0) {
            return '';
        }

        $href .= '&pid='.\Input::get('id').'&parentEvent='.$row['id'].'&rt='.\RequestToken::get();

        return '<a href="'.Controller::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Return the "feature/unfeature element" button.
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
    public function iconFeatured($row, $href, $label, $title, $icon, $attributes)
    {
        $user = BackendUser::getInstance();

        if (\strlen(Input::get('fid'))) {
            $this->toggleFeatured(Input::get('fid'), (1 == Input::get('state')), (@func_get_arg(12) ?: null));
            Controller::redirect(System::getReferer());
        }

        // Check permissions AFTER checking the fid, so hacking attempts are logged
        if (!$user->hasAccess('tl_calendar_events::featured', 'alexf')) {
            return '';
        }

        $href .= '&amp;fid='.$row['id'].'&amp;state='.($row['featured'] ? '' : 1);

        if (!$row['featured']) {
            $icon = 'featured_.svg';
        }

        return '<a href="'.Controller::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['featured'] ? 1 : 0).'"').'</a> ';
    }

    /**
     * Feature/unfeature a calendar event.
     *
     * @param int           $intId
     * @param bool          $blnVisible
     * @param DataContainer $dc
     *
     * @throws \Contao\CoreBundle\Exception\AccessDeniedException
     */
    public function toggleFeatured($intId, $blnVisible, DataContainer $dc = null)
    {
        $user = BackendUser::getInstance();
        $db = Database::getInstance();

        // Check permissions to edit
        Input::setGet('id', $intId);
        Input::setGet('act', 'feature');
        $this->checkPermission();

        // Check permissions to feature
        if (!$user->hasAccess('tl_calendar_events::featured', 'alexf')) {
            throw new AccessDeniedException('Not enough permissions to feature/unfeature calendar_event ID '.$intId.'.');
        }

        $objVersions = new Versions('tl_calendar_events', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['featured']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['featured']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $obj = System::importStatic($callback[0]);
                    $blnVisible = $obj->{$callback[1]}($blnVisible, $dc);
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $this);
                }
            }
        }

        // Update the database
        $db->prepare('UPDATE tl_calendar_events SET tstamp='.time().", featured='".($blnVisible ? 1 : '')."' WHERE id=?")
            ->execute($intId);

        $objVersions->create();
    }

    /**
     * Check permissions to edit table tl_calendar_events.
     *
     * @throws \Contao\CoreBundle\Exception\AccessDeniedException
     */
    public function checkPermission()
    {
        $user = BackendUser::getInstance();
        $db = Database::getInstance();
        $bundles = System::getContainer()->getParameter('kernel.bundles');

        // HOOK: comments extension required
        if (!isset($bundles['ContaoCommentsBundle'])) {
            $key = array_search('allowComments', $GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields']);
            unset($GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields'][$key]);
        }

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
            case 'feature':
                $objCalendar = $db->prepare('SELECT pid FROM tl_calendar_events WHERE id=?')
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

                $objCalendar = $db->prepare('SELECT id FROM tl_calendar_events WHERE pid=?')
                    ->execute($id);

                if ($objCalendar->numRows < 1) {
                    throw new AccessDeniedException('Invalid calendar ID '.$id.'.');
                }

                /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
                $objSession = System::getContainer()->get('session');

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
}
