<?php

namespace App\Service;

use App\Entity\Cat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * AI-powered service for generating cat images using Google Gemini Imagen API.
 * Images are generated based on cat characteristics and cached in the database.
 */
class CatImageGeneratorService
{
    private const IMAGEN_MODEL = 'imagen-3.0-generate-001';
    private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null,
        private ?string $geminiApiKey = null,
    ) {
        $this->geminiApiKey = $_ENV['GEMINI_API_KEY'] ?? null;
    }

    /**
     * Get or generate an AI-powered image for a cat.
     * Uses cached version if available, generates new one if not.
     */
    public function getImage(Cat $cat, bool $forceRegenerate = false): ?string
    {
        // Return cached version if available and not forcing regeneration
        if (!$forceRegenerate && $cat->hasAiImage()) {
            return $cat->getAiImageBase64();
        }

        $imageData = $this->generateImage($cat);

        if ($imageData !== null) {
            $prompt = $this->buildImagePrompt($cat);
            $cat->setAiImageBase64($imageData);
            $cat->setAiImagePrompt($prompt);
            $cat->setAiImageGeneratedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }

        return $imageData;
    }

    /**
     * Generate an AI image for a cat using Gemini Imagen API.
     */
    public function generateImage(Cat $cat): ?string
    {
        if (empty($this->geminiApiKey)) {
            $this->logger?->warning('GEMINI_API_KEY not configured for image generation');
            return null;
        }

        $prompt = $this->buildImagePrompt($cat);

        try {
            $response = $this->httpClient->request('POST', $this->getApiEndpoint(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'instances' => [
                        ['prompt' => $prompt]
                    ],
                    'parameters' => [
                        'sampleCount' => 1,
                        'aspectRatio' => '1:1',
                        'personGeneration' => 'dont_allow',
                    ],
                ],
            ]);

            $data = $response->toArray();

            // Extract base64 image from response
            if (isset($data['predictions'][0]['bytesBase64Encoded'])) {
                return $data['predictions'][0]['bytesBase64Encoded'];
            }

            // Alternative response format
            if (isset($data['images'][0]['bytes'])) {
                return $data['images'][0]['bytes'];
            }

            $this->logger?->warning('Unexpected Imagen API response format', ['response' => $data]);
            return null;

        } catch (\Throwable $e) {
            $this->logger?->error('Failed to generate cat image', [
                'cat_id' => $cat->getId(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build a descriptive prompt for generating the cat image.
     */
    private function buildImagePrompt(Cat $cat): string
    {
        $mood = $cat->getMood();
        $moodDescription = match ($mood) {
            'happy' => 'happy and playful, with bright eyes',
            'content' => 'calm and relaxed, looking peaceful',
            'grumpy' => 'slightly grumpy but adorable',
            'upset' => 'a bit sad with big pleading eyes',
            'hungry' => 'looking expectantly, perhaps near a food bowl',
            'sleepy' => 'drowsy and cozy, maybe yawning or half-asleep',
            default => 'curious and alert',
        };

        $breedStyle = $this->getBreedStyleDescription($cat->getBreed());
        $colorDescription = $this->getColorDescription($cat->getColor());

        return sprintf(
            'A beautiful, realistic photograph of a %s %s cat. The cat is %s. ' .
            '%s ' .
            'Warm, soft lighting, professional pet photography style, ' .
            'high quality, detailed fur texture, cute and endearing expression. ' .
            'Cat cafe atmosphere with cozy background.',
            $colorDescription,
            $cat->getBreed(),
            $moodDescription,
            $breedStyle
        );
    }

    /**
     * Get breed-specific style descriptions for the prompt.
     */
    private function getBreedStyleDescription(string $breed): string
    {
        return match ($breed) {
            'Persian' => 'Long, luxurious fur, flat face, regal appearance.',
            'Maine Coon' => 'Large, majestic cat with tufted ears and long flowing coat.',
            'Siamese' => 'Sleek, elegant body with distinctive color points and striking blue eyes.',
            'British Shorthair' => 'Round face, dense plush coat, teddy bear-like appearance.',
            'Ragdoll' => 'Large, fluffy cat with striking blue eyes and soft, silky coat.',
            'Bengal' => 'Athletic, muscular cat with wild-looking spotted or marbled coat.',
            'Abyssinian' => 'Slender, elegant cat with warm ticked coat and large ears.',
            'Scottish Fold' => 'Round face with distinctive folded ears, sweet expression.',
            'Sphynx' => 'Hairless cat with wrinkled skin, large ears, and curious expression.',
            'Russian Blue' => 'Elegant cat with short, dense blue-gray coat and bright green eyes.',
            'Norwegian Forest Cat' => 'Large, fluffy cat with thick coat and wild, forest-like appearance.',
            'Burmese' => 'Compact, muscular cat with glossy coat and expressive golden eyes.',
            'Birman' => 'Long-haired cat with color points and distinctive white paws.',
            'Devon Rex' => 'Pixie-like cat with large ears, curly coat, and mischievous expression.',
            'Exotic Shorthair' => 'Persian-like face with shorter, plush coat.',
            default => 'Beautiful domestic cat with charming features.',
        };
    }

    /**
     * Get color descriptions for the prompt.
     */
    private function getColorDescription(string $color): string
    {
        return match (strtolower($color)) {
            'orange', 'ginger' => 'warm orange ginger',
            'black' => 'sleek black',
            'white' => 'pure white',
            'gray', 'grey' => 'elegant gray',
            'tabby' => 'striped tabby',
            'calico' => 'tricolor calico (orange, black, and white)',
            'tuxedo' => 'tuxedo (black and white)',
            'siamese' => 'cream-colored with dark points',
            'tortoiseshell', 'tortie' => 'tortoiseshell patterned',
            'cream' => 'soft cream-colored',
            default => $color,
        };
    }

    /**
     * Get the API endpoint for Imagen generation.
     */
    private function getApiEndpoint(): string
    {
        return sprintf(
            '%s/models/%s:predict?key=%s',
            self::API_BASE_URL,
            self::IMAGEN_MODEL,
            $this->geminiApiKey
        );
    }

    /**
     * Check if the image generation service is available.
     */
    public function isAvailable(): bool
    {
        return !empty($this->geminiApiKey);
    }
}
