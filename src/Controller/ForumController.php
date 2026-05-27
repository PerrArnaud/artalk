<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\MOTW;
use App\Event\CommentCreatedEvent;
use App\Form\CommentType;
use App\Repository\ArtTypeRepository;
use App\Repository\CommentRepository;
use App\Repository\MOTWRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'app_forum')]
    public function index(MOTWRepository $motwRepository, ArtTypeRepository $artTypeRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $artTypeId = $request->query->get('artType') ? (int) $request->query->get('artType') : null;

        $qb = $motwRepository->createQueryBuilder('m')
            ->orderBy('m.DatePost', 'DESC');

        if ($artTypeId !== null) {
            $qb->andWhere('m.artType = :artTypeId')
               ->setParameter('artTypeId', $artTypeId);
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $artTypes = $artTypeRepository->findBy([], ['name' => 'ASC']);

        return $this->render('forum/index.html.twig', [
            'motws' => $pagination,
            'artTypes' => $artTypes,
            'selectedArtType' => $artTypeId,
        ]);
    }

    #[Route('/forum/{slug}', name: 'app_forum_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        MOTW $motw, 
        Request $request, 
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        PaginatorInterface $paginator,
        EventDispatcherInterface $eventDispatcher
    ): Response
    {
        // Check if this is an inline reply submission (raw HTML form)
        $parentCommentId = $request->request->get('parentCommentId');
        $replyContent = $request->request->get('reply_content');
        
        if ($request->isMethod('POST') && $parentCommentId && $replyContent) {
            // Handle inline reply form submission
            if (!$this->getUser()) {
                $this->addFlash('error', 'Vous devez être connecté pour répondre.');
                return $this->redirectToRoute('app_login');
            }
            
            $parentComment = $commentRepository->find($parentCommentId);
            
            // Verify parent comment exists and doesn't already have a parent (limit to 1 level)
            if (!$parentComment || $parentComment->getComment() !== null) {
                $this->addFlash('error', 'Impossible de répondre à ce commentaire.');
                return $this->redirectToRoute('app_forum_show', ['slug' => $motw->getSlug()]);
            }
            
            // Create reply
            $reply = new Comment();
            $reply->setContent($replyContent);
            $reply->setMOTW($motw);
            $reply->setUser($this->getUser());
            $reply->setComment($parentComment);
            
            $entityManager->persist($reply);
            $entityManager->flush();
            
            // Dispatch event for notifications
            $eventDispatcher->dispatch(new CommentCreatedEvent($reply), CommentCreatedEvent::NAME);
            
            $this->addFlash('success', 'Votre réponse a été ajoutée avec succès!');
            
            return $this->redirectToRoute('app_forum_show', ['slug' => $motw->getSlug()]);
        }

        // Handle main comment form (Symfony form)
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que l'utilisateur est connecté
            if (!$this->getUser()) {
                $this->addFlash('error', 'Vous devez être connecté pour commenter.');
                return $this->redirectToRoute('app_login');
            }

            $comment->setMOTW($motw);
            $comment->setUser($this->getUser());

            $entityManager->persist($comment);
            $entityManager->flush();
            
            // Dispatch event for notifications
            $eventDispatcher->dispatch(new CommentCreatedEvent($comment), CommentCreatedEvent::NAME);

            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès!');

            return $this->redirectToRoute('app_forum_show', ['slug' => $motw->getSlug()]);
        }
        
        // Get only parent comments (those without a parent) for pagination, excluding hidden
        $queryBuilder = $commentRepository->createQueryBuilder('c')
            ->where('c.mOTW = :motw')
            ->andWhere('c.comment IS NULL')
            ->andWhere('c.hidden = false')
            ->setParameter('motw', $motw)
            ->orderBy('c.createdAt', 'DESC');
        
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15 // Comments per page
        );

        return $this->render('forum/show.html.twig', [
            'motw' => $motw,
            'commentForm' => $form->createView(),
            'pagination' => $pagination,
        ]);
    }
}

