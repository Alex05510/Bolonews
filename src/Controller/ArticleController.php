<?php

namespace App\Controller;

use App\Form\SearchType;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $articles = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $articles = $articleRepository->findBySearchQuery($data['recherche'] ?? '');
        }

        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
            'form' => $form->createView(),
            'articles' => $articles,
        ]);
    }

    #[Route('/article/new', name: 'app_article_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $article = new Article();
    $form = $this->createForm(ArticleType::class, $article);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $article->setAuteur($this->getUser());
        $entityManager->persist($article);
        $entityManager->flush();

        return $this->redirectToRoute('app_profil');
    }

    return $this->render('article/new.html.twig', [
        'form' => $form->createView(),
    ]);
}

    #[Route('/article/{id}', name: 'app_article_show')]
    public function show(int $id, ArticleRepository $articleRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $article = $articleRepository->find($id);
        if (!$article) {
            throw $this->createNotFoundException('aucun article trouvé');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
}