<?php

namespace HeimrichHannot\EventsBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(KernelEvents::REQUEST)]
readonly class RequestListener
{
    public function __construct(
        private ScopeMatcher $scopeMatcher
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($this->scopeMatcher->isBackendRequest($request))
        {
            $this->addBackendAssets();
        }
        // else
        // {
        //     $this->addFrontendAssets();
        // }
    }

    protected function addBackendAssets(): void
    {
        $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotevents/css/contao-events-bundle.be.css';
    }

    // protected function addFrontendAssets(): void
    // {
    //     // nothing to do here
    // }
}