<?php

namespace App\Service;

use App\Entity\Cat;
use App\Entity\ChatMessage;
use App\RAG\KnowledgeRetriever;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * AI-powered cat therapist service that provides life advice from a cat's perspective.
 * Enhanced with RAG (Retrieval-Augmented Generation) for contextual responses.
 */
class CatTherapistService
{
    public function __construct(
        private AgentInterface $catTherapistAgent,
        private KnowledgeRetriever $knowledgeRetriever,
    ) {
    }

    /**
     * Get AI-generated advice from a specific cat.
     *
     * @param ChatMessage[] $chatHistory Previous messages in the conversation
     */
    public function getAdvice(Cat $cat, string $userMessage, array $chatHistory = []): string
    {
        // Retrieve relevant context using RAG
        $retrievedContext = $this->retrieveContext($userMessage, $cat);

        // Build system context with cat info and retrieved knowledge
        $catContext = $this->buildCatContext($cat, $retrievedContext);

        $messages = new MessageBag(Message::forSystem($catContext));

        // Add conversation history (last 10 messages for context)
        $recentHistory = array_slice($chatHistory, -10);
        foreach ($recentHistory as $msg) {
            if ($msg->isFromUser()) {
                $messages->add(Message::ofUser($msg->getContent()));
            } else {
                $messages->add(Message::ofAssistant($msg->getContent()));
            }
        }

        // Add current user message
        $messages->add(Message::ofUser($userMessage));

        try {
            $response = $this->catTherapistAgent->call($messages);
            return $response->getContent();
        } catch (\Throwable $e) {
            // Fallback to a cute error message if AI fails
            return sprintf(
                "*%s tilts head curiously* Mrrrow? My whiskers are tingling strangely... " .
                "Perhaps try asking again after I've had a little nap? 😺",
                $cat->getName()
            );
        }
    }

    /**
     * Retrieve relevant context from the knowledge base.
     */
    private function retrieveContext(string $userMessage, Cat $cat): string
    {
        $result = $this->knowledgeRetriever->retrieveForTherapy($userMessage, $cat);

        if ($result->isEmpty()) {
            return '';
        }

        return $this->knowledgeRetriever->formatAsContext($result);
    }

    /**
     * Build context about the cat for the AI to use.
     */
    private function buildCatContext(Cat $cat, string $retrievedContext = ''): string
    {
        $moodDescriptions = [
            'happy' => 'feeling joyful and energetic, ready to share positivity',
            'content' => 'feeling calm and peaceful, offering thoughtful wisdom',
            'grumpy' => 'a bit irritable but still caring deep down',
            'upset' => 'feeling a little down but wanting to help others feel better',
            'hungry' => 'thinking about food but still willing to offer advice',
            'sleepy' => 'drowsy and dreamy, offering gentle, soothing wisdom',
        ];

        $moodDescription = $moodDescriptions[$cat->getMood()] ?? 'in a mysterious mood';

        $baseContext = sprintf(
            "You are %s, a %d-year-old %s %s cat. You are currently %s. " .
            "Your personality: %s. " .
            "Respond as this specific cat would, incorporating your breed traits and current mood into your advice.",
            $cat->getName(),
            $cat->getAge(),
            $cat->getColor(),
            $cat->getBreed(),
            $moodDescription,
            $cat->getDescription() ?? 'A lovable cafe cat who enjoys making visitors smile'
        );

        // Append retrieved knowledge context if available
        if (!empty($retrievedContext)) {
            $baseContext .= "\n\n--- KNOWLEDGE BASE ---\n" . $retrievedContext .
                "\nUse the above knowledge naturally in your response when relevant. " .
                "Don't explicitly mention that you're using a knowledge base - just incorporate the wisdom and information seamlessly.";
        }

        return $baseContext;
    }
}
