<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BeforeGetSubEventsEvent extends Event
{
    /**
     * @var string
     */
    protected $table;
    /**
     * @var array
     */
    protected $columns;
    /**
     * @var array
     */
    protected $values;
    /**
     * @var array
     */
    protected $options;

    public function __construct(string $table, array $columns, array $values, array $options)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->values = $values;
        $this->options = $options;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
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
