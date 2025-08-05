<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findOneBy([], ['date' => 'DESC']);
        $dernieresParutions = $articleRepository->createQueryBuilder('a')
        ->where('a.date >= :dateLimite')
        ->setParameter('dateLimite', (new \DateTime())->modify('-7 days'))
        ->orderBy('a.date', 'DESC')
        ->getQuery()
        ->getResult();
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'articles' => $articles,
            'dernieresParutions' => $dernieresParutions,
            
        ]);
}
}