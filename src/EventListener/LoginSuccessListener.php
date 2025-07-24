<?php

namespace App\EventListener;

use App\Service\SecurityService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __construct(
        private SecurityService $securityService,
        private RequestStack $requestStack
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $this->requestStack->getCurrentRequest();

        if ($user && $request) {
            $clientIp = $request->getClientIp();

            // Vérifier l'IP autorisée
            if (!$this->securityService->validateIpAccess($user, $clientIp)) {
                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException(
                    'Accès refusé depuis cette adresse IP: ' . $clientIp
                );
            }

            // Créer une nouvelle session
            $this->securityService->createSession($user, $clientIp);

            // Mettre à jour l'activité
            $user->updateLastActivity();
        }
    }
}
