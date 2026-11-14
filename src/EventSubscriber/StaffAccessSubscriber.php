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

            // Allowed paths for staff
            if (
                str_starts_with($path, '/staff') ||
                str_starts_with($path, '/profile') ||
                str_starts_with($path, '/logout') ||
                str_starts_with($path, '/_') ||
                preg_match('#\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?)$#i', $path)
            ) {
                return;
            }

            // Redirect any customer/public page access to the Staff Portal
            if ($request->hasSession()) {
                $session = $request->getSession();
                if (method_exists($session, 'getFlashBag')) {
                    $session->getFlashBag()->add('warning', 'Staff accounts are restricted to the Staff Portal.');
                }
            }

            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_staff_products')));
        }
    }
}
