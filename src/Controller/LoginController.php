<?php

namespace App\Controller;

use App\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement de la connexion
            $data = $form->getData();
            // TODO: Implémenter la logique d'authentification
            
            $this->addFlash('success', 'Connexion réussie !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('login/index.html.twig', [
            'loginForm' => $form->createView(),
        ]);
    }
}
