<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\MOTWRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
final class ApiCommentController extends AbstractController
{
    #[Route('/comments', name: 'api_comment_create', methods: ['POST'])]
    public function create(
        Request $request,
        MOTWRepository $motwRepository,
        CommentRepository $commentRepository,
        EntityManagerInterface $em,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['motwSlug'], $data['content'])) {
            return $this->json(['success' => false, 'message' => 'Missing required fields: motwSlug, content.'], 400);
        }

        $content = trim((string) $data['content']);
        if ($content === '') {
            return $this->json(['success' => false, 'message' => 'Content cannot be empty.'], 400);
        }

        $motw = $motwRepository->findOneBy(['slug' => (string) $data['motwSlug']]);
        if (!$motw) {
            return $this->json(['success' => false, 'message' => 'MOTW not found.'], 404);
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setMOTW($motw);
        $comment->setUser($user);

        if (!empty($data['parentCommentId'])) {
            $parent = $commentRepository->find((int) $data['parentCommentId']);
            if (!$parent || $parent->getMOTW() !== $motw) {
                return $this->json(['success' => false, 'message' => 'Parent comment not found.'], 404);
            }
            $comment->setComment($parent);
        }

        $em->persist($comment);
        $em->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'id'              => $comment->getId(),
                'content'         => $comment->getContent(),
                'createdAt'       => $comment->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                'validated'       => $comment->isValidated(),
                'user'            => [
                    'id'   => $user->getId(),
                    'name' => $user->getName(),
                ],
                'motwSlug'        => $motw->getSlug(),
                'parentCommentId' => $comment->getComment()?->getId(),
                'replies'         => [],
            ],
        ], 201);
    }
}
