<?php

namespace App\Controller\Api;

use App\Repository\ArtTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/art-types', name: 'api_art_types_')]
class ArtTypeController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ArtTypeRepository $artTypeRepository): JsonResponse
    {
        $artTypes = $artTypeRepository->findBy([], ['name' => 'ASC']);

        $data = array_map(fn($artType) => [
            'id' => $artType->getId(),
            'name' => $artType->getName(),
        ], $artTypes);

        return $this->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
