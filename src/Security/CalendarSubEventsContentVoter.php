<?php

namespace HeimrichHannot\EventsBundle\Security;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class CalendarSubEventsContentVoter extends AbstractDynamicPtableVoter
{
    public function __construct(private Connection $connection, private AccessDecisionManagerInterface $decisionManager)
    {
        parent::__construct($connection);
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
    {
        if ('tl_calendar_sub_events' !== $table) {
            return true;
        }

        $event = $this->connection->fetchAssociative('SELECT * FROM tl_calendar_sub_events WHERE id=?', [$id]);

        return $event && $this->decisionManager->decide($token, [ContaoCorePermissions::DC_PREFIX.$table], new UpdateAction($table, $event, []));
    }
}
