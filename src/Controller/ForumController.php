<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\MOTW;
use App\Form\CommentType;
use App\Repository\MOTWRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'app_forum')]
    public function index(MOTWRepository $motwRepository): Response
    {
        // Récupérer tous les MOTW, triés par date de post (plus récent en premier)
        $motws = $motwRepository->findBy([], ['DatePost' => 'DESC']);

        return $this->render('forum/index.html.twig', [
            'motws' => $motws,
        ]);
    }

    #[Route('/forum/{slug}', name: 'app_forum_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        MOTW $motw, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response
    {
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

            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès!');

            return $this->redirectToRoute('app_forum_show', ['slug' => $motw->getSlug()]);
        }

        return $this->render('forum/show.html.twig', [
            'motw' => $motw,
            'commentForm' => $form->createView(),
        ]);
    }
}

