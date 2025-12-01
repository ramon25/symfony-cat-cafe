<?php

namespace App\Controller;

use App\Entity\Cat;
use App\Entity\User;
use App\Repository\CatRepository;
use App\Service\AchievementService;
use App\Service\AdoptionService;
use App\Service\CatMatchmakerService;
use App\Service\CatPersonalityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdoptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CatRepository $catRepository,
        private AdoptionService $adoptionService,
        private AchievementService $achievementService,
        private CatMatchmakerService $matchmakerService,
        private CatPersonalityService $personalityService,
    ) {
    }

    #[Route('/cat/{id}/quiz', name: 'app_cat_quiz', requirements: ['id' => '\d+'])]
    public function quiz(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        $questions = $this->adoptionService->getQuizQuestions();

        return $this->render('adoption/quiz.html.twig', [
            'cat' => $cat,
            'questions' => $questions,
            'existingScore' => $cat->getCompatibilityScore(),
        ]);
    }

    #[Route('/cat/{id}/quiz/submit', name: 'app_cat_quiz_submit', methods: ['POST'])]
    public function submitQuiz(Cat $cat, Request $request): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        $answers = $request->request->all();
        $score = $this->adoptionService->calculateCompatibility($cat, $answers);

        $cat->setCompatibilityScore($score);
        $this->entityManager->flush();

        // Unlock quiz achievements
        $this->achievementService->unlockAchievement('quiz_master');
        if ($score >= 80) {
            $this->achievementService->unlockAchievement('perfect_match');
        }

        return $this->redirectToRoute('app_cat_quiz_result', ['id' => $cat->getId()]);
    }

    #[Route('/cat/{id}/quiz/result', name: 'app_cat_quiz_result', requirements: ['id' => '\d+'])]
    public function quizResult(Cat $cat): Response
    {
        if ($cat->getCompatibilityScore() === null) {
            return $this->redirectToRoute('app_cat_quiz', ['id' => $cat->getId()]);
        }

        $score = $cat->getCompatibilityScore();
        $message = $this->adoptionService->getCompatibilityMessage($score);
        $fosterRequirements = $this->adoptionService->getFosteringRequirements($cat);
        $newAchievements = $this->achievementService->getNewlyUnlockedAchievements();

        return $this->render('adoption/quiz_result.html.twig', [
            'cat' => $cat,
            'score' => $score,
            'message' => $message,
            'fosterRequirements' => $fosterRequirements,
            'canFoster' => $cat->canBeFostered(),
            'newAchievements' => $newAchievements,
        ]);
    }

    #[Route('/cat/{id}/foster', name: 'app_cat_foster', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function foster(Cat $cat): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        if ($cat->isFostered()) {
            if ($cat->isOwnedBy($user)) {
                $this->addFlash('info', sprintf('You are already fostering %s!', $cat->getName()));
            } else {
                $this->addFlash('error', sprintf('%s is already being fostered by someone else.', $cat->getName()));
            }
            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        if (!$cat->canBeFostered()) {
            $this->addFlash('error', 'You need to build more bond and complete the compatibility quiz first!');
            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        $cat->setFostered(true);
        $cat->setOwner($user);
        $this->entityManager->flush();

        $this->achievementService->unlockAchievement('foster_parent');

        $this->addFlash('success', sprintf('Congratulations! You are now fostering %s! 🏡', $cat->getName()));

        return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
    }

    #[Route('/cat/{id}/adopt', name: 'app_cat_adopt_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function adopt(Cat $cat): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        // Only the fostering user can adopt
        if ($cat->isFostered() && !$cat->isOwnedBy($user)) {
            $this->addFlash('error', sprintf('%s is being fostered by someone else.', $cat->getName()));
            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        if (!$cat->canBeAdopted()) {
            $requirements = $this->adoptionService->getAdoptionRequirements($cat);
            $missing = array_filter($requirements, fn($r) => !$r['met']);
            $missingLabels = array_map(fn($r) => $r['label'], $missing);
            $this->addFlash('error', 'Almost there! You still need: ' . implode(', ', $missingLabels));
            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        $cat->setAdopted(true);
        $cat->setOwner($user);
        $this->entityManager->flush();

        $this->achievementService->unlockAchievement('forever_home');

        $this->addFlash('success', sprintf('Congratulations! You have officially adopted %s! Welcome to your forever family!', $cat->getName()));

        return $this->redirectToRoute('app_adoptions');
    }

    #[Route('/achievements', name: 'app_achievements')]
    public function achievements(): Response
    {
        $allAchievements = $this->achievementService->getAllAchievementsWithStatus();
        $progress = $this->achievementService->getAchievementProgress();
        $totalPoints = $this->achievementService->getTotalPoints();

        return $this->render('adoption/achievements.html.twig', [
            'achievements' => $allAchievements,
            'progress' => $progress,
            'totalPoints' => $totalPoints,
        ]);
    }

    #[Route('/cat/{id}/adoption-journey', name: 'app_cat_journey', requirements: ['id' => '\d+'])]
    public function adoptionJourney(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            return $this->render('adoption/completed.html.twig', [
                'cat' => $cat,
            ]);
        }

        $fosterRequirements = $this->adoptionService->getFosteringRequirements($cat);
        $adoptionRequirements = $this->adoptionService->getAdoptionRequirements($cat);

        return $this->render('adoption/journey.html.twig', [
            'cat' => $cat,
            'fosterRequirements' => $fosterRequirements,
            'adoptionRequirements' => $adoptionRequirements,
            'canFoster' => $cat->canBeFostered(),
            'canAdopt' => $cat->canBeAdopted(),
        ]);
    }

    #[Route('/api/cat/{id}/bonding', name: 'app_api_cat_bonding', methods: ['GET'])]
    public function apiBonding(Cat $cat): JsonResponse
    {
        return new JsonResponse([
            'bondingLevel' => $cat->getBondingLevel(),
            'milestone' => $cat->getBondingMilestone(),
            'emoji' => $cat->getBondingEmoji(),
            'canFoster' => $cat->canBeFostered(),
            'canAdopt' => $cat->canBeAdopted(),
            'isFostered' => $cat->isFostered(),
            'compatibilityScore' => $cat->getCompatibilityScore(),
            'preferredInteraction' => $cat->getPreferredInteraction(),
        ]);
    }

    // === AI-POWERED FEATURES ===

    #[Route('/matchmaker', name: 'app_matchmaker')]
    public function matchmaker(Request $request): Response
    {
        // Check if user has completed a quiz (stored in session)
        $session = $request->getSession();
        $quizAnswers = $session->get('last_quiz_answers', null);

        return $this->render('adoption/matchmaker.html.twig', [
            'hasQuizAnswers' => $quizAnswers !== null,
            'recommendations' => $quizAnswers ? $this->matchmakerService->getRecommendations($quizAnswers) : [],
        ]);
    }

    #[Route('/matchmaker/quiz', name: 'app_matchmaker_quiz')]
    public function matchmakerQuiz(): Response
    {
        $questions = $this->adoptionService->getQuizQuestions();

        return $this->render('adoption/matchmaker_quiz.html.twig', [
            'questions' => $questions,
        ]);
    }

    #[Route('/matchmaker/quiz/submit', name: 'app_matchmaker_quiz_submit', methods: ['POST'])]
    public function matchmakerQuizSubmit(Request $request): Response
    {
        $answers = $request->request->all();

        // Store answers in session for recommendations
        $session = $request->getSession();
        $session->set('last_quiz_answers', $answers);

        // Unlock quiz achievement
        $this->achievementService->unlockAchievement('quiz_master');

        return $this->redirectToRoute('app_matchmaker');
    }

    #[Route('/api/cat/{id}/ai-insights', name: 'app_api_cat_ai_insights', methods: ['GET'])]
    public function apiAiInsights(Cat $cat, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $quizAnswers = $session->get('last_quiz_answers', []);
        $score = $cat->getCompatibilityScore() ?? 50;

        $insights = $this->matchmakerService->getCompatibilityInsights($cat, $quizAnswers, $score);

        return new JsonResponse([
            'insights' => $insights,
        ]);
    }

    #[Route('/api/cat/{id}/bonding-advice', name: 'app_api_cat_bonding_advice', methods: ['GET'])]
    public function apiBondingAdvice(Cat $cat): JsonResponse
    {
        $advice = $this->matchmakerService->getBondingAdvice($cat, $cat->getBondingLevel());

        return new JsonResponse([
            'advice' => $advice,
            'bondingLevel' => $cat->getBondingLevel(),
            'milestone' => $cat->getBondingMilestone(),
        ]);
    }

    #[Route('/api/cat/{id}/personality', name: 'app_api_cat_personality', methods: ['GET'])]
    public function apiPersonality(Cat $cat, Request $request): JsonResponse
    {
        $forceRegenerate = $request->query->getBoolean('regenerate', false);

        $profile = $this->personalityService->getPersonalityProfile($cat, $forceRegenerate);
        $funFacts = $this->personalityService->getFunFacts($cat, $forceRegenerate);

        return new JsonResponse([
            'profile' => $profile,
            'funFacts' => $funFacts,
            'cached' => !$forceRegenerate && $cat->getAiGeneratedAt() !== null,
            'generatedAt' => $cat->getAiGeneratedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/cat/{id}/backstory', name: 'app_api_cat_backstory', methods: ['GET'])]
    public function apiBackstory(Cat $cat, Request $request): JsonResponse
    {
        $forceRegenerate = $request->query->getBoolean('regenerate', false);

        $backstory = $this->personalityService->getBackstory($cat, $forceRegenerate);

        return new JsonResponse([
            'backstory' => $backstory,
            'cached' => !$forceRegenerate && $cat->getAiGeneratedAt() !== null,
            'generatedAt' => $cat->getAiGeneratedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/cat/{id}/thought', name: 'app_api_cat_thought', methods: ['GET'])]
    public function apiThought(Cat $cat): JsonResponse
    {
        // Thoughts and bonding messages are always fresh (based on current state)
        $thought = $this->personalityService->generateCatThought($cat);
        $bondingMessage = $this->personalityService->generateBondingMessage($cat, $cat->getBondingLevel());

        return new JsonResponse([
            'thought' => $thought,
            'bondingMessage' => $bondingMessage,
            'mood' => $cat->getMood(),
            'moodEmoji' => $cat->getMoodEmoji(),
        ]);
    }

    #[Route('/api/cat/{id}/ai-content/regenerate', name: 'app_api_cat_ai_regenerate', methods: ['POST'])]
    public function apiRegenerateAiContent(Cat $cat): JsonResponse
    {
        // Generate all AI content and save to database
        $this->personalityService->generateAllContent($cat);

        return new JsonResponse([
            'success' => true,
            'message' => 'AI content regenerated successfully',
            'profile' => $cat->getAiPersonalityProfile(),
            'backstory' => $cat->getAiBackstory(),
            'funFacts' => $cat->getAiFunFacts(),
            'generatedAt' => $cat->getAiGeneratedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/cat/{id}/ai-content', name: 'app_api_cat_ai_content', methods: ['GET'])]
    public function apiAiContent(Cat $cat): JsonResponse
    {
        // Return cached AI content without generating new
        return new JsonResponse([
            'hasContent' => $cat->hasAiContent(),
            'profile' => $cat->getAiPersonalityProfile(),
            'backstory' => $cat->getAiBackstory(),
            'funFacts' => $cat->getAiFunFacts(),
            'generatedAt' => $cat->getAiGeneratedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}
