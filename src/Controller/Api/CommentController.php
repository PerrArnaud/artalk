<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\MOTW;
use App\Entity\User;
use App\Event\CommentCreatedEvent;
use App\Repository\CommentRepository;
use App\Repository\MOTWRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_')]
class CommentController extends AbstractController
{
    #[Route('/motw/{slug}/comments', name: 'comments_list', methods: ['GET'])]
    public function list(
        string $slug,
        Request $request,
        MOTWRepository $motwRepository,
        CommentRepository $commentRepository,
        ReportRepository $reportRepository,
        #[CurrentUser] ?User $currentUser = null
    ): JsonResponse {
        $motw = $motwRepository->findOneBy(['slug' => $slug]);

        if (!$motw) {
            return $this->json([
                'success' => false,
                'message' => 'Artwork not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Get top-level comments (no parent) that are validated and not hidden
        $queryBuilder = $commentRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.Reply', 'r')
            ->leftJoin('r.user', 'ru')
            ->where('c.mOTW = :motw')
            ->andWhere('c.comment IS NULL')
            ->andWhere('c.validated = true')
            ->andWhere('c.hidden = false')
            ->setParameter('motw', $motw)
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $comments = $queryBuilder->getQuery()->getResult();

        // Get total count
        $totalCount = $commentRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.mOTW = :motw')
            ->andWhere('c.comment IS NULL')
            ->andWhere('c.validated = true')
            ->andWhere('c.hidden = false')
            ->setParameter('motw', $motw)
            ->getQuery()
            ->getSingleScalarResult();

        // Collect all comment/reply IDs to look up which ones the user has already reported
        $allIds = [];
        foreach ($comments as $comment) {
            $allIds[] = $comment->getId();
            foreach ($comment->getReply() as $reply) {
                $allIds[] = $reply->getId();
            }
        }
        $reportedIds = $currentUser
            ? $reportRepository->findReportedCommentIdsByUser($currentUser, $allIds)
            : [];
        $reportedIdSet = array_flip($reportedIds);

        $data = [];
        foreach ($comments as $comment) {
            $commentData = $this->serializeComment($comment, $reportedIdSet);

            // Add replies (1 level deep only), excluding hidden ones
            $replies = [];
            foreach ($comment->getReply() as $reply) {
                if ($reply->isValidated() && !$reply->isHidden()) {
                    $replies[] = $this->serializeComment($reply, $reportedIdSet);
                }
            }
            $commentData['replies'] = $replies;

            $data[] = $commentData;
        }

        return $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $limit)
            ]
        ]);
    }

    #[Route('/comments', name: 'comments_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        MOTWRepository $motwRepository,
        CommentRepository $commentRepository,
        EventDispatcherInterface $eventDispatcher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validate input
        if (!isset($data['motwSlug']) || !isset($data['content'])) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required fields: motwSlug, content'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Find MOTW
        $motw = $motwRepository->findOneBy(['slug' => $data['motwSlug']]);
        if (!$motw) {
            return $this->json([
                'success' => false,
                'message' => 'Artwork not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Create comment
        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setMOTW($motw);
        $comment->setUser($user);
        $comment->setValidated(true); // Auto-validate for now

        // Handle parent comment (for replies)
        if (isset($data['parentCommentId'])) {
            $parentComment = $commentRepository->find($data['parentCommentId']);

            if (!$parentComment) {
                return $this->json([
                    'success' => false,
                    'message' => 'Parent comment not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Check that parent comment doesn't already have a parent (max 1 level)
            if ($parentComment->getComment() !== null) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cannot reply to a reply. Maximum nesting level is 1.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check that parent comment belongs to the same MOTW
            if ($parentComment->getMOTW()->getId() !== $motw->getId()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Parent comment does not belong to this artwork'
                ], Response::HTTP_BAD_REQUEST);
            }

            $comment->setComment($parentComment);
        }

        $entityManager->persist($comment);
        $entityManager->flush();

        // Dispatch event
        $event = new CommentCreatedEvent($comment);
        $eventDispatcher->dispatch($event, 'comment.created');

        return $this->json([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => $this->serializeComment($comment, [])
        ], Response::HTTP_CREATED);
    }

    private function serializeComment(Comment $comment, array $reportedIdSet): array
    {
        $user = $comment->getUser();
        $motw = $comment->getMOTW();

        return [
            'id'                    => $comment->getId(),
            'content'               => $comment->getContent(),
            'createdAt'             => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            'validated'             => $comment->isValidated(),
            'user'                  => [
                'id'   => $user ? $user->getId() : null,
                'name' => $user ? $user->getName() : 'Unknown',
            ],
            'motwSlug'              => $motw ? $motw->getSlug() : null,
            'parentCommentId'       => $comment->getComment() ? $comment->getComment()->getId() : null,
            'reportedByCurrentUser' => isset($reportedIdSet[$comment->getId()]),
        ];
    }
}

