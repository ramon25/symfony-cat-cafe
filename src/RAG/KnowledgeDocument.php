<?php

namespace App\RAG;

/**
 * A simple document for the knowledge base with content and metadata.
 */
class KnowledgeDocument
{
    public function __construct(
        private string $id,
        private string $content,
        private string $category,
        private array $keywords = [],
        private array $metadata = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Calculate relevance score against a query using keyword matching.
     */
    public function calculateRelevance(string $query): float
    {
        $query = strtolower($query);
        $queryWords = $this->tokenize($query);
        $score = 0.0;

        // Check keyword matches (highest weight)
        foreach ($this->keywords as $keyword) {
            if (str_contains($query, strtolower($keyword))) {
                $score += 3.0;
            }
        }

        // Check content word matches
        $contentLower = strtolower($this->content);
        foreach ($queryWords as $word) {
            if (strlen($word) > 2 && str_contains($contentLower, $word)) {
                $score += 1.0;
            }
        }

        // Bonus for category match if query mentions it
        if (str_contains($query, strtolower($this->category))) {
            $score += 2.0;
        }

        return $score;
    }

    private function tokenize(string $text): array
    {
        // Remove punctuation and split into words
        $text = preg_replace('/[^\w\s]/', '', $text);
        return array_filter(explode(' ', strtolower($text)), fn($w) => strlen($w) > 2);
    }
}
