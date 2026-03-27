<?php

namespace App\Controller\Api;

use App\Entity\MOTW;
use App\Repository\MOTWRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/motw', name: 'api_motw_')]
class MOTWController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, MOTWRepository $motwRepository): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $offset = ($page - 1) * $limit;

        // Get all MOTW sorted by datePost descending
        $queryBuilder = $motwRepository->createQueryBuilder('m')
            ->orderBy('m.DatePost', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $motws = $queryBuilder->getQuery()->getResult();
        
        // Get total count for pagination
        $totalCount = $motwRepository->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $data = [];
        foreach ($motws as $motw) {
            $data[] = [
                'id' => $motw->getId(),
                'name' => $motw->getName(),
                'artist' => $motw->getArtist(),
                'date' => $motw->getDate()->format('Y-m-d'),
                'datePost' => $motw->getDatePost()->format('Y-m-d'),
                'slug' => $motw->getSlug(),
                'visual' => $motw->getFullVisualUrl(),
                'commentCount' => $motw->getCommentCount()
            ];
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

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug, MOTWRepository $motwRepository): JsonResponse
    {
        $motw = $motwRepository->findOneBy(['slug' => $slug]);

        if (!$motw) {
            return $this->json([
                'success' => false,
                'message' => 'Artwork not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $motw->getId(),
                'name' => $motw->getName(),
                'artist' => $motw->getArtist(),
                'date' => $motw->getDate()->format('Y-m-d'),
                'datePost' => $motw->getDatePost()->format('Y-m-d'),
                'slug' => $motw->getSlug(),
                'visual' => $motw->getFullVisualUrl(),
                'commentCount' => $motw->getCommentCount()
            ]
        ]);
    }
}
