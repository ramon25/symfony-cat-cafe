<?php

namespace App\Controller;

use App\Entity\Cat;
use App\Entity\ChatMessage;
use App\Repository\CatRepository;
use App\Repository\ChatMessageRepository;
use App\Service\AchievementService;
use App\Service\CatTherapistService;
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
        private CatTherapistService $therapistService,
        private ChatMessageRepository $chatMessageRepository,
        private AchievementService $achievementService,
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

        // Track achievements
        $this->achievementService->incrementStat('feed', $cat->getId());
        $this->checkBondingAchievements($cat);

        $bonusMsg = $cat->getPreferredInteraction() === Cat::INTERACTION_FEED ? ' 💕 They LOVE being fed!' : '';
        $this->addFlash('success', sprintf('%s has been fed and is feeling better!%s', $cat->getName(), $bonusMsg));

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

        // Track achievements
        $this->achievementService->incrementStat('pet', $cat->getId());
        $this->checkBondingAchievements($cat);

        $bonusMsg = $cat->getPreferredInteraction() === Cat::INTERACTION_PET ? ' 💕 They LOVE being petted!' : '';
        $this->addFlash('success', sprintf('%s purrs happily!%s', $cat->getName(), $bonusMsg));

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

        // Track achievements
        $this->achievementService->incrementStat('play', $cat->getId());
        $this->checkBondingAchievements($cat);

        $bonusMsg = $cat->getPreferredInteraction() === Cat::INTERACTION_PLAY ? ' 💕 They LOVE playing!' : '';
        $this->addFlash('success', sprintf('%s had so much fun playing!%s', $cat->getName(), $bonusMsg));

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

        // Track achievements
        $this->achievementService->incrementStat('rest', $cat->getId());
        $this->checkBondingAchievements($cat);

        $bonusMsg = $cat->getPreferredInteraction() === Cat::INTERACTION_REST ? ' 💕 They LOVE resting with you!' : '';
        $this->addFlash('success', sprintf('%s takes a peaceful nap.%s', $cat->getName(), $bonusMsg));

        return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
    }

    private function checkBondingAchievements(Cat $cat): void
    {
        if ($cat->getBondingLevel() >= 80) {
            $this->achievementService->unlockAchievement('best_friends');
        }
    }

    #[Route('/cat/{id}/wisdom', name: 'app_cat_wisdom', methods: ['GET'])]
    public function wisdom(Cat $cat): JsonResponse
    {
        $fortune = $this->wisdomService->getWisdomFromCat($cat);

        // Track wisdom achievement
        $this->achievementService->incrementStat('wisdom', $cat->getId());

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

    #[Route('/cat/{id}/therapy', name: 'app_cat_therapy', methods: ['POST'])]
    public function therapy(Cat $cat, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';
        $sessionId = $request->getSession()->getId();

        if (empty(trim($userMessage))) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Please share what\'s on your mind!',
            ], 400);
        }

        // Get conversation history for context
        $chatHistory = $this->chatMessageRepository->findByCatAndSession($cat, $sessionId);

        // Save user message
        $userChatMessage = new ChatMessage();
        $userChatMessage->setCat($cat);
        $userChatMessage->setSessionId($sessionId);
        $userChatMessage->setRole('user');
        $userChatMessage->setContent($userMessage);
        $this->entityManager->persist($userChatMessage);

        // Get AI response with conversation history
        $advice = $this->therapistService->getAdvice($cat, $userMessage, $chatHistory);

        // Save assistant response
        $assistantMessage = new ChatMessage();
        $assistantMessage->setCat($cat);
        $assistantMessage->setSessionId($sessionId);
        $assistantMessage->setRole('assistant');
        $assistantMessage->setContent($advice);
        $this->entityManager->persist($assistantMessage);

        $this->entityManager->flush();

        // Track therapy achievement
        $this->achievementService->incrementStat('therapy', $cat->getId());

        return new JsonResponse([
            'success' => true,
            'catName' => $cat->getName(),
            'catEmoji' => $cat->getMoodEmoji(),
            'advice' => $advice,
        ]);
    }

    #[Route('/cat/{id}/chat-history', name: 'app_cat_chat_history', methods: ['GET'])]
    public function chatHistory(Cat $cat, Request $request): JsonResponse
    {
        $sessionId = $request->getSession()->getId();
        $messages = $this->chatMessageRepository->findByCatAndSession($cat, $sessionId);

        $formattedMessages = array_map(fn(ChatMessage $msg) => [
            'role' => $msg->getRole(),
            'content' => $msg->getContent(),
            'createdAt' => $msg->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $messages);

        return new JsonResponse([
            'success' => true,
            'messages' => $formattedMessages,
            'catName' => $cat->getName(),
            'catEmoji' => $cat->getMoodEmoji(),
        ]);
    }

    #[Route('/cat/{id}/chat-clear', name: 'app_cat_chat_clear', methods: ['POST'])]
    public function chatClear(Cat $cat, Request $request): JsonResponse
    {
        $sessionId = $request->getSession()->getId();
        $deletedCount = $this->chatMessageRepository->clearByCatAndSession($cat, $sessionId);

        return new JsonResponse([
            'success' => true,
            'deletedCount' => $deletedCount,
            'message' => 'Chat history cleared successfully!',
        ]);
    }

    #[Route('/cat/{id}/adopt', name: 'app_cat_adopt', methods: ['POST'])]
    public function adopt(Cat $cat): Response
    {
        // Redirect to the new adoption journey system
        return $this->redirectToRoute('app_cat_journey', ['id' => $cat->getId()]);
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

    #[Route('/api/cats/stats', name: 'app_api_cats_stats', methods: ['GET'])]
    public function apiCatsStats(): JsonResponse
    {
        $cats = $this->catRepository->findAvailable();
        $hungryCats = $this->catRepository->findHungryCats();
        $adoptedCount = $this->catRepository->countAdopted();

        $catsData = array_map(fn(Cat $cat) => [
            'id' => $cat->getId(),
            'name' => $cat->getName(),
            'hunger' => $cat->getHunger(),
            'happiness' => $cat->getHappiness(),
            'energy' => $cat->getEnergy(),
            'mood' => $cat->getMood(),
            'moodEmoji' => $cat->getMoodEmoji(),
        ], $cats);

        return new JsonResponse([
            'success' => true,
            'cats' => $catsData,
            'summary' => [
                'availableCount' => count($cats),
                'adoptedCount' => $adoptedCount,
                'hungryCount' => count($hungryCats),
            ],
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
