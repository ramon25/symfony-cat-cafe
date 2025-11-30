<?php

namespace App\Controller;

use App\Entity\Cat;
use App\Repository\CatRepository;
use App\Service\CatWisdomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CafeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CatRepository $catRepository,
        private CatWisdomService $wisdomService,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $cats = $this->catRepository->findAvailable();
        $adoptedCount = $this->catRepository->countAdopted();
        $hungryCats = $this->catRepository->findHungryCats();

        return $this->render('cafe/index.html.twig', [
            'cats' => $cats,
            'adoptedCount' => $adoptedCount,
            'hungryCats' => $hungryCats,
        ]);
    }

    #[Route('/cat/{id}', name: 'app_cat_show', requirements: ['id' => '\d+'])]
    public function show(Cat $cat): Response
    {
        return $this->render('cafe/show.html.twig', [
            'cat' => $cat,
        ]);
    }

    #[Route('/cat/{id}/feed', name: 'app_cat_feed', methods: ['POST'])]
    public function feed(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        $cat->feed();
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%s has been fed and is feeling better!', $cat->getName()));

        return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
    }

    #[Route('/cat/{id}/pet', name: 'app_cat_pet', methods: ['POST'])]
    public function pet(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        $cat->pet();
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%s purrs happily!', $cat->getName()));

        return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
    }

    #[Route('/cat/{id}/play', name: 'app_cat_play', methods: ['POST'])]
    public function play(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        $cat->play();
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%s had so much fun playing!', $cat->getName()));

        return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
    }

    #[Route('/cat/{id}/rest', name: 'app_cat_rest', methods: ['POST'])]
    public function rest(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        $cat->rest();
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%s takes a peaceful nap.', $cat->getName()));

        return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
    }

    #[Route('/cat/{id}/wisdom', name: 'app_cat_wisdom', methods: ['GET'])]
    public function wisdom(Cat $cat): JsonResponse
    {
        $fortune = $this->wisdomService->getWisdomFromCat($cat->getName(), $cat->getMood());

        return new JsonResponse([
            'success' => true,
            'catName' => $cat->getName(),
            'catEmoji' => $cat->getMoodEmoji(),
            'prefix' => $fortune['prefix'],
            'wisdom' => $fortune['wisdom'],
            'luckyItem' => $fortune['luckyItem'],
            'luckyNumber' => $fortune['luckyNumber'],
        ]);
    }

    #[Route('/cat/{id}/adopt', name: 'app_cat_adopt', methods: ['POST'])]
    public function adopt(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        $cat->setAdopted(true);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Congratulations! You have adopted %s!', $cat->getName()));

        return $this->redirectToRoute('app_adoptions');
    }

    #[Route('/adoptions', name: 'app_adoptions')]
    public function adoptions(): Response
    {
        $adoptedCats = $this->catRepository->findAdopted();

        return $this->render('cafe/adoptions.html.twig', [
            'cats' => $adoptedCats,
        ]);
    }

    #[Route('/cat/new', name: 'app_cat_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $cat = new Cat();
            $cat->setName($request->request->get('name'));
            $cat->setBreed($request->request->get('breed'));
            $cat->setAge((int) $request->request->get('age'));
            $cat->setColor($request->request->get('color'));
            $cat->setDescription($request->request->get('description'));

            $this->entityManager->persist($cat);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('%s has joined the cat cafe!', $cat->getName()));

            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        return $this->render('cafe/new.html.twig');
    }

    #[Route('/feed-all', name: 'app_feed_all', methods: ['POST'])]
    public function feedAll(): Response
    {
        $cats = $this->catRepository->findAvailable();
        $fedCount = 0;

        foreach ($cats as $cat) {
            $cat->feed();
            $fedCount++;
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Fed all %d cats!', $fedCount));

        return $this->redirectToRoute('app_home');
    }
}
