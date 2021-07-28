<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Event;

class BeforeGetSubEventsEvent
{
    public const NAME = 'huh.events.before_get_sub_events_event';
    protected $columns;
    protected $values;
    protected $options;

    public function __construct(array $columns, array $values, array $options)
    {
        $this->columns = $columns;
        $this->values = $values;
        $this->options = $options;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
