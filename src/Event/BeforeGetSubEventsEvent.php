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

    public function __construct(array $columns, array $values)
    {
        $this->columns = $columns;
        $this->values = $values;
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
}
