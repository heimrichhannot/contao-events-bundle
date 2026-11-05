<?php

namespace HeimrichHannot\EventsBundle\Security;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class CalendarSubEventsAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private Connection $connection, private AccessDecisionManagerInterface $decisionManager)
    {
    }

    protected function getTable(): string
    {
        return 'tl_calendar_sub_events';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $parents = [$action instanceof CreateAction ? $action->getNewPid() : $action->getCurrentPid()];
        if ($action instanceof UpdateAction && null !== $action->getNewPid()) {
            $parents[] = $action->getNewPid();
        }

        foreach (array_unique($parents) as $id) {
            $event = $this->connection->fetchAssociative('SELECT * FROM tl_calendar_events WHERE id=?', [(int) $id]);
            if (!$event || !$this->decisionManager->decide($token, [ContaoCorePermissions::DC_PREFIX.'tl_calendar_events'], new UpdateAction('tl_calendar_events', $event, []))) {
                return false;
            }
        }

        return true;
    }
}
