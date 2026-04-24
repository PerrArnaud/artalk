<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
final class ApiAuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        // Intercepted by the security firewall's json_login handler
        throw new \LogicException('This should not be reached.');
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['password'], $data['name'])) {
            return $this->json(['success' => false, 'message' => 'Missing required fields: email, password, name.'], 400);
        }

        $email    = trim((string) $data['email']);
        $name     = trim((string) $data['name']);
        $password = (string) $data['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'message' => 'Invalid email address.'], 400);
        }

        if ($name === '') {
            return $this->json(['success' => false, 'message' => 'Name cannot be empty.'], 400);
        }

        if (strlen($password) < 6) {
            return $this->json(['success' => false, 'message' => 'Password must be at least 6 characters.'], 400);
        }

        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return $this->json(['success' => false, 'message' => 'Email already in use.'], 409);
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRole('ROLE_USER');

        $em->persist($user);
        $em->flush();

        $token = $jwtManager->create($user);

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user'  => [
                    'id'    => $user->getId(),
                    'email' => $user->getEmail(),
                    'name'  => $user->getName(),
                    'role'  => $user->getRole(),
                ],
            ],
        ], 201);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
                'name'  => $user->getName(),
                'role'  => $user->getRole(),
            ],
        ]);
    }
}
