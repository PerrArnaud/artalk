<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\CommentLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentLike>
 */
class CommentLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentLike::class);
    }

    public function findOneByCommentAndUser(Comment $comment, User $user): ?CommentLike
    {
        return $this->findOneBy(['comment' => $comment, 'user' => $user]);
    }

    /**
     * Returns the IDs from $ids that the given user has liked.
     *
     * @param int[] $ids
     * @return int[]
     */
    public function findLikedCommentIdsByUser(User $user, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $result = $this->createQueryBuilder('cl')
            ->select('IDENTITY(cl.comment)')
            ->where('cl.user = :user')
            ->andWhere('cl.comment IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $result);
    }

    /**
     * Returns a map of commentId => likeCount for the given comment IDs.
     *
     * @param int[] $ids
     * @return array<int, int>
     */
    public function getLikeCountsByCommentIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = $this->createQueryBuilder('cl')
            ->select('IDENTITY(cl.comment) AS commentId, COUNT(cl.id) AS cnt')
            ->where('cl.comment IN (:ids)')
            ->setParameter('ids', $ids)
            ->groupBy('cl.comment')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($results as $row) {
            $map[(int) $row['commentId']] = (int) $row['cnt'];
        }

        return $map;
    }
}
