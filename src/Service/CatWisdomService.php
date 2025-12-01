<?php

namespace App\Service;

use App\Entity\Cat;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * AI-powered service that provides whimsical cat wisdom fortunes.
 */
class CatWisdomService
{
    private const FALLBACK_WISDOMS = [
        "A warm lap is worth a thousand words.",
        "The best things in life are worth waiting for... like dinner.",
        "Nap often, for dreams await the patient soul.",
        "Curiosity didn't kill the cat — it made them wiser.",
        "If it fits, sits. This is the way.",
        "The early bird gets the worm, but the wise cat waits for treats.",
        "A gentle purr can heal any troubled heart.",
        "Never underestimate the power of a well-timed head boop.",
        "Chase your dreams as fiercely as you chase the red dot.",
        "Sometimes the best view is from the top of the bookshelf.",
        "Trust your whiskers — they know the way.",
        "Every cardboard box holds infinite possibilities.",
        "The sun always shines for those who find the sunny spot.",
        "Stretch before any important endeavor. Actually, stretch always.",
        "True friends will always share their warmth.",
        "Knock things off the table of doubt.",
        "The path to happiness is paved with soft blankets.",
        "Always land on your feet, but don't be afraid to fall.",
        "A belly rub a day keeps the grumpies away.",
        "The quietest meow often speaks the loudest truth.",
    ];

    private const LUCKY_ITEMS = [
        "A surprise treat",
        "An extra belly rub",
        "A sunny napping spot",
        "A fresh cardboard box",
        "A mysterious string",
        "An empty paper bag",
        "A warm laundry pile",
        "A crinkly toy",
        "An open window breeze",
        "A dangling feather",
    ];

    public function __construct(
        private AgentInterface $catWisdomAgent,
    ) {
    }

    /**
     * Get AI-generated wisdom from a specific cat.
     */
    public function getWisdomFromCat(Cat $cat): array
    {
        $wisdom = $this->generateAIWisdom($cat);
        $luckyItem = self::LUCKY_ITEMS[array_rand(self::LUCKY_ITEMS)];
        $luckyNumber = random_int(1, 9);

        $prefix = $this->buildPrefix($cat);

        return [
            'prefix' => $prefix,
            'wisdom' => $wisdom,
            'luckyItem' => $luckyItem,
            'luckyNumber' => $luckyNumber,
        ];
    }

    /**
     * Generate AI-powered wisdom based on the cat's personality.
     */
    private function generateAIWisdom(Cat $cat): string
    {
        $context = $this->buildCatContext($cat);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser('Share your wisdom with me.'));

        try {
            $response = $this->catWisdomAgent->call($messages);
            $wisdom = trim($response->getContent());

            // Clean up the response - remove quotes if present
            $wisdom = trim($wisdom, '"\'');

            return $wisdom;
        } catch (\Throwable $e) {
            // Fallback to static wisdom if AI fails
            return self::FALLBACK_WISDOMS[array_rand(self::FALLBACK_WISDOMS)];
        }
    }

    /**
     * Build context about the cat for AI wisdom generation.
     */
    private function buildCatContext(Cat $cat): string
    {
        $moodDescriptions = [
            'happy' => 'feeling joyful and optimistic',
            'content' => 'calm and at peace with the world',
            'grumpy' => 'a bit cynical but still wise',
            'upset' => 'contemplative and seeking meaning',
            'hungry' => 'thinking about sustenance and patience',
            'sleepy' => 'dreamy and philosophical',
        ];

        $moodDescription = $moodDescriptions[$cat->getMood()] ?? 'in a mysterious mood';

        return sprintf(
            "You are %s, a %d-year-old %s %s cat who is currently %s. " .
            "Your personality: %s. " .
            "Generate wisdom that reflects your unique perspective as this cat.",
            $cat->getName(),
            $cat->getAge(),
            $cat->getColor(),
            $cat->getBreed(),
            $moodDescription,
            $cat->getDescription() ?? 'A wise cafe cat who enjoys sharing insights with visitors'
        );
    }

    /**
     * Build a mood-based prefix for the wisdom delivery.
     */
    private function buildPrefix(Cat $cat): string
    {
        $catName = $cat->getName();

        return match ($cat->getMood()) {
            'happy' => "$catName purrs contentedly and shares this wisdom:",
            'content' => "$catName blinks slowly and offers this insight:",
            'grumpy' => "$catName huffs but reluctantly shares:",
            'upset' => "$catName sighs deeply and mutters:",
            'hungry' => "$catName glances at the food bowl, then says:",
            'sleepy' => "$catName yawns and whispers:",
            default => "$catName gazes at you wisely and says:",
        };
    }
}
