<?php

namespace App\Service;

use App\Entity\Cat;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * AI-powered cat generator service that creates unique cats using AI.
 */
class CatGeneratorService
{
    private const BREEDS = [
        'Persian', 'Maine Coon', 'Siamese', 'British Shorthair', 'Ragdoll',
        'Bengal', 'Abyssinian', 'Scottish Fold', 'Sphynx', 'Russian Blue',
        'Norwegian Forest', 'American Shorthair', 'Domestic Shorthair',
        'Domestic Longhair', 'Mixed',
    ];

    private const COLORS = [
        'Orange', 'Black', 'White', 'Gray', 'Calico',
        'Tabby', 'Tuxedo', 'Tortoiseshell', 'Cream', 'Brown',
    ];

    public function __construct(
        private AgentInterface $catGeneratorAgent,
    ) {
    }

    /**
     * Generate a cat using AI based on optional user preferences/prompt.
     *
     * @param string|null $userPrompt Optional prompt describing what kind of cat the user wants
     * @return array{name: string, breed: string, age: int, color: string, description: string, preferredInteraction: string}
     */
    public function generateCat(?string $userPrompt = null): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userMessage = $userPrompt
            ? "Generate a unique cat with these preferences: {$userPrompt}"
            : "Generate a unique and creative cat for our cat cafe.";

        $messages = new MessageBag(Message::forSystem($systemPrompt));
        $messages->add(Message::ofUser($userMessage));

        try {
            $response = $this->catGeneratorAgent->call($messages);
            $content = $response->getContent();

            return $this->parseResponse($content);
        } catch (\Throwable $e) {
            // Fallback to random generation if AI fails
            return $this->generateRandomCat();
        }
    }

    /**
     * Build the system prompt for the AI.
     */
    private function buildSystemPrompt(): string
    {
        $breeds = implode(', ', self::BREEDS);
        $colors = implode(', ', self::COLORS);
        $interactions = implode(', ', Cat::ALL_INTERACTIONS);

        return <<<PROMPT
You are a creative cat generator for the "Whiskers & Wonders Cat Cafe". Your job is to generate unique and charming cats.

IMPORTANT: You MUST respond in valid JSON format with exactly these fields:
{
  "name": "A creative, cute cat name (2-20 characters)",
  "breed": "One of: {$breeds}",
  "age": A number between 1 and 15,
  "color": "One of: {$colors}",
  "description": "A charming 2-3 sentence personality description that makes this cat unique and lovable",
  "preferredInteraction": "One of: {$interactions}"
}

Guidelines for generation:
- Names should be creative, cute, and memorable (like Whiskers, Luna, Mochi, Sir Fluffington, etc.)
- Descriptions should be whimsical and highlight the cat's unique personality traits
- Match the description to the breed characteristics (e.g., Siamese are vocal, Maine Coons are gentle giants)
- The preferred interaction should match the cat's personality

Only respond with the JSON object, no additional text.
PROMPT;
    }

    /**
     * Parse the AI response into cat data.
     *
     * @return array{name: string, breed: string, age: int, color: string, description: string, preferredInteraction: string}
     */
    private function parseResponse(string $content): array
    {
        // Try to extract JSON from the response
        $content = trim($content);

        // Handle markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
            return $this->generateRandomCat();
        }

        // Validate and sanitize the response
        return [
            'name' => $this->sanitizeName($data['name'] ?? null),
            'breed' => $this->sanitizeBreed($data['breed'] ?? null),
            'age' => $this->sanitizeAge($data['age'] ?? null),
            'color' => $this->sanitizeColor($data['color'] ?? null),
            'description' => $this->sanitizeDescription($data['description'] ?? null),
            'preferredInteraction' => $this->sanitizeInteraction($data['preferredInteraction'] ?? null),
        ];
    }

    private function sanitizeName(?string $name): string
    {
        if (!$name || strlen($name) < 2 || strlen($name) > 100) {
            return $this->getRandomName();
        }
        return substr(trim($name), 0, 100);
    }

    private function sanitizeBreed(?string $breed): string
    {
        if (!$breed || !in_array($breed, self::BREEDS, true)) {
            return self::BREEDS[array_rand(self::BREEDS)];
        }
        return $breed;
    }

    private function sanitizeAge(mixed $age): int
    {
        $age = (int) $age;
        if ($age < 1 || $age > 30) {
            return random_int(1, 12);
        }
        return $age;
    }

    private function sanitizeColor(?string $color): string
    {
        if (!$color || !in_array($color, self::COLORS, true)) {
            return self::COLORS[array_rand(self::COLORS)];
        }
        return $color;
    }

    private function sanitizeDescription(?string $description): string
    {
        if (!$description) {
            return 'A lovable cafe cat who enjoys making visitors smile with their charming personality.';
        }
        return substr(trim($description), 0, 1000);
    }

    private function sanitizeInteraction(?string $interaction): string
    {
        if (!$interaction || !in_array($interaction, Cat::ALL_INTERACTIONS, true)) {
            return Cat::ALL_INTERACTIONS[array_rand(Cat::ALL_INTERACTIONS)];
        }
        return $interaction;
    }

    /**
     * Generate a random cat as fallback.
     *
     * @return array{name: string, breed: string, age: int, color: string, description: string, preferredInteraction: string}
     */
    private function generateRandomCat(): array
    {
        return [
            'name' => $this->getRandomName(),
            'breed' => self::BREEDS[array_rand(self::BREEDS)],
            'age' => random_int(1, 12),
            'color' => self::COLORS[array_rand(self::COLORS)],
            'description' => 'A lovable cafe cat who enjoys making visitors smile with their charming personality.',
            'preferredInteraction' => Cat::ALL_INTERACTIONS[array_rand(Cat::ALL_INTERACTIONS)],
        ];
    }

    private function getRandomName(): string
    {
        $names = [
            'Whiskers', 'Luna', 'Mochi', 'Shadow', 'Ginger',
            'Mittens', 'Felix', 'Cleo', 'Simba', 'Nala',
            'Oliver', 'Bella', 'Max', 'Chloe', 'Tiger',
            'Smokey', 'Oreo', 'Patches', 'Ziggy', 'Pumpkin',
        ];
        return $names[array_rand($names)];
    }

    /**
     * Get the list of available breeds.
     */
    public static function getBreeds(): array
    {
        return self::BREEDS;
    }

    /**
     * Get the list of available colors.
     */
    public static function getColors(): array
    {
        return self::COLORS;
    }
}
