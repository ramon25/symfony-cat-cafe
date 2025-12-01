<?php

namespace App\Service;

use App\Entity\Cat;
use App\Repository\CatRepository;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * AI-powered matchmaker service that provides personalized cat recommendations
 * and compatibility insights.
 */
class CatMatchmakerService
{
    public function __construct(
        private AgentInterface $catMatchmakerAgent,
        private CatRepository $catRepository,
        private AdoptionService $adoptionService,
    ) {
    }

    /**
     * Get AI-generated compatibility insights for a cat based on quiz answers.
     */
    public function getCompatibilityInsights(Cat $cat, array $quizAnswers, int $score): string
    {
        $context = $this->buildCompatibilityContext($cat, $quizAnswers, $score);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser(
            "Based on the quiz answers and compatibility score, provide personalized insights about why this person and {$cat->getName()} would be a good match. Include specific tips for building a stronger bond."
        ));

        try {
            $response = $this->catMatchmakerAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            return $this->getFallbackInsight($cat, $score);
        }
    }

    /**
     * Get AI-powered bonding advice specific to a cat's personality and current state.
     */
    public function getBondingAdvice(Cat $cat, int $currentBondingLevel): string
    {
        $context = $this->buildBondingContext($cat, $currentBondingLevel);

        $messages = new MessageBag(Message::forSystem($context));

        $prompt = match (true) {
            $currentBondingLevel < 30 => "Provide 3 specific tips to help build initial trust and reach the fostering milestone with {$cat->getName()}.",
            $currentBondingLevel < 50 => "Provide 3 specific tips to deepen the bond and prepare for adoption with {$cat->getName()}.",
            $currentBondingLevel < 80 => "Provide 3 tips to strengthen an already good relationship with {$cat->getName()} and become best friends.",
            default => "Provide tips for maintaining a wonderful best-friend relationship with {$cat->getName()}.",
        };

        $messages->add(Message::ofUser($prompt));

        try {
            $response = $this->catMatchmakerAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            return $this->getFallbackBondingAdvice($cat, $currentBondingLevel);
        }
    }

    /**
     * Get AI-powered cat recommendations based on user preferences from quiz.
     *
     * @return array{cat: Cat, score: int, reason: string}[]
     */
    public function getRecommendations(array $quizAnswers, int $limit = 3): array
    {
        $availableCats = $this->catRepository->findAvailable();

        if (empty($availableCats)) {
            return [];
        }

        // Calculate compatibility scores for all cats
        $scoredCats = [];
        foreach ($availableCats as $cat) {
            $score = $this->adoptionService->calculateCompatibility($cat, $quizAnswers);
            $scoredCats[] = [
                'cat' => $cat,
                'score' => $score,
            ];
        }

        // Sort by score descending
        usort($scoredCats, fn($a, $b) => $b['score'] <=> $a['score']);

        // Take top cats
        $topCats = array_slice($scoredCats, 0, $limit);

        // Generate AI reasons for each recommendation
        $recommendations = [];
        foreach ($topCats as $scoredCat) {
            $cat = $scoredCat['cat'];
            $score = $scoredCat['score'];

            $reason = $this->generateRecommendationReason($cat, $quizAnswers, $score);

            $recommendations[] = [
                'cat' => $cat,
                'score' => $score,
                'reason' => $reason,
            ];
        }

        return $recommendations;
    }

    /**
     * Generate AI-powered reason for why a cat is recommended.
     */
    private function generateRecommendationReason(Cat $cat, array $quizAnswers, int $score): string
    {
        $context = $this->buildRecommendationContext($cat, $quizAnswers, $score);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser(
            "In 1-2 sentences, explain why {$cat->getName()} would be a great match for this person based on their lifestyle answers."
        ));

        try {
            $response = $this->catMatchmakerAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            return $this->getFallbackReason($cat, $score);
        }
    }

    private function buildCompatibilityContext(Cat $cat, array $quizAnswers, int $score): string
    {
        $answersDescription = $this->describeAnswers($quizAnswers);
        $catDescription = $this->describeCat($cat);

        return <<<CONTEXT
You are a friendly cat adoption matchmaker at "Whiskers & Wonders Cat Cafe".
Your job is to help potential adopters understand their compatibility with cats and give helpful advice.

Cat Profile:
{$catDescription}

User's Quiz Answers:
{$answersDescription}

Compatibility Score: {$score}%

Provide warm, encouraging insights that highlight the positive aspects of this potential match.
Keep responses concise (2-3 short paragraphs) and actionable.
CONTEXT;
    }

    private function buildBondingContext(Cat $cat, int $bondingLevel): string
    {
        $catDescription = $this->describeCat($cat);
        $milestone = $cat->getBondingMilestone();
        $preferredInteraction = $cat->getPreferredInteractionLabel();

        return <<<CONTEXT
You are a cat behavior expert at "Whiskers & Wonders Cat Cafe" helping visitors bond with cats.

Cat Profile:
{$catDescription}

Current Bonding Level: {$bondingLevel}%
Bonding Milestone: {$milestone}
Favorite Activity: {$preferredInteraction}

Provide specific, practical bonding tips tailored to this cat's personality and preferences.
Keep advice warm, encouraging, and actionable. Format as a short numbered list.
CONTEXT;
    }

    private function buildRecommendationContext(Cat $cat, array $quizAnswers, int $score): string
    {
        $answersDescription = $this->describeAnswers($quizAnswers);
        $catDescription = $this->describeCat($cat);

        return <<<CONTEXT
You are a cat matchmaker providing brief reasons why a cat is a good match.

Cat: {$catDescription}
User Lifestyle: {$answersDescription}
Match Score: {$score}%

Be warm and specific about why this match works. Focus on 1-2 key compatibility factors.
CONTEXT;
    }

    private function describeCat(Cat $cat): string
    {
        $parts = [
            "Name: {$cat->getName()}",
            "Breed: {$cat->getBreed()}",
            "Age: {$cat->getAge()} years old",
            "Color: {$cat->getColor()}",
            "Current Mood: {$cat->getMood()}",
            "Favorite Activity: {$cat->getPreferredInteractionLabel()}",
        ];

        if ($cat->getDescription()) {
            $parts[] = "Personality: {$cat->getDescription()}";
        }

        return implode("\n", $parts);
    }

    private function describeAnswers(array $answers): string
    {
        $descriptions = [];
        $questions = AdoptionService::QUIZ_QUESTIONS;

        foreach ($questions as $question) {
            $answerId = $answers[$question['id']] ?? null;
            if ($answerId) {
                foreach ($question['options'] as $option) {
                    if ($option['value'] === $answerId) {
                        $descriptions[] = "{$question['question']} {$option['label']}";
                        break;
                    }
                }
            }
        }

        return implode("\n", $descriptions);
    }

    private function getFallbackInsight(Cat $cat, int $score): string
    {
        if ($score >= 80) {
            return "You and {$cat->getName()} are a fantastic match! Your lifestyles align wonderfully. Focus on {$cat->getPreferredInteractionLabel()} to build an even stronger connection.";
        }
        if ($score >= 60) {
            return "You and {$cat->getName()} have great potential together! Spend quality time on activities they enjoy, especially {$cat->getPreferredInteractionLabel()}.";
        }
        return "Every friendship takes time to grow! Keep visiting {$cat->getName()} and discovering what makes them happy.";
    }

    private function getFallbackBondingAdvice(Cat $cat, int $bondingLevel): string
    {
        $preferred = $cat->getPreferredInteractionLabel();

        if ($bondingLevel < 30) {
            return "1. Visit regularly - consistency builds trust\n2. Try {$preferred} - it's their favorite!\n3. Be patient and let {$cat->getName()} come to you";
        }
        if ($bondingLevel < 50) {
            return "1. Continue {$preferred} sessions\n2. Try new activities to discover more about {$cat->getName()}\n3. Spend longer visits to deepen your connection";
        }
        return "1. Maintain your bond with regular {$preferred} sessions\n2. You know {$cat->getName()} well - trust your instincts!\n3. Enjoy your amazing friendship";
    }

    private function getFallbackReason(Cat $cat, int $score): string
    {
        if ($score >= 80) {
            return "{$cat->getName()} is an excellent match for your lifestyle! Their {$cat->getBreed()} temperament suits you perfectly.";
        }
        if ($score >= 60) {
            return "{$cat->getName()}'s personality complements your preferences, especially their love of {$cat->getPreferredInteractionLabel()}.";
        }
        return "{$cat->getName()} could be a wonderful companion with a bit of bonding time.";
    }
}
