<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Report;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function findOneByCommentAndUser(Comment $comment, User $user): ?Report
    {
        return $this->createQueryBuilder('r')
            ->where('r.comment = :comment')
            ->andWhere('r.user = :user')
            ->setParameter('comment', $comment)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns comments that have at least one report, with report count and reasons.
     *
     * @return array<int, array{comment: Comment, reportCount: int, reasons: string[]}>
     */
    public function findReportedCommentsGrouped(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.comment) AS commentId, COUNT(r.id) AS reportCount')
            ->groupBy('r.comment')
            ->orderBy('reportCount', 'DESC')
            ->getQuery()
            ->getResult();

        if (empty($rows)) {
            return [];
        }

        $commentIds = array_column($rows, 'commentId');

        $reasonRows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.comment) AS commentId, r.reason')
            ->where('r.comment IN (:ids)')
            ->setParameter('ids', $commentIds)
            ->getQuery()
            ->getResult();

        $reasonsByCommentId = [];
        foreach ($reasonRows as $rr) {
            $reasonsByCommentId[(int) $rr['commentId']][] = $rr['reason'];
        }

        $comments = $this->getEntityManager()
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->leftJoin('c.mOTW', 'm')->addSelect('m')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $commentIds)
            ->getQuery()
            ->getResult();

        $commentsById = [];
        foreach ($comments as $comment) {
            $commentsById[$comment->getId()] = $comment;
        }

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['commentId'];
            if (!isset($commentsById[$id])) {
                continue;
            }
            $result[] = [
                'comment'     => $commentsById[$id],
                'reportCount' => (int) $row['reportCount'],
                'reasons'     => array_unique($reasonsByCommentId[$id] ?? []),
            ];
        }

        return $result;
    }

    /**
     * Returns the IDs of comments reported by a given user, limited to a set of comment IDs.
     *
     * @param int[] $commentIds
     * @return int[]
     */
    public function findReportedCommentIdsByUser(User $user, array $commentIds): array
    {
        if (empty($commentIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.comment) AS commentId')
            ->where('r.user = :user')
            ->andWhere('r.comment IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $commentIds)
            ->getQuery()
            ->getResult();

        return array_map(fn($row) => (int) $row['commentId'], $rows);
    }
}
