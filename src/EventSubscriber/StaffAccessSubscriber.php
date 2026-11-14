<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StaffAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 0 runs after the security firewall (priority 8)
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Check if user is staff and not admin
        if ($this->security->isGranted('ROLE_STAFF') && !$this->security->isGranted('ROLE_ADMIN')) {
            $request = $event->getRequest();
            $path = $request->getPathInfo();

            // STRICT RUBRIC: Staff must NEVER access admin pages
            if (str_starts_with($path, '/admin')) {
                if ($request->hasSession()) {
                    $session = $request->getSession();
                    if (method_exists($session, 'getFlashBag')) {
                        $session->getFlashBag()->add('danger', 'Access Denied: Staff members cannot access the Admin Console.');
                    }
                }
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_staff_dashboard')));
                return;
            }

            // Staff can access storefront (/shop, /product, /, /about, /contact, /cart, /staff, /profile, etc.)
            return;
        }
    }
}
