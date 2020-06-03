<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ContentListener implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    /**
     * Check permissions to edit table tl_content.
     */
    public function checkPermission()
    {
        $user = BackendUser::getInstance();
        $db = Database::getInstance();

        if ($user->isAdmin) {
            return;
        }

        // Set the root IDs
        if (empty($user->calendars) || !\is_array($user->calendars)) {
            $root = [0];
        } else {
            $root = $user->calendars;
        }

        // Check the current action
        switch (Input::get('act')) {
            case 'paste':
                // Allow
                break;

            case '': // empty
            case 'create':
            case 'select':
                // Check access to the news item
                $this->checkAccessToElement(CURRENT_ID, $root, true);

                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                // Check access to the parent element if a content element is moved
                if ('cutAll' == Input::get('act') || 'copyAll' == Input::get('act')) {
                    $this->checkAccessToElement(Input::get('pid'), $root, (2 == Input::get('mode')));
                }

                $objCes = $db->prepare("SELECT id FROM tl_content WHERE ptable='tl_calendar_events' AND pid=?")
                    ->execute(CURRENT_ID);

                /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
                $objSession = System::getContainer()->get('session');

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
                $objSession->replace($session);

                break;

            case 'cut':
            case 'copy':
                // Check access to the parent element if a content element is moved
                $this->checkAccessToElement(Input::get('pid'), $root, (2 == Input::get('mode')));
            // no break STATEMENT HERE

            default:
                // Check access to the content element
                $this->checkAccessToElement(Input::get('id'), $root);

                break;
        }
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
        $user = BackendUser::getInstance();

        if (\strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (1 == Input::get('state')), (@func_get_arg(12) ?: null));
            Controller::redirect(System::getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$user->hasAccess('tl_content::invisible', 'alexf')) {
            return '';
        }

        $href .= '&amp;id='.Input::get('id').'&amp;tid='.$row['id'].'&amp;state='.$row['invisible'];

        if ($row['invisible']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.Controller::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['invisible'] ? 0 : 1).'"').'</a> ';
    }

    /**
     * Toggle the visibility of an element.
     *
     * @param int           $intId
     * @param bool          $blnVisible
     * @param DataContainer $dc
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
        if (\is_array($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $obj = System::importStatic($callback[0]);
                    $obj->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$user->hasAccess('tl_content::invisible', 'alexf')) {
            throw new AccessDeniedException('Not enough permissions to publish/unpublish content element ID '.$intId.'.');
        }

        // Set the current record
        if ($dc) {
            $objRow = $db->prepare('SELECT * FROM tl_content WHERE id=?')
                ->limit(1)
                ->execute($intId);

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Versions('tl_content', $intId);
        $objVersions->initialize();

        // Reverse the logic (elements have invisible=1)
        $blnVisible = !$blnVisible;

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_content']['fields']['invisible']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_content']['fields']['invisible']['save_callback'] as $callback) {
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
        $db->prepare("UPDATE tl_content SET tstamp=$time, invisible='".($blnVisible ? '1' : '')."' WHERE id=?")
            ->execute($intId);

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->invisible = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'] as $callback) {
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

    /**
     * Check access to a particular content element.
     *
     * @param int   $id
     * @param array $root
     * @param bool  $blnIsPid
     *
     * @throws \Contao\CoreBundle\Exception\AccessDeniedException
     */
    protected function checkAccessToElement($id, $root, $blnIsPid = false)
    {
        $db = Database::getInstance();

        if ($blnIsPid) {
            $objCalendar = $db->prepare('SELECT a.id, n.id AS nid FROM tl_calendar_events n, tl_calendar a WHERE n.id=? AND n.pid=a.id')
                ->limit(1)
                ->execute($id);
        } else {
            $objCalendar = $db->prepare('SELECT a.id, n.id AS nid FROM tl_content c, tl_calendar_events n, tl_calendar a WHERE c.id=? AND c.pid=n.id AND n.pid=a.id')
                ->limit(1)
                ->execute($id);
        }

        // Invalid ID
        if ($objCalendar->numRows < 1) {
            throw new AccessDeniedException('Invalid event content element ID '.$id.'.');
        }

        // The calendar is not mounted
        if (!\in_array($objCalendar->id, $root)) {
            throw new AccessDeniedException('Not enough permissions to modify article ID '.$objCalendar->nid.' in calendar ID '.$objCalendar->id.'.');
        }
    }
}
