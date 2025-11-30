<?php

namespace App\Command;

use App\RAG\CatCafeKnowledgeBase;
use App\RAG\VectorStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:index-knowledge-base',
    description: 'Index the knowledge base into the Qdrant vector store for semantic search',
)]
class IndexKnowledgeBaseCommand extends Command
{
    public function __construct(
        private CatCafeKnowledgeBase $knowledgeBase,
        private VectorStore $vectorStore,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset the collection before indexing (deletes all existing vectors)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be indexed without actually indexing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reset = $input->getOption('reset');
        $dryRun = $input->getOption('dry-run');

        $io->title('Cat Cafe Knowledge Base Indexer');

        // Get all documents
        $documents = $this->knowledgeBase->getDocuments();
        $documentCount = count($documents);

        if ($documentCount === 0) {
            $io->warning('No documents found in the knowledge base.');
            return Command::SUCCESS;
        }

        // Group by category for display
        $byCategory = [];
        foreach ($documents as $doc) {
            $category = $doc->getCategory();
            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;
        }

        $io->section('Documents to index');
        $io->table(
            ['Category', 'Count'],
            array_map(fn($cat, $count) => [$cat, $count], array_keys($byCategory), array_values($byCategory))
        );
        $io->text(sprintf('Total: %d documents', $documentCount));

        if ($dryRun) {
            $io->note('Dry run mode - no changes made.');
            return Command::SUCCESS;
        }

        // Initialize or reset collection
        if ($reset) {
            $io->section('Resetting vector collection...');
            $this->vectorStore->resetCollection();
            $io->success('Collection reset successfully.');
        } else {
            $io->section('Initializing vector collection...');
            $this->vectorStore->initializeCollection();
        }

        // Index documents
        $io->section('Indexing documents...');
        $io->progressStart($documentCount);

        try {
            $this->vectorStore->indexDocuments($documents, function ($current, $total) use ($io) {
                $io->progressAdvance();
            });
        } catch (\Throwable $e) {
            $io->progressFinish();
            $io->error('Failed to index documents: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->progressFinish();

        // Verify
        $finalCount = $this->vectorStore->getDocumentCount();
        $io->success(sprintf(
            'Successfully indexed %d documents into the vector store.',
            $finalCount
        ));

        return Command::SUCCESS;
    }
}
