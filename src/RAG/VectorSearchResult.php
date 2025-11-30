<?php

namespace App\RAG;

/**
 * Result from a vector similarity search.
 */
class VectorSearchResult
{
    public function __construct(
        public readonly string $documentId,
        public readonly string $content,
        public readonly string $category,
        public readonly float $score,
        public readonly array $metadata = [],
        public readonly array $keywords = [],
    ) {
    }

    /**
     * Convert to a KnowledgeDocument for compatibility.
     */
    public function toKnowledgeDocument(): KnowledgeDocument
    {
        return new KnowledgeDocument(
            id: $this->documentId,
            content: $this->content,
            category: $this->category,
            keywords: $this->keywords,
            metadata: $this->metadata,
        );
    }

    /**
     * Check if the result is above a relevance threshold.
     */
    public function isRelevant(float $threshold = 0.7): bool
    {
        return $this->score >= $threshold;
    }
}
