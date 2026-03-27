<?php

namespace App\Controller;

use App\Entity\MOTW;
use App\Form\MOTWType;
use App\Repository\MOTWRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/post/crud')]
final class PostCrudController extends AbstractController
{
    #[Route(name: 'app_post_crud_index', methods: ['GET'])]
    public function index(MOTWRepository $mOTWRepository): Response
    {
        return $this->render('post_crud/index.html.twig', [
            'm_o_t_ws' => $mOTWRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_post_crud_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $mOTW = new MOTW();
        $form = $this->createForm(MOTWType::class, $mOTW);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload du fichier image
            $visualFile = $form->get('visual')->getData();
            
            if ($visualFile) {
                $originalFilename = pathinfo($visualFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$visualFile->guessExtension();
                
                try {
                    $visualFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/images',
                        $newFilename
                    );
                    
                    $mOTW->setVisual('/uploads/images/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }
            
            $mOTW->setDatePost(new \DateTime());
            $entityManager->persist($mOTW);
            $entityManager->flush();

            return $this->redirectToRoute('app_post_crud_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post_crud/new.html.twig', [
            'm_o_t_w' => $mOTW,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_crud_show', methods: ['GET'])]
    public function show(MOTW $mOTW): Response
    {
        return $this->render('post_crud/show.html.twig', [
            'm_o_t_w' => $mOTW,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_crud_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MOTW $mOTW, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(MOTWType::class, $mOTW);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload du fichier image
            $visualFile = $form->get('visual')->getData();
            
            if ($visualFile) {
                $originalFilename = pathinfo($visualFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$visualFile->guessExtension();
                
                try {
                    $visualFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/images',
                        $newFilename
                    );
                    
                    $mOTW->setVisual('/uploads/images/'.$newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('app_post_crud_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post_crud/edit.html.twig', [
            'm_o_t_w' => $mOTW,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_crud_delete', methods: ['POST'])]
    public function delete(Request $request, MOTW $mOTW, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mOTW->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($mOTW);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_crud_index', [], Response::HTTP_SEE_OTHER);
    }
}
