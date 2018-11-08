<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
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

trait EventsItemTrait
{
    /**
     * URL cache array.
     *
     * @var array
     */
    private static $urlCache = [];

    /**
     * @return string
     */
    public function getHref(): string
    {
        return $this->getDetailsUrl();
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->title;
    }

    /**
     * Compile the headline link.
     *
     * @return string
     */
    public function getLinkHeadline(): string
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
    public function getMore(): string
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
        if (null === ($calendar = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_calendar', $this->pid))) {
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
     *
     * @return null|string
     */
    public function getDetailsUrlWithArchive(): ?string
    {
        $url = $this->getDetailsUrl();

        // Add the current calendar parameter
        if (System::getContainer()->get('huh.request')->query->has('month')) {
            $url .= '?month='.System::getContainer()->get('huh.request')->query->get('month');
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDetailsUrl(bool $external = true): string
    {
        $cacheKey = 'id_'.$this->id;

        // Load the URL from cache
        if (isset(self::$urlCache[$cacheKey])) {
            return self::$urlCache[$cacheKey];
        }

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

        return $this->getDefaultUrl();
    }

    /**
     * Get the external url source = 'external'.
     *
     * @return null|string
     */
    public function getExternalUrl(): ?string
    {
        $cacheKey = 'id_'.$this->id;

        // Load the URL from cache
        if (isset(self::$urlCache[$cacheKey])) {
            return self::$urlCache[$cacheKey];
        }

        if ('mailto:' == substr($this->url, 0, 7)) {
            self::$urlCache[$cacheKey] = StringUtil::encodeEmail($this->url);
        } else {
            self::$urlCache[$cacheKey] = ampersand($this->url);
        }

        return self::$urlCache[$cacheKey] ?? null;
    }

    /**
     * Get the internal url source = 'internal'.
     *
     * @return null|string
     */
    public function getInternalUrl(): ?string
    {
        $cacheKey = 'id_'.$this->id;

        // Load the URL from cache
        if (isset(self::$urlCache[$cacheKey])) {
            return self::$urlCache[$cacheKey];
        }

        if (null !== ($target = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_page', $this->jumpTo))) {
            self::$urlCache[$cacheKey] = ampersand($target->getFrontendUrl());
        }

        return self::$urlCache[$cacheKey] ?? null;
    }

    /**
     * Get the article url source = 'article'.
     *
     * @return null|string
     */
    public function getArticleUrl(): ?string
    {
        $cacheKey = 'id_'.$this->id;

        // Load the URL from cache
        if (isset(self::$urlCache[$cacheKey])) {
            return self::$urlCache[$cacheKey];
        }

        $modelUtil = System::getContainer()->get('huh.utils.model');

        if (null !== ($article = $modelUtil->findModelInstanceByPk('tl_article', $this->articleId, ['eager' => true])) &&
            null !== ($parentPage = $modelUtil->findModelInstanceByPk('tl_page', $article->pid))) {
            self::$urlCache[$cacheKey] = ampersand($parentPage->getFrontendUrl('/articles/'.($article->alias ?: $article->id)));
        }

        return self::$urlCache[$cacheKey] ?? null;
    }

    /**
     * Get the default url source = 'default'.
     *
     * @return null|string
     */
    public function getDefaultUrl(): ?string
    {
        $cacheKey = 'id_'.$this->id;

        // Load the URL from cache
        if (isset(self::$urlCache[$cacheKey])) {
            return self::$urlCache[$cacheKey];
        }

        $modelUtil = System::getContainer()->get('huh.utils.model');

        if (null === ($archive = $modelUtil->findModelInstanceByPk('tl_calendar', $this->pid))) {
            return null;
        }

        if (null === ($page = $modelUtil->findModelInstanceByPk('tl_page', $archive->jumpTo))) {
            self::$urlCache[$cacheKey] = ampersand(System::getContainer()->get('request_stack')->getCurrentRequest()->getRequestUri(), true);
        } else {
            self::$urlCache[$cacheKey] = ampersand($page->getFrontendUrl((Config::get('useAutoItem') ? '/' : '/items/').($this->alias ?: $this->id)));
        }

        return self::$urlCache[$cacheKey] ?? null;
    }

    /**
     * Get date DateTime.
     *
     * @return string
     */
    public function getDatetime(): string
    {
        $start = $this->startTime;
        $end = $this->endTime;

        return $this->getRawValue('addTime') ? date('Y-m-d\TH:i:sP', $start) : date('Y-m-d', $end);
    }

    /**
     * Get date timestamp.
     *
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->startTime;
    }

    /**
     * Get the author.
     *
     * @return null|string
     */
    public function getAuthor(): ?string
    {
        if (null !== ($user = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_user', $this->author))) {
            return $GLOBALS['TL_LANG']['MSC']['by'].' '.$user->name;
        }

        return null;
    }

    /**
     * Compile comment count.
     *
     * @return null|string
     */
    public function getCommentCount(): ?string
    {
        $total = $this->getNumberOfComments();

        return $total > 0 ? sprintf($GLOBALS['TL_LANG']['MSC']['commentCount'], $total) : '';
    }

    /**
     * Get number of comments.
     *
     * @return int|null
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
     * Get formatted meta date.
     *
     * @return string
     */
    public function getDate(): string
    {
        global $objPage;

        $start = $this->startTime;
        $end = $this->endTime;

        $date = \Date::parse($objPage->dateFormat, $start);
        $span = \Calendar::calculateSpan($start, $end);

        if ($span > 0) {
            $date = \Date::parse($objPage->dateFormat, $start).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].\Date::parse($objPage->dateFormat, $end);
        }

        if ($this->getRawValue('addTime')) {
            if ($span > 0) {
                $date = \Date::parse($objPage->dateFormat, $start).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].\Date::parse($objPage->dateFormat, $end);
            }
        }

        return $date;
    }

    /**
     * Get formatted meta time.
     *
     * @return string
     */
    public function getTime(): string
    {
        global $objPage;

        $start = $this->startTime;
        $end = $this->endTime;

        $time = '';

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
     *
     * @return array|null
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
    public function getDetails(): string
    {
        $strText = '';

        /**
         * @var ContentModel
         */
        $adapter = $this->getManager()->getFramework()->getAdapter(ContentModel::class);

        if (null !== ($elements = $adapter->findPublishedByPidAndTable($this->id, $this->getDataContainer()))) {
            foreach ($elements as $element) {
                try {
                    $strText .= Controller::getContentElement($element->id);
                } catch (\ErrorException $e) {
                }
            }
        }

        return $strText;
    }

    /**
     * Check if text is available.
     *
     * @return bool
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
     *
     * @return bool
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
    public function getTeaser(): string
    {
        return StringUtil::encodeEmail(StringUtil::toHtml5($this->teaser));
    }
}
