<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Article;
use App\Form\SearchType;
use App\Form\ArticleType;
use App\Form\CommentaireType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $articles = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $articles = $articleRepository->findBySearchQuery($data['recherche'] ?? '');
        }

        $categories = $entityManager->getRepository(\App\Entity\Categorie::class)->findAll();

        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
            'form' => $form->createView(),
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    #[Route('/article/index', name: 'app_article_index')]
    public function articleIndex(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();
        return $this->render('article/index.html.twig', [
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
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('images_directory'),
                $newFilename
            );
            $article->setImage($newFilename);
        }
        $article->setAuteur($this->getUser());
        $entityManager->persist($article);
        $entityManager->flush();

        return $this->redirectToRoute('app_profil');
    }

    return $this->render('article/new.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/article/{id}/edit', name: 'app_article_edit')]
public function edit(Request $request, Article $article, EntityManagerInterface $em): Response
{
    $form = $this->createForm(ArticleType::class, $article);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        return $this->redirectToRoute('app_profil'); 
    }

    return $this->render('article/edit.html.twig', [
        'form' => $form->createView(),
        'article' => $article,
    ]);
}

    #[Route('/article/show/{id}', name: 'app_article_show')]
    public function show(int $id, ArticleRepository $articleRepository, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $article = $articleRepository->find($id);
        if (!$article) {
            throw $this->createNotFoundException('aucun article trouvé');
        }

        // Récupérer les commentaires liés à l'article
        $commentaires = $em->getRepository(\App\Entity\Commentaire::class)->findBy(['article' => $article]);

        // Formulaire d'ajout de commentaire
        $form = $this->createForm(CommentaireType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire = $form->getData();
            $commentaire->setArticle($article);
            $commentaire->setAuteur($this->getUser());
            $commentaire->setDate(new \DateTime());
            $em->persist($commentaire);
            $em->flush();
            return $this->redirectToRoute('app_article_show', ['id' => $id]);
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'commentaires' => $commentaires,
            'form' => $form->createView(),
        ]);
    }

    #[Route('categorie/{id}', name: 'app_categorie_articles', methods: ['GET'])]
    public function categorieArticles(int $id, ArticleRepository $articleRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $form = $this->createForm(\App\Form\SearchType::class);
        $form->handleRequest($request);
        $articles = $articleRepository->findBy(['categorie' => $id]);
        $categories = $entityManager->getRepository(\App\Entity\Categorie::class)->findAll();
        return $this->render('article/categorie_articles.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/article/{id}/like', name: 'app_article_like', methods: ['POST'])]
public function like(Article $article, EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['error' => 'Non connecté'], 403);
    }
    $like = $em->getRepository(Like::class)->findOneBy(['user' => $user, 'article' => $article]);
    if ($like) {
        $em->remove($like);
        $em->flush();
        return new JsonResponse(['liked' => false, 'count' => count($article->getLikes())]);
    } else {
        $like = new Like();
        $like->setUser($user);
        $like->setArticle($article);
        $em->persist($like);
        $em->flush();
        return new JsonResponse(['liked' => true, 'count' => count($article->getLikes())]);
    }
}
    #[Route('/article/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Article $article, EntityManagerInterface $em): Response
    {
        $em->remove($article);
        $em->flush();
        return $this->redirectToRoute('app_profil');
    }
}