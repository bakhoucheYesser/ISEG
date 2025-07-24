<?php

// src/Controller/SecurityController.php
namespace App\Controller;

use App\Entity\User;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private SecurityService $securityService
    ) {}

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Si déjà connecté, rediriger vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        // Récupérer les erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Vérifier l'IP si un utilisateur essaie de se connecter
        $clientIp = $request->getClientIp();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'client_ip' => $clientIp
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut être vide - elle sera interceptée par la clé de logout dans security.yaml
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
