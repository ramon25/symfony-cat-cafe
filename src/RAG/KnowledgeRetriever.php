<?php

namespace App\RAG;

use App\Entity\Cat;

/**
 * Retrieves relevant documents from the knowledge base based on user queries.
 *
 * Uses keyword-based semantic matching to find the most relevant content
 * for augmenting AI responses.
 */
class KnowledgeRetriever
{
    public function __construct(
        private CatCafeKnowledgeBase $knowledgeBase,
    ) {
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
        // Get emotional support content (highest priority for therapy)
        $emotionalResult = $this->retrieve($query, 2, ['emotions']);

        // Get relevant wisdom
        $wisdomResult = $this->retrieve($query, 2, ['wisdom']);

        // Get cat-specific content if a cat is provided
        $catContent = [];
        if ($cat) {
            $breedResult = $this->retrieve($cat->getBreed(), 1, ['breeds']);
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
