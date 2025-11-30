<?php

namespace App\RAG;

use Qdrant\Qdrant;
use Qdrant\Config;
use Qdrant\Models\Filter\Condition\MatchString;
use Qdrant\Models\Filter\Filter;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\PointStruct;
use Qdrant\Models\Request\CollectionConfig\CreateCollection;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\VectorStruct;

/**
 * Vector store service for semantic search using Qdrant.
 */
class VectorStore
{
    private ?Qdrant $client = null;
    private const VECTOR_SIZE = 768; // Google text-embedding-004 dimension

    public function __construct(
        private string $qdrantHost,
        private int $qdrantPort,
        private string $collectionName,
        private EmbeddingService $embeddingService,
    ) {
    }

    /**
     * Get the Qdrant client instance.
     */
    private function getClient(): Qdrant
    {
        if ($this->client === null) {
            $config = new Config($this->qdrantHost, $this->qdrantPort);
            $this->client = new Qdrant($config);
        }

        return $this->client;
    }

    /**
     * Initialize the collection if it doesn't exist.
     */
    public function initializeCollection(): void
    {
        $client = $this->getClient();

        try {
            $client->collections()->get($this->collectionName);
            // Collection exists
        } catch (\Throwable $e) {
            // Collection doesn't exist, create it
            $createCollection = new CreateCollection();
            $createCollection->addVector(new \Qdrant\Models\Request\VectorParams(
                self::VECTOR_SIZE,
                \Qdrant\Models\Request\VectorParams::DISTANCE_COSINE
            ));

            $client->collections()->create($this->collectionName, $createCollection);
        }
    }

    /**
     * Delete and recreate the collection (for re-indexing).
     */
    public function resetCollection(): void
    {
        $client = $this->getClient();

        try {
            $client->collections()->delete($this->collectionName);
        } catch (\Throwable $e) {
            // Collection might not exist, ignore
        }

        $this->initializeCollection();
    }

    /**
     * Index a single document.
     */
    public function indexDocument(KnowledgeDocument $document): void
    {
        $embedding = $this->embeddingService->embed($document->getContent());

        $point = new PointStruct(
            $this->generateNumericId($document->getId()),
            new VectorStruct($embedding),
            [
                'document_id' => $document->getId(),
                'content' => $document->getContent(),
                'category' => $document->getCategory(),
                'keywords' => implode(',', $document->getKeywords()),
                'metadata' => json_encode($document->getMetadata()),
            ]
        );

        $this->getClient()->points()->upsert(
            $this->collectionName,
            new PointsStruct([$point])
        );
    }

    /**
     * Index multiple documents in batch.
     *
     * @param KnowledgeDocument[] $documents
     */
    public function indexDocuments(array $documents, ?callable $progressCallback = null): void
    {
        if (empty($documents)) {
            return;
        }

        // Generate embeddings in batch for efficiency
        $texts = array_map(fn($doc) => $doc->getContent(), $documents);
        $embeddings = $this->embeddingService->embedBatch($texts);

        $points = [];
        foreach ($documents as $i => $document) {
            $points[] = new PointStruct(
                $this->generateNumericId($document->getId()),
                new VectorStruct($embeddings[$i]),
                [
                    'document_id' => $document->getId(),
                    'content' => $document->getContent(),
                    'category' => $document->getCategory(),
                    'keywords' => implode(',', $document->getKeywords()),
                    'metadata' => json_encode($document->getMetadata()),
                ]
            );

            if ($progressCallback) {
                $progressCallback($i + 1, count($documents));
            }
        }

        // Upsert in chunks to avoid memory issues
        $chunks = array_chunk($points, 100);
        foreach ($chunks as $chunk) {
            $this->getClient()->points()->upsert(
                $this->collectionName,
                new PointsStruct($chunk)
            );
        }
    }

    /**
     * Search for similar documents using vector similarity.
     *
     * @param string $query The search query
     * @param int $limit Maximum number of results
     * @param string|null $category Optional category filter
     * @return VectorSearchResult[]
     */
    public function search(string $query, int $limit = 5, ?string $category = null): array
    {
        $queryEmbedding = $this->embeddingService->embedQuery($query);

        $searchRequest = new SearchRequest(new VectorStruct($queryEmbedding));
        $searchRequest->setLimit($limit);
        $searchRequest->setWithPayload(true);

        if ($category !== null) {
            $filter = new Filter();
            $filter->addMust(new MatchString('category', $category));
            $searchRequest->setFilter($filter);
        }

        try {
            $response = $this->getClient()->points()->search($this->collectionName, $searchRequest);
            $results = [];

            foreach ($response['result'] as $hit) {
                $results[] = new VectorSearchResult(
                    documentId: $hit['payload']['document_id'],
                    content: $hit['payload']['content'],
                    category: $hit['payload']['category'],
                    score: $hit['score'],
                    metadata: json_decode($hit['payload']['metadata'] ?? '{}', true),
                    keywords: explode(',', $hit['payload']['keywords'] ?? '')
                );
            }

            return $results;
        } catch (\Throwable $e) {
            // Return empty results if search fails (e.g., collection not initialized)
            return [];
        }
    }

    /**
     * Search across multiple categories.
     *
     * @param string[] $categories
     * @return VectorSearchResult[]
     */
    public function searchMultipleCategories(string $query, array $categories, int $limitPerCategory = 2): array
    {
        $results = [];

        foreach ($categories as $category) {
            $categoryResults = $this->search($query, $limitPerCategory, $category);
            $results = array_merge($results, $categoryResults);
        }

        // Sort by score descending
        usort($results, fn($a, $b) => $b->score <=> $a->score);

        return $results;
    }

    /**
     * Check if the collection exists and has documents.
     */
    public function isInitialized(): bool
    {
        try {
            $info = $this->getClient()->collections()->get($this->collectionName);
            return ($info['result']['points_count'] ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the number of documents in the collection.
     */
    public function getDocumentCount(): int
    {
        try {
            $info = $this->getClient()->collections()->get($this->collectionName);
            return $info['result']['points_count'] ?? 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Generate a numeric ID from a string ID (Qdrant requires numeric IDs).
     */
    private function generateNumericId(string $stringId): int
    {
        // Use CRC32 for fast, collision-resistant numeric IDs
        return abs(crc32($stringId));
    }
}
