<?php

namespace App\RAG;

/**
 * Result of a retrieval operation containing matched documents and scores.
 */
class RetrievalResult
{
    /**
     * @param string $query The original query
     * @param KnowledgeDocument[] $documents Retrieved documents
     * @param float[] $scores Relevance scores for each document
     */
    public function __construct(
        private string $query,
        private array $documents,
        private array $scores = [],
    ) {
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return KnowledgeDocument[]
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * @return float[]
     */
    public function getScores(): array
    {
        return $this->scores;
    }

    public function isEmpty(): bool
    {
        return empty($this->documents);
    }

    public function count(): int
    {
        return count($this->documents);
    }

    /**
     * Get documents as a simple array of content strings.
     *
     * @return string[]
     */
    public function getContents(): array
    {
        return array_map(fn($doc) => $doc->getContent(), $this->documents);
    }

    /**
     * Get the top document if available.
     */
    public function getTopDocument(): ?KnowledgeDocument
    {
        return $this->documents[0] ?? null;
    }

    /**
     * Get documents filtered by category.
     *
     * @return KnowledgeDocument[]
     */
    public function getByCategory(string $category): array
    {
        return array_filter(
            $this->documents,
            fn($doc) => $doc->getCategory() === $category
        );
    }
}
