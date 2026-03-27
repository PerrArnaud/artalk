<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/admin/comments', name: 'app_admin_comments')]
    public function listPendingComments(
        CommentRepository $commentRepository,
        Request $request
    ): Response
    {
        // Get filter parameter (all, pending, validated)
        $filter = $request->query->get('filter', 'all');
        
        $queryBuilder = $commentRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->leftJoin('c.mOTW', 'm')
            ->addSelect('m')
            ->orderBy('c.createdAt', 'DESC');
        
        // Apply filter
        if ($filter === 'pending') {
            $queryBuilder->andWhere('c.validated = false');
        } elseif ($filter === 'validated') {
            $queryBuilder->andWhere('c.validated = true');
        }
        
        $comments = $queryBuilder->getQuery()->getResult();
        
        return $this->render('admin/comments.html.twig', [
            'comments' => $comments,
            'filter' => $filter,
        ]);
    }

    #[Route('/admin/comments/{id}/validate', name: 'app_admin_comment_validate', methods: ['POST'])]
    public function validateComment(
        Comment $comment,
        EntityManagerInterface $entityManager
    ): Response
    {
        $comment->setValidated(true);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le commentaire a été validé avec succès.');
        
        return $this->redirectToRoute('app_admin_comments');
    }

    #[Route('/admin/comments/{id}/delete', name: 'app_admin_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Comment $comment,
        EntityManagerInterface $entityManager
    ): Response
    {
        $entityManager->remove($comment);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le commentaire a été supprimé avec succès.');
        
        return $this->redirectToRoute('app_admin_comments');
    }
}
