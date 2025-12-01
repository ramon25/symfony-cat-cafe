<?php

namespace App\Service;

use App\Entity\Cat;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * AI-powered service for generating dynamic cat personality profiles,
 * backstories, and mood-based thoughts.
 */
class CatPersonalityService
{
    public function __construct(
        private AgentInterface $catPersonalityAgent,
    ) {
    }

    /**
     * Generate an AI-powered personality profile for a cat.
     */
    public function generatePersonalityProfile(Cat $cat): string
    {
        $context = $this->buildProfileContext($cat);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser(
            "Generate a charming personality profile for {$cat->getName()}. Include their quirks, favorite things, and what makes them special. Keep it warm and inviting."
        ));

        try {
            $response = $this->catPersonalityAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            return $this->getFallbackProfile($cat);
        }
    }

    /**
     * Generate a unique backstory for a cat.
     */
    public function generateBackstory(Cat $cat): string
    {
        $context = $this->buildBackstoryContext($cat);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser(
            "Create a heartwarming origin story for {$cat->getName()}. How did they come to the cafe? What's their journey been like?"
        ));

        try {
            $response = $this->catPersonalityAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            return $this->getFallbackBackstory($cat);
        }
    }

    /**
     * Generate what the cat is "thinking" based on their current mood and stats.
     */
    public function generateCatThought(Cat $cat): string
    {
        $context = $this->buildThoughtContext($cat);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser(
            "What is {$cat->getName()} thinking right now? Generate a short, cute thought bubble (1-2 sentences) based on their current mood and state."
        ));

        try {
            $response = $this->catPersonalityAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            return $this->getFallbackThought($cat);
        }
    }

    /**
     * Generate a special message for adopters based on bonding level.
     */
    public function generateBondingMessage(Cat $cat, int $bondingLevel): string
    {
        $context = $this->buildBondingMessageContext($cat, $bondingLevel);

        $messages = new MessageBag(Message::forSystem($context));

        $prompt = match (true) {
            $bondingLevel < 10 => "Generate a shy but curious greeting from {$cat->getName()} to a new visitor.",
            $bondingLevel < 30 => "Generate a friendly message from {$cat->getName()} recognizing a returning visitor.",
            $bondingLevel < 50 => "Generate an affectionate message from {$cat->getName()} to someone they're bonding with.",
            $bondingLevel < 80 => "Generate a loving message from {$cat->getName()} to their close friend.",
            default => "Generate an extremely affectionate message from {$cat->getName()} to their best friend.",
        };

        $messages->add(Message::ofUser($prompt));

        try {
            $response = $this->catPersonalityAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            return $this->getFallbackBondingMessage($cat, $bondingLevel);
        }
    }

    /**
     * Generate fun facts about a cat based on their breed and characteristics.
     */
    public function generateFunFacts(Cat $cat): array
    {
        $context = $this->buildFunFactsContext($cat);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser(
            "Generate 3 fun, unique facts about {$cat->getName()} based on their breed and personality. Format as a simple list, one fact per line."
        ));

        try {
            $response = $this->catPersonalityAgent->call($messages);
            $content = $response->getContent();

            // Parse the response into an array of facts
            $facts = array_filter(
                array_map('trim', explode("\n", $content)),
                fn($line) => !empty($line) && $line !== '-' && strlen($line) > 5
            );

            // Clean up any bullet points or numbers
            $facts = array_map(function ($fact) {
                return preg_replace('/^[\d\.\-\*]+\s*/', '', $fact);
            }, $facts);

            return array_values(array_slice($facts, 0, 3));
        } catch (\Throwable $e) {
            return $this->getFallbackFunFacts($cat);
        }
    }

    private function buildProfileContext(Cat $cat): string
    {
        return <<<CONTEXT
You are a creative writer at "Whiskers & Wonders Cat Cafe" who writes delightful cat profiles.

Cat Details:
- Name: {$cat->getName()}
- Breed: {$cat->getBreed()}
- Age: {$cat->getAge()} years old
- Color: {$cat->getColor()}
- Current Mood: {$cat->getMood()}
- Favorite Activity: {$cat->getPreferredInteractionLabel()}
- Existing Description: {$cat->getDescription()}

Write engaging, warm content that makes visitors want to meet this cat.
Keep it concise (2-3 short paragraphs) and capture the cat's unique personality.
CONTEXT;
    }

    private function buildBackstoryContext(Cat $cat): string
    {
        $joinDate = $cat->getCreatedAt()->format('F Y');

        return <<<CONTEXT
You are a storyteller at "Whiskers & Wonders Cat Cafe" who creates heartwarming cat origin stories.

Cat Details:
- Name: {$cat->getName()}
- Breed: {$cat->getBreed()}
- Age: {$cat->getAge()} years old
- Color: {$cat->getColor()}
- Joined the Cafe: {$joinDate}
- Personality: {$cat->getDescription()}

Create a touching but uplifting backstory. Keep it brief (2-3 short paragraphs).
Avoid overly sad or traumatic elements - focus on hope and new beginnings.
CONTEXT;
    }

    private function buildThoughtContext(Cat $cat): string
    {
        return <<<CONTEXT
You generate cute, in-character cat thoughts based on their current state.

Cat: {$cat->getName()} ({$cat->getBreed()})
Current Mood: {$cat->getMood()}
Hunger Level: {$cat->getHunger()}% (higher = more hungry)
Happiness: {$cat->getHappiness()}%
Energy: {$cat->getEnergy()}%
Favorite Activity: {$cat->getPreferredInteractionLabel()}

Generate a short, cute thought that reflects their current state. Use cat logic and cat priorities.
Keep it to 1-2 short sentences. Can include cat sounds like "mrrrow" or "purr".
CONTEXT;
    }

    private function buildBondingMessageContext(Cat $cat, int $bondingLevel): string
    {
        $milestone = $cat->getBondingMilestone();

        return <<<CONTEXT
You write sweet messages from cats to their human friends.

Cat: {$cat->getName()} ({$cat->getBreed()})
Personality: {$cat->getDescription()}
Current Mood: {$cat->getMood()}
Bonding Level: {$bondingLevel}% ({$milestone})

Write a message as if the cat could talk. Keep it short (1-2 sentences), sweet, and match the bonding level.
Include occasional cat mannerisms. The message should feel personal and heartfelt.
CONTEXT;
    }

    private function buildFunFactsContext(Cat $cat): string
    {
        return <<<CONTEXT
You generate fun, educational facts about cats based on their breed and personality.

Cat: {$cat->getName()}
Breed: {$cat->getBreed()}
Age: {$cat->getAge()} years
Favorite Activity: {$cat->getPreferredInteractionLabel()}

Mix breed-specific facts with made-up quirky personal facts. Make them entertaining and memorable.
CONTEXT;
    }

    private function getFallbackProfile(Cat $cat): string
    {
        $preferred = $cat->getPreferredInteractionLabel();

        return "{$cat->getName()} is a wonderful {$cat->getAge()}-year-old {$cat->getBreed()} with a {$cat->getColor()} coat. " .
            "Known for their love of {$preferred}, they bring joy to everyone at the cafe. " .
            "Come meet this special feline friend!";
    }

    private function getFallbackBackstory(Cat $cat): string
    {
        $joinDate = $cat->getCreatedAt()->format('F Y');

        return "{$cat->getName()} arrived at Whiskers & Wonders in {$joinDate}, " .
            "and quickly became one of the cafe's most beloved residents. " .
            "Now they spend their days making visitors smile and waiting for their perfect forever home.";
    }

    private function getFallbackThought(Cat $cat): string
    {
        return match ($cat->getMood()) {
            'happy' => "Life is purrfect right now! *happy tail swish*",
            'hungry' => "Hmm, I wonder when snack time is... *sniff sniff*",
            'sleepy' => "Just... five more minutes... zzz...",
            'grumpy' => "*judges silently from a distance*",
            'upset' => "I need some gentle pets and reassurance...",
            default => "Just another day being adorable! *stretches*",
        };
    }

    private function getFallbackBondingMessage(Cat $cat, int $bondingLevel): string
    {
        return match (true) {
            $bondingLevel < 10 => "*peeks curiously* Oh, hello there... who might you be?",
            $bondingLevel < 30 => "*perks up* Oh, it's you again! I remember your scent.",
            $bondingLevel < 50 => "*purrs softly* I was hoping you'd visit today!",
            $bondingLevel < 80 => "*runs over excitedly* My favorite human is here!",
            default => "*cuddles close* You're my absolute best friend, you know that? 💕",
        };
    }

    private function getFallbackFunFacts(Cat $cat): array
    {
        $breedFacts = [
            'Maine Coon' => [
                "Maine Coons are known as 'gentle giants' and can weigh up to 25 pounds!",
                "{$cat->getName()} loves water - a rare trait among cats.",
                "This breed is nicknamed 'the dog of the cat world' for their loyal nature.",
            ],
            'Siamese' => [
                "Siamese cats are one of the most vocal breeds - they love to 'talk'!",
                "{$cat->getName()}'s striking blue eyes are a Siamese trademark.",
                "This ancient breed originated in Thailand (formerly Siam).",
            ],
            'Ragdoll' => [
                "Ragdolls go limp when picked up, hence their name!",
                "{$cat->getName()} follows their favorite humans from room to room.",
                "This breed is known for their dog-like devotion to their owners.",
            ],
            'default' => [
                "{$cat->getName()} has their own unique spot in the cafe they claim as 'theirs'.",
                "This kitty knows exactly when treat time is - cats have amazing internal clocks!",
                "{$cat->getName()}'s purr frequency (25-150 Hz) can actually help heal bones!",
            ],
        ];

        return $breedFacts[$cat->getBreed()] ?? $breedFacts['default'];
    }
}
