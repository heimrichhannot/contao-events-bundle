<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Item;

use Contao\CommentsModel;
use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\ModuleLoader;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\ListBundle\Manager\ListManagerInterface;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Content\ContentUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;

trait EventsItemTrait
{
    /**
     * @return string
     */
    public function getHref(): ?string
    {
        return $this->getDetailsUrl();
    }

    /**
     * @return string
     */
    public function getLink(): ?string
    {
        return $this->title;
    }

    public function getLocationLabel()
    {
        return $GLOBALS['TL_LANG']['MSC']['location'];
    }

    /**
     * Compile the headline link.
     *
     * @return string
     */
    public function getLinkHeadline(): ?string
    {
        // Internal link
        if ('external' !== $this->source) {
            return sprintf('<a href="%s" title="%s" itemprop="url">%s%s</a>', $this->getDetailsUrl(), StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $this->title), true), $this->title, '');
        }

        // External link
        return sprintf('<a href="%s" title="%s"%s itemprop="url">%s</a>', $this->getExternalUrl(), \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['open'], $this->getExternalUrl())), ($this->target ? ' target="_blank"' : ''), $this->title);
    }

    /**
     * Compile the more link.
     *
     * @return string
     */
    public function getMore(): ?string
    {
        return $GLOBALS['TL_LANG']['MSC']['more'];
    }

    /**
     * Get the calendar data.
     *
     * @return array
     */
    public function getCalendar(): ?array
    {
        if (null === ($calendar = System::getContainer()->get(ModelUtil::class)->findModelInstanceByPk('tl_calendar', $this->pid))) {
            return null;
        }

        return $calendar->row();
    }

    public function getReadMore()
    {
        return StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $this->title));
    }

    /**
     * Get details url and add archive.
     */
    public function getDetailsUrlWithArchive(): ?string
    {
        $url = $this->getDetailsUrl();

        // Add the current calendar parameter
        if (System::getContainer()->get(Request::class)->query->has('month')) {
            $url .= '?month='.System::getContainer()->get(Request::class)->query->get('month');
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDetailsUrl(bool $external = true, bool $isCanonical = false): ?string
    {
        switch ($this->source) {
            // Link to an external page
            case 'external':
                return $external ? $this->getExternalUrl() : '';
            // Link to an internal page
            case 'internal':
                return $this->getInternalUrl();
            // Link to an article
            case 'article':
                return $this->getArticleUrl();
        }

        return $this->getDefaultUrl($isCanonical);
    }

    /**
     * Get the external url source = 'external'.
     */
    public function getExternalUrl(): ?string
    {
        if ('mailto:' == substr($this->url, 0, 7)) {
            $url = StringUtil::encodeEmail($this->url);
        } else {
            $url = ampersand($this->url);
        }

        return $url ?? null;
    }

    /**
     * Get the internal url source = 'internal'.
     */
    public function getInternalUrl(): ?string
    {
        $url = '';

        if (null !== ($target = System::getContainer()->get(ModelUtil::class)->findModelInstanceByPk('tl_page', $this->jumpTo))) {
            $url = ampersand($target->getFrontendUrl());
        }

        return $url ?? null;
    }

    /**
     * Get the article url source = 'article'.
     */
    public function getArticleUrl(): ?string
    {
        $url = '';

        $modelUtil = System::getContainer()->get(ModelUtil::class);

        if (null !== ($article = $modelUtil->findModelInstanceByPk('tl_article', $this->articleId, ['eager' => true])) &&
            null !== ($parentPage = $modelUtil->findModelInstanceByPk('tl_page', $article->pid))) {
            $url = ampersand($parentPage->getFrontendUrl('/articles/'.($article->alias ?: $article->id)));
        }

        return $url ?? null;
    }

    /**
     * Get the default url source = 'default'.
     */
    public function getDefaultUrl(bool $isCanonical = false): ?string
    {
        if (!$isCanonical && $this->getManager() instanceof ListManagerInterface && $this->getManager()->getListConfig()->addDetails) {
            $url = $this->_detailsUrl;
        } else {
            $modelUtil = System::getContainer()->get(ModelUtil::class);

            if (null === ($archive = $modelUtil->findModelInstanceByPk('tl_calendar', $this->pid))) {
                return null;
            }

            if (null === ($page = $modelUtil->findModelInstanceByPk('tl_page', $archive->jumpTo))) {
                $url = ampersand(System::getContainer()->get('request_stack')->getCurrentRequest()->getRequestUri(), true);
            } else {
                $url = ampersand($page->getFrontendUrl((Config::get('useAutoItem') ? '/' : '/items/').($this->alias ?: $this->id)));
            }
        }

        return $url ?? null;
    }

    /**
     * Get the author.
     */
    public function getAuthor(): ?string
    {
        if (null !== ($user = System::getContainer()->get(ModelUtil::class)->findModelInstanceByPk('tl_user', $this->author))) {
            return $GLOBALS['TL_LANG']['MSC']['by'].' '.$user->name;
        }

        return null;
    }

    /**
     * Compile comment count.
     */
    public function getCommentCount(): ?string
    {
        $total = $this->getNumberOfComments();

        return $total > 0 ? sprintf($GLOBALS['TL_LANG']['MSC']['commentCount'], $total) : '';
    }

    /**
     * Get number of comments.
     */
    public function getNumberOfComments(): ?int
    {
        if ($this->noComments || !\in_array('comments', ModuleLoader::getActive(), true) || 'default' != $this->source) {
            return null;
        }

        $total = CommentsModel::countPublishedBySourceAndParent($this->getDataContainer(), $this->id);

        return $total;
    }

    /**
     * Get date timestamp.
     *
     * @return string
     */
    public function getTimestamp(): ?string
    {
        return $this->startTime;
    }

    /**
     * Get date DateTime.
     *
     * @return string
     */
    public function getDatetime(): ?string
    {
        $start = $this->startTime;
        $end = $this->endTime;

        return $this->getRawValue('addTime') ? date('Y-m-d\TH:i:sP', $start) : date('Y-m-d', $end);
    }

    /**
     * Get formatted meta date.
     *
     * @return string
     */
    public function getDate(): ?string
    {
        global $objPage;

        $start = $this->startTime;
        $end = $this->endTime;

        $date = \Date::parse($objPage->dateFormat, $start);
        $span = \Calendar::calculateSpan($start, $end);

        if ($span > 0) {
            $date = \Date::parse($objPage->dateFormat, $start).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].\Date::parse($objPage->dateFormat, $end);
        }

        return $date;
    }

    /**
     * Get formatted meta time.
     *
     * @return string
     */
    public function getTime(): ?string
    {
        global $objPage;

        $start = $this->startTime;
        $end = $this->endTime;

        $time = '';

        $span = \Calendar::calculateSpan($start, $end);

        if ($span > 0) {
            return '';
        }

        if ($this->getRawValue('addTime')) {
            if ($start == $end) {
                $time = \Date::parse($objPage->timeFormat, $start);
            } else {
                $time = \Date::parse($objPage->timeFormat, $start).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].\Date::parse($objPage->timeFormat, $end);
            }
        }

        return $time;
    }

    public function getTarget()
    {
        if ('external' === $this->source && $this->target) {
            return ' target="_blank"';
        }

        return $this->target;
    }

    /**
     * Get all enclosures.
     */
    public function getEnclosures(): ?array
    {
        if (true === $this->getRawValue('addEnclosure')) {
            return null;
        }

        $template = new \stdClass();
        Controller::addEnclosuresToTemplate($template, $this->getRaw());

        return $template->enclosure;
    }

    /**
     * Compile the text.
     *
     * @return string
     */
    public function getDetails(): ?string
    {
        return System::getContainer()->get(ContentUtil::class)->getMultilingualElements($this->getRawValue('id'), $this->getDataContainer());
    }

    /**
     * Check if text is available.
     */
    public function getHasDetails(): bool
    {
        // Display the "read more" button for external/article links
        if ('default' !== $this->source) {
            return true;
        }

        /** @var ContentModel $adapter */
        $adapter = $this->getManager()->getFramework()->getAdapter(ContentModel::class);

        return $adapter->countPublishedByPidAndTable($this->id, $this->getDataContainer()) > 0;
    }

    /**
     * Check if a teaser text is available.
     */
    public function hasTeaser(): bool
    {
        return '' !== $this->teaser;
    }

    /**
     * Compile the teaser text.
     *
     * @return string
     */
    public function getTeaser(): ?string
    {
        return StringUtil::encodeEmail(StringUtil::toHtml5($this->teaser));
    }
}
