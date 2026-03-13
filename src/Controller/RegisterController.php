<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(Request $request, Connection $connection, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Créer un objet User temporaire pour le hashing du mot de passe
            $user = new User();
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            
            // Obtenir la connexion PDO native
            $pdo = $connection->getNativeConnection();
            
            // Préparer la requête SQL avec PDO
            $sql = "INSERT INTO user (name, email, password, role) VALUES (:name, :email, :password, :role)";
            $stmt = $pdo->prepare($sql);
            
            // Exécuter la requête avec les paramètres
            $stmt->execute([
                ':name' => $data['username'],
                ':email' => $data['email'],
                ':password' => $hashedPassword,
                ':role' => 'ROLE_USER'
            ]);

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('register/index.html.twig', [
            'registerForm' => $form->createView(),
        ]);
    }
}
