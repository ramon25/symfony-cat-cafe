<?php

namespace App\RAG;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for generating text embeddings using Google's Generative AI API.
 *
 * Uses the text-embedding-004 model which produces 768-dimensional vectors.
 */
class EmbeddingService
{
    private const EMBEDDING_MODEL = 'text-embedding-004';
    private const EMBEDDING_DIMENSION = 768;
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:embedContent';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $geminiApiKey,
    ) {
    }

    /**
     * Generate an embedding vector for a single text.
     *
     * @return float[] Embedding vector of dimension 768
     * @throws \RuntimeException If the API call fails
     */
    public function embed(string $text): array
    {
        if (empty($this->geminiApiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY is not configured');
        }

        $url = sprintf(self::API_URL, self::EMBEDDING_MODEL) . '?key=' . $this->geminiApiKey;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'model' => 'models/' . self::EMBEDDING_MODEL,
                    'content' => [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ],
                    'taskType' => 'RETRIEVAL_DOCUMENT',
                ],
            ]);

            $data = $response->toArray();

            if (!isset($data['embedding']['values'])) {
                throw new \RuntimeException('Invalid response from embedding API');
            }

            return $data['embedding']['values'];
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate embedding: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate embeddings for multiple texts in batch.
     *
     * @param string[] $texts
     * @return array<int, float[]> Array of embedding vectors
     */
    public function embedBatch(array $texts): array
    {
        if (empty($this->geminiApiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY is not configured');
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:batchEmbedContents?key=%s',
            self::EMBEDDING_MODEL,
            $this->geminiApiKey
        );

        $requests = array_map(fn($text) => [
            'model' => 'models/' . self::EMBEDDING_MODEL,
            'content' => [
                'parts' => [['text' => $text]]
            ],
            'taskType' => 'RETRIEVAL_DOCUMENT',
        ], $texts);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => ['requests' => $requests],
            ]);

            $data = $response->toArray();

            if (!isset($data['embeddings'])) {
                throw new \RuntimeException('Invalid response from batch embedding API');
            }

            return array_map(fn($e) => $e['values'], $data['embeddings']);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate batch embeddings: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate an embedding for a search query.
     * Uses RETRIEVAL_QUERY task type for better search results.
     *
     * @return float[]
     */
    public function embedQuery(string $query): array
    {
        if (empty($this->geminiApiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY is not configured');
        }

        $url = sprintf(self::API_URL, self::EMBEDDING_MODEL) . '?key=' . $this->geminiApiKey;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'model' => 'models/' . self::EMBEDDING_MODEL,
                    'content' => [
                        'parts' => [
                            ['text' => $query]
                        ]
                    ],
                    'taskType' => 'RETRIEVAL_QUERY',
                ],
            ]);

            $data = $response->toArray();

            if (!isset($data['embedding']['values'])) {
                throw new \RuntimeException('Invalid response from embedding API');
            }

            return $data['embedding']['values'];
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate query embedding: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the dimension of the embedding vectors.
     */
    public function getDimension(): int
    {
        return self::EMBEDDING_DIMENSION;
    }

    /**
     * Get the model name being used.
     */
    public function getModelName(): string
    {
        return self::EMBEDDING_MODEL;
    }
}
