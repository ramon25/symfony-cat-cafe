<?php

namespace App\Service;

/**
 * Service that provides whimsical cat wisdom fortunes.
 */
class CatWisdomService
{
    private const WISDOMS = [
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
        "Life is better with a little catnip.",
        "Judge not by the scratching post, but by the character.",
        "Patience is the art of hiding your anticipation for treats.",
        "Even the mightiest lion started as a curious kitten.",
        "Elegance is an attitude, not just a coat color.",
        "The window to the soul is best viewed from a windowsill.",
        "In every ending, there is a new beginning... especially at 3 AM.",
        "Share your toys generously, except the favorite one.",
        "The greatest journeys begin with a single pounce.",
        "Let your inner kitten guide you to joy.",
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

    public function getRandomWisdom(): array
    {
        $wisdom = self::WISDOMS[array_rand(self::WISDOMS)];
        $luckyItem = self::LUCKY_ITEMS[array_rand(self::LUCKY_ITEMS)];
        $luckyNumber = random_int(1, 9);

        return [
            'wisdom' => $wisdom,
            'luckyItem' => $luckyItem,
            'luckyNumber' => $luckyNumber,
        ];
    }

    public function getWisdomFromCat(string $catName, string $catMood): array
    {
        $fortune = $this->getRandomWisdom();

        // Add a mood-based prefix from the cat
        $prefix = match ($catMood) {
            'happy' => "$catName purrs contentedly and shares this wisdom:",
            'content' => "$catName blinks slowly and offers this insight:",
            'grumpy' => "$catName huffs but reluctantly shares:",
            'upset' => "$catName sighs deeply and mutters:",
            'hungry' => "$catName glances at the food bowl, then says:",
            'sleepy' => "$catName yawns and whispers:",
            default => "$catName gazes at you wisely and says:",
        };

        return [
            'prefix' => $prefix,
            'wisdom' => $fortune['wisdom'],
            'luckyItem' => $fortune['luckyItem'],
            'luckyNumber' => $fortune['luckyNumber'],
        ];
    }
}
