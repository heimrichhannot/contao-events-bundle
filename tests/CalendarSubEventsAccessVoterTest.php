<?php

namespace HeimrichHannot\EventsBundle\Test;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use HeimrichHannot\EventsBundle\Security\CalendarSubEventsAccessVoter;
use HeimrichHannot\EventsBundle\Security\CalendarSubEventsContentVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CalendarSubEventsAccessVoterTest extends TestCase
{
    public function testMovingChecksBothParentEvents(): void
    {
        $db = $this->createMock(Connection::class);
        $parents = [];
        $db->expects(self::exactly(2))->method('fetchAssociative')->willReturnCallback(function ($sql, $params) use (&$parents) {
            $parents[] = $params[0];
            return ['id' => $params[0], 'pid' => 10];
        });
        $decisions = $this->createMock(AccessDecisionManagerInterface::class);
        $decisions->expects(self::exactly(2))->method('decide')->willReturn(true, false);
        $voter = new CalendarSubEventsAccessVoter($db, $decisions);
        $result = $voter->vote($this->createMock(TokenInterface::class), new UpdateAction('tl_calendar_sub_events', ['id' => 1, 'pid' => 2], ['pid' => 3]), [ContaoCorePermissions::DC_PREFIX.'tl_calendar_sub_events']);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
        self::assertSame([2, 3], $parents);
    }

    public function testCreatingUnderMissingParentIsDenied(): void
    {
        $db = $this->createMock(Connection::class);
        $db->method('fetchAssociative')->willReturn(false);
        $decisions = $this->createMock(AccessDecisionManagerInterface::class);
        $decisions->expects(self::never())->method('decide');
        $voter = new CalendarSubEventsAccessVoter($db, $decisions);
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->createMock(TokenInterface::class), new CreateAction('tl_calendar_sub_events', ['pid' => 999]), [ContaoCorePermissions::DC_PREFIX.'tl_calendar_sub_events']));
    }

    public function testContentRequiresAccessToItsSubEvent(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::once())->method('fetchAssociative')->with('SELECT * FROM tl_calendar_sub_events WHERE id=?', [12])->willReturn(['id' => 12, 'pid' => 5]);
        $decisions = $this->createMock(AccessDecisionManagerInterface::class);
        $decisions->expects(self::once())->method('decide')->willReturn(false);
        $voter = new CalendarSubEventsContentVoter($db, $decisions);
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->createMock(TokenInterface::class), new ReadAction('tl_content', ['id' => 3, 'pid' => 12, 'ptable' => 'tl_calendar_sub_events']), [ContaoCorePermissions::DC_PREFIX.'tl_content']));
    }

    public function testUnrelatedContentDoesNotQuerySubEvents(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::never())->method('fetchAssociative');
        $voter = new CalendarSubEventsContentVoter($db, $this->createMock(AccessDecisionManagerInterface::class));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($this->createMock(TokenInterface::class), new ReadAction('tl_content', ['id' => 3, 'pid' => 12, 'ptable' => 'tl_article']), [ContaoCorePermissions::DC_PREFIX.'tl_content']));
    }
}
