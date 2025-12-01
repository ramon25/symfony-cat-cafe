<?php

namespace App\Service;

use App\Entity\Cat;

class AdoptionService
{
    public const QUIZ_QUESTIONS = [
        [
            'id' => 'living_space',
            'question' => 'What kind of living space do you have?',
            'emoji' => '🏠',
            'options' => [
                ['value' => 'apartment', 'label' => 'Cozy apartment', 'traits' => ['calm', 'indoor']],
                ['value' => 'house', 'label' => 'House with yard', 'traits' => ['active', 'explorer']],
                ['value' => 'studio', 'label' => 'Small studio', 'traits' => ['calm', 'compact']],
            ],
        ],
        [
            'id' => 'activity_level',
            'question' => 'How active are you at home?',
            'emoji' => '🏃',
            'options' => [
                ['value' => 'couch', 'label' => 'Netflix & chill lifestyle', 'traits' => ['calm', 'cuddly']],
                ['value' => 'moderate', 'label' => 'Balanced activity', 'traits' => ['adaptable']],
                ['value' => 'active', 'label' => 'Always on the move!', 'traits' => ['active', 'playful']],
            ],
        ],
        [
            'id' => 'schedule',
            'question' => 'What\'s your typical schedule like?',
            'emoji' => '📅',
            'options' => [
                ['value' => 'home', 'label' => 'Work from home', 'traits' => ['social', 'attention-loving']],
                ['value' => 'regular', 'label' => 'Regular 9-5', 'traits' => ['independent']],
                ['value' => 'irregular', 'label' => 'Unpredictable hours', 'traits' => ['independent', 'adaptable']],
            ],
        ],
        [
            'id' => 'noise_level',
            'question' => 'How do you feel about a chatty cat?',
            'emoji' => '🗣️',
            'options' => [
                ['value' => 'quiet', 'label' => 'Prefer the strong, silent type', 'traits' => ['quiet']],
                ['value' => 'moderate', 'label' => 'Some meows are cute', 'traits' => ['moderate-vocal']],
                ['value' => 'chatty', 'label' => 'Love constant conversations!', 'traits' => ['vocal', 'social']],
            ],
        ],
        [
            'id' => 'experience',
            'question' => 'Have you had cats before?',
            'emoji' => '🐱',
            'options' => [
                ['value' => 'first', 'label' => 'This would be my first!', 'traits' => ['beginner-friendly']],
                ['value' => 'some', 'label' => 'Had one growing up', 'traits' => ['adaptable']],
                ['value' => 'expert', 'label' => 'Experienced cat parent', 'traits' => ['any']],
            ],
        ],
        [
            'id' => 'other_pets',
            'question' => 'Any other pets at home?',
            'emoji' => '🐕',
            'options' => [
                ['value' => 'none', 'label' => 'Nope, just me!', 'traits' => ['any']],
                ['value' => 'cats', 'label' => 'Other cats', 'traits' => ['social', 'cat-friendly']],
                ['value' => 'dogs', 'label' => 'Dogs (the friendly kind)', 'traits' => ['dog-friendly', 'confident']],
            ],
        ],
        [
            'id' => 'cuddle_style',
            'question' => 'What\'s your ideal cuddle situation?',
            'emoji' => '🤗',
            'options' => [
                ['value' => 'lap', 'label' => 'Lap cat 24/7', 'traits' => ['cuddly', 'affectionate']],
                ['value' => 'nearby', 'label' => 'Same room, but personal space', 'traits' => ['independent', 'calm']],
                ['value' => 'mix', 'label' => 'Cuddles on THEIR terms', 'traits' => ['independent', 'personality']],
            ],
        ],
        [
            'id' => 'zoomies',
            'question' => 'How do you feel about 3 AM zoomies?',
            'emoji' => '🌙',
            'options' => [
                ['value' => 'no', 'label' => 'Hard pass, I need my sleep!', 'traits' => ['calm', 'older']],
                ['value' => 'maybe', 'label' => 'Occasionally is fine', 'traits' => ['adaptable']],
                ['value' => 'yes', 'label' => 'Bring on the chaos!', 'traits' => ['young', 'playful', 'energetic']],
            ],
        ],
    ];

    // Cat breed traits for compatibility matching
    private const BREED_TRAITS = [
        'Maine Coon' => ['calm', 'affectionate', 'adaptable', 'dog-friendly', 'social'],
        'Siamese' => ['vocal', 'social', 'attention-loving', 'active', 'playful'],
        'Domestic Shorthair' => ['adaptable', 'independent', 'beginner-friendly', 'any'],
        'Scottish Fold' => ['calm', 'cuddly', 'quiet', 'indoor', 'adaptable'],
        'British Shorthair' => ['calm', 'independent', 'quiet', 'beginner-friendly'],
        'Ragdoll' => ['cuddly', 'calm', 'affectionate', 'indoor', 'beginner-friendly'],
        'Tuxedo' => ['playful', 'social', 'adaptable', 'confident', 'personality'],
        'Abyssinian' => ['active', 'playful', 'explorer', 'energetic', 'young'],
    ];

    public function getQuizQuestions(): array
    {
        return self::QUIZ_QUESTIONS;
    }

    public function calculateCompatibility(Cat $cat, array $answers): int
    {
        $catTraits = $this->getCatTraits($cat);
        $userTraits = $this->getUserTraitsFromAnswers($answers);

        // Calculate match percentage
        $matchCount = 0;
        $totalWeight = 0;

        foreach ($userTraits as $trait => $weight) {
            $totalWeight += $weight;
            if (in_array($trait, $catTraits) || in_array('any', $catTraits) || $trait === 'any') {
                $matchCount += $weight;
            }
        }

        // Base compatibility from trait matching
        $traitScore = $totalWeight > 0 ? ($matchCount / $totalWeight) * 60 : 30;

        // Bonus points based on bonding level (up to 20 points)
        $bondingBonus = ($cat->getBondingLevel() / 100) * 20;

        // Bonus for preferred interaction alignment (up to 20 points)
        $preferenceBonus = $this->calculatePreferenceBonus($cat, $answers);

        $totalScore = min(100, round($traitScore + $bondingBonus + $preferenceBonus));

        return max(20, $totalScore); // Minimum 20% - everyone has some compatibility!
    }

    private function getCatTraits(Cat $cat): array
    {
        $breedTraits = self::BREED_TRAITS[$cat->getBreed()] ?? ['adaptable'];

        // Add age-based traits
        if ($cat->getAge() <= 2) {
            $breedTraits[] = 'young';
            $breedTraits[] = 'playful';
            $breedTraits[] = 'energetic';
        } elseif ($cat->getAge() >= 5) {
            $breedTraits[] = 'older';
            $breedTraits[] = 'calm';
        }

        // Add mood-based traits
        if ($cat->getMood() === 'happy') {
            $breedTraits[] = 'social';
        }

        return array_unique($breedTraits);
    }

    private function getUserTraitsFromAnswers(array $answers): array
    {
        $traits = [];

        foreach (self::QUIZ_QUESTIONS as $question) {
            $answerId = $answers[$question['id']] ?? null;
            if ($answerId) {
                foreach ($question['options'] as $option) {
                    if ($option['value'] === $answerId) {
                        foreach ($option['traits'] as $trait) {
                            $traits[$trait] = ($traits[$trait] ?? 0) + 1;
                        }
                        break;
                    }
                }
            }
        }

        return $traits;
    }

    private function calculatePreferenceBonus(Cat $cat, array $answers): int
    {
        // Map quiz answers to preferred interactions
        $activityAnswer = $answers['activity_level'] ?? 'moderate';
        $cuddleAnswer = $answers['cuddle_style'] ?? 'mix';

        $preferredInteraction = $cat->getPreferredInteraction();

        $bonus = 0;

        // If user is active and cat loves play
        if ($activityAnswer === 'active' && $preferredInteraction === Cat::INTERACTION_PLAY) {
            $bonus += 10;
        }

        // If user is couch potato and cat loves rest
        if ($activityAnswer === 'couch' && $preferredInteraction === Cat::INTERACTION_REST) {
            $bonus += 10;
        }

        // If user loves lap cats and cat loves pets
        if ($cuddleAnswer === 'lap' && $preferredInteraction === Cat::INTERACTION_PET) {
            $bonus += 10;
        }

        // If user is home a lot and cat loves feeding time
        $scheduleAnswer = $answers['schedule'] ?? 'regular';
        if ($scheduleAnswer === 'home' && $preferredInteraction === Cat::INTERACTION_FEED) {
            $bonus += 10;
        }

        return min(20, $bonus);
    }

    public function getCompatibilityMessage(int $score): string
    {
        if ($score >= 90) {
            return "You're a match made in cat heaven! This kitty was meant for you!";
        }
        if ($score >= 80) {
            return "Excellent match! You two would make an amazing team!";
        }
        if ($score >= 70) {
            return "Great compatibility! You'd get along wonderfully!";
        }
        if ($score >= 60) {
            return "Good match! With some bonding, you'll be best friends!";
        }
        if ($score >= 50) {
            return "Decent match! Every friendship takes time to blossom!";
        }
        if ($score >= 40) {
            return "You two are different, but opposites attract!";
        }
        return "A unique pairing! Love knows no compatibility score!";
    }

    public function getFosteringRequirements(Cat $cat): array
    {
        $requirements = [];

        // Bonding requirement
        $bondingMet = $cat->getBondingLevel() >= 30;
        $requirements['bonding'] = [
            'label' => 'Build a bond (30%+ bonding level)',
            'met' => $bondingMet,
            'current' => $cat->getBondingLevel(),
            'required' => 30,
            'emoji' => $bondingMet ? '✅' : '💛',
        ];

        // Quiz requirement
        $quizMet = $cat->getCompatibilityScore() !== null;
        $requirements['quiz'] = [
            'label' => 'Complete compatibility quiz',
            'met' => $quizMet,
            'emoji' => $quizMet ? '✅' : '📝',
        ];

        return $requirements;
    }

    public function getAdoptionRequirements(Cat $cat): array
    {
        $requirements = [];

        // Bonding requirement
        $bondingMet = $cat->getBondingLevel() >= 50;
        $requirements['bonding'] = [
            'label' => 'Strong bond (50%+ bonding level)',
            'met' => $bondingMet,
            'current' => $cat->getBondingLevel(),
            'required' => 50,
            'emoji' => $bondingMet ? '✅' : '❤️',
        ];

        // Fostering requirement
        $fosterMet = $cat->isFostered();
        $requirements['foster'] = [
            'label' => 'Complete foster trial period',
            'met' => $fosterMet,
            'emoji' => $fosterMet ? '✅' : '🏡',
        ];

        return $requirements;
    }
}
