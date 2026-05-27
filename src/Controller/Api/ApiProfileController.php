<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/profile')]
final class ApiProfileController extends AbstractController
{
    #[Route('/avatar', name: 'api_profile_avatar_upload', methods: ['POST'])]
    public function uploadAvatar(
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): JsonResponse {
        $file = $request->files->get('avatar');

        if (!$file) {
            return $this->json(['success' => false, 'message' => 'No file provided.'], 400);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            return $this->json(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG and WebP are allowed.'], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['success' => false, 'message' => 'File too large. Maximum size is 5MB.'], 400);
        }

        // Delete old avatar file if it exists
        $oldAvatar = $user->getAvatar();
        if ($oldAvatar) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $oldAvatar;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $newFilename);

        $avatarPath = '/uploads/avatars/' . $newFilename;
        $user->setAvatar($avatarPath);

        $em->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'avatar' => $avatarPath,
            ],
        ]);
    }

    #[Route('/avatar', name: 'api_profile_avatar_delete', methods: ['DELETE'])]
    public function deleteAvatar(
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
    ): JsonResponse {
        $oldAvatar = $user->getAvatar();
        if ($oldAvatar) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $oldAvatar;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            $user->setAvatar(null);
            $em->flush();
        }

        return $this->json(['success' => true, 'message' => 'Avatar removed.']);
    }
}
