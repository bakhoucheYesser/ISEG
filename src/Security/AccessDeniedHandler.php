<?php
// src/Security/AccessDeniedHandler.php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private Environment $twig
    ) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $content = $this->twig->render('security/access_denied.html.twig', [
            'exception' => $accessDeniedException,
            'request' => $request
        ]);

        return new Response($content, 403);
    }
}
