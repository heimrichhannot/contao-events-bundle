<?php

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use HeimrichHannot\UtilsBundle\Util\Utils;

readonly class Container
{
    public function __construct(
        private Connection $connection,
        private Utils      $utils,
    ) {}

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[AsCallback(CalendarEventsContainer::TABLE, 'config.onsubmit')]
    public function onSubmit_dateAdded(DataContainer $dc): void
    {
        if (!$dc->table || !$dc->id) {
            return;
        }

        $this->initDateAdded($dc->table, $dc->id);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[AsCallback(CalendarEventsContainer::TABLE, 'config.oncopy')]
    public function onCopy_dateAdded(int $insertId, DataContainer $dc): void
    {
        if (!$dc->table || !$insertId) {
            return;
        }

        $this->initDateAdded($dc->table, $insertId);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function initDateAdded(string $table, int $id): void
    {
        if (!$table || !$id) {
            return;
        }

        if (!$model = $this->utils->model()->findModelInstanceByPk($table, $id)) {
            return;
        }

        if ($model->dateAdded) {
            return;
        }

        $this->connection->update($table, ['dateAdded' => time()], ['id' => $id]);
    }
}