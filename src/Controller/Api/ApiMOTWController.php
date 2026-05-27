<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\MOTW;
use App\Entity\User;
use App\Repository\CommentLikeRepository;
use App\Repository\MOTWRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
final class ApiMOTWController extends AbstractController
{
    #[Route('/motw', name: 'api_motw_list', methods: ['GET'])]
    public function list(
        Request $request,
        MOTWRepository $motwRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $page  = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(50, (int) $request->query->get('limit', '10')));

        $total = $motwRepository->count([]);
        $motws = $motwRepository->findBy([], ['DatePost' => 'DESC'], $limit, ($page - 1) * $limit);

        // Fetch validated comment counts in a single query
        $counts = [];
        if ($motws) {
            $rows = $em->createQuery(
                'SELECT IDENTITY(c.mOTW) AS motwId, COUNT(c.id) AS cnt
                 FROM App\Entity\Comment c
                 WHERE c.mOTW IN (:motws) AND c.validated = true
                 GROUP BY c.mOTW'
            )->setParameter('motws', $motws)->getArrayResult();

            foreach ($rows as $row) {
                $counts[(int) $row['motwId']] = (int) $row['cnt'];
            }
        }

        $data = array_map(
            fn(MOTW $motw) => $this->serializeMotw($motw, $counts[(int) $motw->getId()] ?? 0),
            $motws
        );

        return $this->json([
            'success' => true,
            'data'    => $data,
            'pagination' => [
                'currentPage' => $page,
                'pages'       => (int) ceil(max(1, $total) / $limit),
            ],
        ]);
    }

    #[Route('/motw/{slug}', name: 'api_motw_detail', methods: ['GET'])]
    public function detail(
        string $slug,
        MOTWRepository $motwRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $motw = $motwRepository->findOneBy(['slug' => $slug]);

        if (!$motw) {
            return $this->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $count = (int) $em->createQuery(
            'SELECT COUNT(c) FROM App\Entity\Comment c WHERE c.mOTW = :motw AND c.validated = true'
        )->setParameter('motw', $motw)->getSingleScalarResult();

        return $this->json([
            'success' => true,
            'data'    => $this->serializeMotw($motw, $count),
        ]);
    }

    #[Route('/motw/{slug}/comments', name: 'api_motw_comments', methods: ['GET'])]
    public function comments(
        string $slug,
        Request $request,
        MOTWRepository $motwRepository,
        EntityManagerInterface $em,
        ReportRepository $reportRepository,
        CommentLikeRepository $commentLikeRepository,
        #[CurrentUser] ?User $currentUser = null,
    ): JsonResponse {
        $motw = $motwRepository->findOneBy(['slug' => $slug]);

        if (!$motw) {
            return $this->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $page   = max(1, $request->query->getInt('page', 1));
        $limit  = min(100, max(1, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;
        $sort   = $request->query->get('sort', 'recent'); // 'recent' or 'likes'

        $qb = $em->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->where('c.mOTW = :motw')
            ->andWhere('c.validated = true')
            ->andWhere('c.hidden = false')
            ->andWhere('c.comment IS NULL')
            ->setParameter('motw', $motw)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($sort === 'likes') {
            $qb->leftJoin('c.likes', 'cl')
               ->addSelect('COUNT(cl.id) AS HIDDEN likesCount')
               ->groupBy('c.id')
               ->orderBy('likesCount', 'DESC')
               ->addOrderBy('c.createdAt', 'DESC');
        } else {
            $qb->orderBy('c.createdAt', 'DESC');
        }

        /** @var Comment[] $comments */
        $comments = $qb->getQuery()->getResult();

        // Collect all IDs for bulk like/report lookups
        $allIds = [];
        foreach ($comments as $c) {
            $allIds[] = $c->getId();
            foreach ($c->getReply() as $reply) {
                $allIds[] = $reply->getId();
            }
        }

        $reportedIdSet = $currentUser
            ? array_flip($reportRepository->findReportedCommentIdsByUser($currentUser, $allIds))
            : [];
        $likeCountMap = $commentLikeRepository->getLikeCountsByCommentIds($allIds);
        $likedIdSet   = $currentUser
            ? array_flip($commentLikeRepository->findLikedCommentIdsByUser($currentUser, $allIds))
            : [];

        $data = array_map(
            fn(Comment $c) => $this->serializeComment($c, $reportedIdSet, $likeCountMap, $likedIdSet),
            $comments
        );

        $total = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Comment::class, 'c')
            ->where('c.mOTW = :motw')
            ->andWhere('c.validated = true')
            ->andWhere('c.hidden = false')
            ->andWhere('c.comment IS NULL')
            ->setParameter('motw', $motw)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'success'    => true,
            'data'       => $data,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil(max(1, $total) / $limit),
            ],
        ]);
    }

    private function serializeMotw(MOTW $motw, int $commentCount = 0): array
    {
        return [
            'id'           => $motw->getId(),
            'name'         => $motw->getName(),
            'artist'       => $motw->getArtist(),
            'date'         => $motw->getDate()?->format('Y-m-d'),
            'datePost'     => $motw->getDatePost()?->format('Y-m-d'),
            'slug'         => $motw->getSlug(),
            'visual'       => $motw->getVisual(),
            'commentCount' => $commentCount,
        ];
    }

    private function serializeComment(Comment $comment, array $reportedIdSet = [], array $likeCountMap = [], array $likedIdSet = []): array
    {
        $replies = [];
        foreach ($comment->getReply() as $reply) {
            if ($reply->isValidated() && !$reply->isHidden()) {
                $replies[] = [
                    'id'                    => $reply->getId(),
                    'content'               => $reply->getContent(),
                    'createdAt'             => $reply->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                    'validated'             => $reply->isValidated(),
                    'user'                  => [
                        'id'   => $reply->getUser()?->getId(),
                        'name' => $reply->getUser()?->getName(),
                    ],
                    'motwSlug'              => $reply->getMOTW()?->getSlug(),
                    'parentCommentId'       => $reply->getComment()?->getId(),
                    'reportedByCurrentUser' => isset($reportedIdSet[$reply->getId()]),
                    'likesCount'            => $likeCountMap[$reply->getId()] ?? 0,
                    'likedByCurrentUser'    => isset($likedIdSet[$reply->getId()]),
                    'replies'               => [],
                ];
            }
        }

        return [
            'id'                    => $comment->getId(),
            'content'               => $comment->getContent(),
            'createdAt'             => $comment->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'validated'             => $comment->isValidated(),
            'user'                  => [
                'id'   => $comment->getUser()?->getId(),
                'name' => $comment->getUser()?->getName(),
            ],
            'motwSlug'              => $comment->getMOTW()?->getSlug(),
            'parentCommentId'       => $comment->getComment()?->getId(),
            'reportedByCurrentUser' => isset($reportedIdSet[$comment->getId()]),
            'likesCount'            => $likeCountMap[$comment->getId()] ?? 0,
            'likedByCurrentUser'    => isset($likedIdSet[$comment->getId()]),
            'replies'               => $replies,
        ];
    }
}
