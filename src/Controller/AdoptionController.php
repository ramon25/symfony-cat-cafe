<?php

namespace App\Controller;

use App\Entity\Cat;
use App\Repository\CatRepository;
use App\Service\AchievementService;
use App\Service\AdoptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdoptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CatRepository $catRepository,
        private AdoptionService $adoptionService,
        private AchievementService $achievementService,
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
    public function foster(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        if ($cat->isFostered()) {
            $this->addFlash('info', sprintf('You are already fostering %s!', $cat->getName()));
            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        if (!$cat->canBeFostered()) {
            $this->addFlash('error', 'You need to build more bond and complete the compatibility quiz first!');
            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        $cat->setFostered(true);
        $this->entityManager->flush();

        $this->achievementService->unlockAchievement('foster_parent');

        $this->addFlash('success', sprintf('Congratulations! You are now fostering %s! 🏡', $cat->getName()));

        return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
    }

    #[Route('/cat/{id}/adopt', name: 'app_cat_adopt_new', methods: ['POST'])]
    public function adopt(Cat $cat): Response
    {
        if ($cat->isAdopted()) {
            $this->addFlash('error', 'This cat has already been adopted!');
            return $this->redirectToRoute('app_home');
        }

        if (!$cat->canBeAdopted()) {
            $requirements = $this->adoptionService->getAdoptionRequirements($cat);
            $missing = array_filter($requirements, fn($r) => !$r['met']);
            $missingLabels = array_map(fn($r) => $r['label'], $missing);
            $this->addFlash('error', 'Almost there! You still need: ' . implode(', ', $missingLabels));
            return $this->redirectToRoute('app_cat_show', ['id' => $cat->getId()]);
        }

        $cat->setAdopted(true);
        $this->entityManager->flush();

        $this->achievementService->unlockAchievement('forever_home');

        $this->addFlash('success', sprintf('🎉 Congratulations! You have officially adopted %s! Welcome to your forever family!', $cat->getName()));

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
}
