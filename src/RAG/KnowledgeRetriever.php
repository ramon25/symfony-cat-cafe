<?php

namespace App\RAG;

use App\Entity\Cat;

/**
 * Retrieves relevant documents from the knowledge base based on user queries.
 *
 * Supports two retrieval modes:
 * - Vector search (semantic) via Qdrant when available
 * - Keyword-based fallback when vector store is not initialized
 */
class KnowledgeRetriever
{
    public function __construct(
        private CatCafeKnowledgeBase $knowledgeBase,
        private ?VectorStore $vectorStore = null,
    ) {
    }

    /**
     * Check if vector search is available.
     */
    public function isVectorSearchAvailable(): bool
    {
        return $this->vectorStore !== null && $this->vectorStore->isInitialized();
    }

    /**
     * Retrieve relevant documents for a user query.
     *
     * @param string $query The user's message/question
     * @param int $limit Maximum number of documents to return
     * @param string[] $categories Optional filter by category
     * @return RetrievalResult
     */
    public function retrieve(string $query, int $limit = 5, array $categories = []): RetrievalResult
    {
        // Try vector search first if available
        if ($this->isVectorSearchAvailable()) {
            return $this->vectorRetrieve($query, $limit, $categories);
        }

        // Fallback to keyword search
        return $this->keywordRetrieve($query, $limit, $categories);
    }

    /**
     * Vector-based semantic retrieval.
     */
    private function vectorRetrieve(string $query, int $limit, array $categories): RetrievalResult
    {
        try {
            if (!empty($categories)) {
                $results = $this->vectorStore->searchMultipleCategories($query, $categories, (int)ceil($limit / count($categories)));
                $results = array_slice($results, 0, $limit);
            } else {
                $results = $this->vectorStore->search($query, $limit);
            }

            // Convert VectorSearchResult to KnowledgeDocument
            $documents = array_map(fn($r) => $r->toKnowledgeDocument(), $results);
            $scores = array_map(fn($r) => $r->score, $results);

            return new RetrievalResult(
                query: $query,
                documents: $documents,
                scores: $scores
            );
        } catch (\Throwable $e) {
            // Fallback to keyword search on error
            return $this->keywordRetrieve($query, $limit, $categories);
        }
    }

    /**
     * Keyword-based retrieval (fallback).
     */
    private function keywordRetrieve(string $query, int $limit, array $categories): RetrievalResult
    {
        $documents = $this->knowledgeBase->getDocuments();

        // Filter by categories if specified
        if (!empty($categories)) {
            $documents = array_filter(
                $documents,
                fn($doc) => in_array($doc->getCategory(), $categories)
            );
        }

        // Score all documents
        $scored = [];
        foreach ($documents as $doc) {
            $score = $doc->calculateRelevance($query);
            if ($score > 0) {
                $scored[] = ['document' => $doc, 'score' => $score];
            }
        }

        // Sort by score descending
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // Take top results
        $topResults = array_slice($scored, 0, $limit);

        return new RetrievalResult(
            query: $query,
            documents: array_map(fn($r) => $r['document'], $topResults),
            scores: array_map(fn($r) => $r['score'], $topResults)
        );
    }

    /**
     * Retrieve context specifically for therapy sessions.
     * Prioritizes emotional support and wisdom content.
     */
    public function retrieveForTherapy(string $query, ?Cat $cat = null): RetrievalResult
    {
        // Use vector search if available for better semantic matching
        if ($this->isVectorSearchAvailable()) {
            return $this->vectorRetrieveForTherapy($query, $cat);
        }

        return $this->keywordRetrieveForTherapy($query, $cat);
    }

    /**
     * Vector-based therapy retrieval.
     */
    private function vectorRetrieveForTherapy(string $query, ?Cat $cat): RetrievalResult
    {
        // Search across therapy-relevant categories
        $categories = ['emotions', 'wisdom'];
        if ($cat) {
            $categories[] = 'breeds';
        }

        $results = $this->vectorStore->searchMultipleCategories($query, $categories, 2);

        // If a cat is provided, also search for breed-specific info
        if ($cat) {
            $breedResults = $this->vectorStore->search($cat->getBreed(), 1, 'breeds');
            $results = array_merge($results, $breedResults);
        }

        // Deduplicate by document ID
        $seen = [];
        $unique = [];
        foreach ($results as $result) {
            if (!isset($seen[$result->documentId])) {
                $seen[$result->documentId] = true;
                $unique[] = $result;
            }
        }

        // Sort by score and limit
        usort($unique, fn($a, $b) => $b->score <=> $a->score);
        $unique = array_slice($unique, 0, 5);

        return new RetrievalResult(
            query: $query,
            documents: array_map(fn($r) => $r->toKnowledgeDocument(), $unique),
            scores: array_map(fn($r) => $r->score, $unique)
        );
    }

    /**
     * Keyword-based therapy retrieval (fallback).
     */
    private function keywordRetrieveForTherapy(string $query, ?Cat $cat): RetrievalResult
    {
        // Get emotional support content (highest priority for therapy)
        $emotionalResult = $this->keywordRetrieve($query, 2, ['emotions']);

        // Get relevant wisdom
        $wisdomResult = $this->keywordRetrieve($query, 2, ['wisdom']);

        // Get cat-specific content if a cat is provided
        $catContent = [];
        if ($cat) {
            $breedResult = $this->keywordRetrieve($cat->getBreed(), 1, ['breeds']);
            $catContent = $breedResult->getDocuments();
        }

        // Combine results, removing duplicates
        $allDocs = array_merge(
            $emotionalResult->getDocuments(),
            $wisdomResult->getDocuments(),
            $catContent
        );

        // Deduplicate by ID
        $uniqueDocs = [];
        $seenIds = [];
        foreach ($allDocs as $doc) {
            if (!in_array($doc->getId(), $seenIds)) {
                $uniqueDocs[] = $doc;
                $seenIds[] = $doc->getId();
            }
        }

        return new RetrievalResult(
            query: $query,
            documents: array_slice($uniqueDocs, 0, 5),
            scores: [] // Combined scores not meaningful
        );
    }

    /**
     * Retrieve cafe information.
     */
    public function retrieveCafeInfo(string $query): RetrievalResult
    {
        return $this->retrieve($query, 3, ['cafe', 'care']);
    }

    /**
     * Format retrieved documents as context for the AI.
     */
    public function formatAsContext(RetrievalResult $result): string
    {
        if (empty($result->getDocuments())) {
            return '';
        }

        $context = "Relevant knowledge to incorporate in your response:\n\n";

        foreach ($result->getDocuments() as $i => $doc) {
            $category = ucfirst($doc->getCategory());
            $context .= sprintf("[%s]: %s\n\n", $category, $doc->getContent());
        }

        return $context;
    }
}
