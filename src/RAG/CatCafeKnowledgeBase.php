<?php

namespace App\RAG;

use App\Entity\Cat;
use App\Repository\CatRepository;

/**
 * Knowledge base containing all retrievable content for RAG.
 *
 * Categories:
 * - wisdom: Cat wisdom quotes for emotional support
 * - cafe: Information about the cafe
 * - care: Cat care tips and advice
 * - emotions: Emotional support content mapped to feelings
 * - breeds: Information about cat breeds
 */
class CatCafeKnowledgeBase
{
    /** @var KnowledgeDocument[] */
    private array $documents = [];
    private bool $initialized = false;

    public function __construct(
        private CatRepository $catRepository,
    ) {
    }

    /**
     * Get all documents in the knowledge base.
     *
     * @return KnowledgeDocument[]
     */
    public function getDocuments(): array
    {
        $this->ensureInitialized();
        return $this->documents;
    }

    /**
     * Get documents by category.
     *
     * @return KnowledgeDocument[]
     */
    public function getByCategory(string $category): array
    {
        $this->ensureInitialized();
        return array_filter($this->documents, fn($doc) => $doc->getCategory() === $category);
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->loadWisdomDocuments();
        $this->loadCafeDocuments();
        $this->loadCareDocuments();
        $this->loadEmotionalSupportDocuments();
        $this->loadBreedDocuments();
        $this->loadCatProfileDocuments();

        $this->initialized = true;
    }

    private function loadWisdomDocuments(): void
    {
        $wisdoms = [
            ['content' => 'A warm lap is worth a thousand words.', 'keywords' => ['comfort', 'warmth', 'connection', 'love', 'lonely']],
            ['content' => 'The best things in life are worth waiting for... like dinner.', 'keywords' => ['patience', 'waiting', 'anticipation', 'reward']],
            ['content' => 'Nap often, for dreams await the patient soul.', 'keywords' => ['rest', 'sleep', 'tired', 'exhausted', 'dreams', 'patience']],
            ['content' => 'Curiosity didn\'t kill the cat — it made them wiser.', 'keywords' => ['curiosity', 'learning', 'growth', 'fear', 'trying', 'new']],
            ['content' => 'If it fits, sits. This is the way.', 'keywords' => ['acceptance', 'comfort', 'belonging', 'finding', 'place']],
            ['content' => 'The early bird gets the worm, but the wise cat waits for treats.', 'keywords' => ['wisdom', 'timing', 'patience', 'strategy']],
            ['content' => 'A gentle purr can heal any troubled heart.', 'keywords' => ['healing', 'comfort', 'sadness', 'pain', 'heartbreak', 'loss']],
            ['content' => 'Never underestimate the power of a well-timed head boop.', 'keywords' => ['affection', 'connection', 'friendship', 'love', 'gesture']],
            ['content' => 'Chase your dreams as fiercely as you chase the red dot.', 'keywords' => ['dreams', 'goals', 'ambition', 'determination', 'chase', 'pursue']],
            ['content' => 'Sometimes the best view is from the top of the bookshelf.', 'keywords' => ['perspective', 'overview', 'problems', 'distance', 'clarity']],
            ['content' => 'Trust your whiskers — they know the way.', 'keywords' => ['intuition', 'trust', 'instinct', 'decision', 'guidance', 'confused']],
            ['content' => 'Every cardboard box holds infinite possibilities.', 'keywords' => ['possibilities', 'opportunity', 'creativity', 'simple', 'joy']],
            ['content' => 'The sun always shines for those who find the sunny spot.', 'keywords' => ['optimism', 'positivity', 'hope', 'finding', 'happiness', 'depressed']],
            ['content' => 'Stretch before any important endeavor. Actually, stretch always.', 'keywords' => ['preparation', 'self-care', 'health', 'wellness', 'start']],
            ['content' => 'True friends will always share their warmth.', 'keywords' => ['friendship', 'friends', 'support', 'warmth', 'lonely', 'alone']],
            ['content' => 'Knock things off the table of doubt.', 'keywords' => ['doubt', 'confidence', 'action', 'decisive', 'worry', 'overthinking']],
            ['content' => 'The path to happiness is paved with soft blankets.', 'keywords' => ['happiness', 'comfort', 'peace', 'contentment', 'simple']],
            ['content' => 'Always land on your feet, but don\'t be afraid to fall.', 'keywords' => ['resilience', 'failure', 'fear', 'courage', 'falling', 'mistake']],
            ['content' => 'A belly rub a day keeps the grumpies away.', 'keywords' => ['self-care', 'mood', 'grumpy', 'angry', 'stress', 'relax']],
            ['content' => 'The quietest meow often speaks the loudest truth.', 'keywords' => ['quiet', 'listening', 'truth', 'voice', 'heard', 'ignored']],
            ['content' => 'Life is better with a little catnip.', 'keywords' => ['fun', 'joy', 'playfulness', 'serious', 'lighten', 'enjoy']],
            ['content' => 'Judge not by the scratching post, but by the character.', 'keywords' => ['judgment', 'character', 'appearance', 'surface', 'deeper']],
            ['content' => 'Patience is the art of hiding your anticipation for treats.', 'keywords' => ['patience', 'waiting', 'anticipation', 'impatient', 'want']],
            ['content' => 'Even the mightiest lion started as a curious kitten.', 'keywords' => ['beginnings', 'growth', 'starting', 'inexperience', 'beginner', 'new']],
            ['content' => 'Elegance is an attitude, not just a coat color.', 'keywords' => ['confidence', 'attitude', 'self-esteem', 'appearance', 'beauty']],
            ['content' => 'The window to the soul is best viewed from a windowsill.', 'keywords' => ['reflection', 'soul', 'contemplation', 'thinking', 'meaning']],
            ['content' => 'In every ending, there is a new beginning... especially at 3 AM.', 'keywords' => ['endings', 'beginnings', 'change', 'transition', 'loss', 'moving']],
            ['content' => 'Share your toys generously, except the favorite one.', 'keywords' => ['sharing', 'generosity', 'boundaries', 'limits', 'giving']],
            ['content' => 'The greatest journeys begin with a single pounce.', 'keywords' => ['journey', 'start', 'beginning', 'action', 'first', 'step']],
            ['content' => 'Let your inner kitten guide you to joy.', 'keywords' => ['joy', 'playfulness', 'inner', 'child', 'fun', 'serious']],
        ];

        foreach ($wisdoms as $i => $wisdom) {
            $this->documents[] = new KnowledgeDocument(
                id: "wisdom_{$i}",
                content: $wisdom['content'],
                category: 'wisdom',
                keywords: $wisdom['keywords'],
                metadata: ['type' => 'fortune_wisdom']
            );
        }
    }

    private function loadCafeDocuments(): void
    {
        $cafeInfo = [
            [
                'content' => 'Welcome to Whiskers & Wonders Cat Cafe! We are a cozy sanctuary where cat lovers can enjoy delicious treats while spending quality time with our adorable resident cats. Each cat has their own unique personality and is available for adoption to loving homes.',
                'keywords' => ['cafe', 'about', 'welcome', 'what', 'where', 'visit'],
            ],
            [
                'content' => 'Our cafe hours are 10 AM to 8 PM daily. We recommend booking ahead for therapy sessions with our cats, as they are quite popular! Walk-ins are welcome for general cafe visits.',
                'keywords' => ['hours', 'open', 'visit', 'booking', 'appointment', 'when'],
            ],
            [
                'content' => 'Cat therapy sessions at Whiskers & Wonders provide a unique experience where you can share your thoughts and receive wisdom from our resident feline therapists. Each cat offers a different perspective based on their personality and mood.',
                'keywords' => ['therapy', 'session', 'advice', 'help', 'talk', 'counsel'],
            ],
            [
                'content' => 'All our cats are rescue cats given a second chance at life. By visiting and potentially adopting, you help support our mission of finding forever homes for cats in need.',
                'keywords' => ['rescue', 'adoption', 'adopt', 'mission', 'help', 'support', 'home'],
            ],
            [
                'content' => 'Our cafe menu features cat-themed treats and beverages, including the Purrfect Latte, Meow Muffins, and Whisker Cookies. All proceeds help care for our resident cats.',
                'keywords' => ['menu', 'food', 'drinks', 'eat', 'coffee', 'treats'],
            ],
        ];

        foreach ($cafeInfo as $i => $info) {
            $this->documents[] = new KnowledgeDocument(
                id: "cafe_{$i}",
                content: $info['content'],
                keywords: $info['keywords'],
                category: 'cafe',
                metadata: ['type' => 'cafe_info']
            );
        }
    }

    private function loadCareDocuments(): void
    {
        $careTips = [
            [
                'content' => 'Cats thrive on routine. Try to feed, play, and rest at consistent times each day. This helps reduce anxiety and creates a sense of security.',
                'keywords' => ['routine', 'schedule', 'anxiety', 'stress', 'calm', 'regular'],
            ],
            [
                'content' => 'Play is essential for a cat\'s mental and physical health. Just 15-20 minutes of interactive play daily can significantly improve mood and reduce behavioral issues.',
                'keywords' => ['play', 'exercise', 'health', 'mental', 'behavior', 'active'],
            ],
            [
                'content' => 'Cats communicate through body language. A slow blink means trust and affection. Ears back might indicate fear or aggression. A relaxed tail shows contentment.',
                'keywords' => ['communication', 'body', 'language', 'understand', 'feelings', 'mood'],
            ],
            [
                'content' => 'Creating vertical spaces like cat trees or shelves gives cats a sense of security and territory. Cats feel safer when they can observe from above.',
                'keywords' => ['space', 'territory', 'safety', 'environment', 'home', 'comfortable'],
            ],
            [
                'content' => 'Quality time with your cat strengthens your bond. Simply sitting near them, gentle petting, or quiet companionship matters more than constant interaction.',
                'keywords' => ['bonding', 'time', 'relationship', 'connection', 'love', 'together'],
            ],
        ];

        foreach ($careTips as $i => $tip) {
            $this->documents[] = new KnowledgeDocument(
                id: "care_{$i}",
                content: $tip['content'],
                keywords: $tip['keywords'],
                category: 'care',
                metadata: ['type' => 'care_tip']
            );
        }
    }

    private function loadEmotionalSupportDocuments(): void
    {
        $emotionalSupport = [
            [
                'content' => 'Feeling anxious is like having your whiskers constantly twitching. The key is to find your safe cardboard box - a place or activity that makes you feel secure. Start with deep breaths and small, manageable steps.',
                'keywords' => ['anxious', 'anxiety', 'worried', 'nervous', 'panic', 'stress', 'overwhelmed'],
            ],
            [
                'content' => 'Sadness is like a rainy day that keeps you from the sunny spot. Remember, even cats have their off days. Allow yourself to feel, but know that the sun will return. Surround yourself with warmth and comfort.',
                'keywords' => ['sad', 'sadness', 'depressed', 'down', 'unhappy', 'crying', 'tears', 'grief'],
            ],
            [
                'content' => 'Loneliness is tough, even for independent cats. Connection matters. Reach out to someone, even if it feels hard. Sometimes a simple meow - or message - can open doors to companionship.',
                'keywords' => ['lonely', 'alone', 'isolated', 'friendless', 'single', 'missing', 'connection'],
            ],
            [
                'content' => 'Anger is like when someone disturbs your nap - intense but temporary. Acknowledge the feeling without acting on it rashly. Find a healthy outlet, like a good scratch on the scratching post of life.',
                'keywords' => ['angry', 'anger', 'mad', 'furious', 'frustrated', 'annoyed', 'irritated'],
            ],
            [
                'content' => 'Fear of failure keeps many from pouncing on opportunities. Remember: cats don\'t always catch the red dot, but they never stop trying. Each attempt is practice, not failure.',
                'keywords' => ['fear', 'failure', 'afraid', 'scared', 'failing', 'mistake', 'imposter'],
            ],
            [
                'content' => 'Work stress can feel like chasing your tail endlessly. Set boundaries like a cat guards their territory. Rest is not laziness - it\'s essential maintenance for peak performance.',
                'keywords' => ['work', 'job', 'career', 'stress', 'burnout', 'tired', 'exhausted', 'boss'],
            ],
            [
                'content' => 'Relationship troubles? Cats know that sometimes you need space, and sometimes you need closeness. Communication is key - express your needs clearly, listen actively, and respect boundaries.',
                'keywords' => ['relationship', 'partner', 'boyfriend', 'girlfriend', 'spouse', 'marriage', 'dating', 'love'],
            ],
            [
                'content' => 'Feeling stuck is like being in a room with a closed door. But remember, cats are persistent - we sit and wait, we meow, we find another way. Your breakthrough is coming.',
                'keywords' => ['stuck', 'trapped', 'blocked', 'stagnant', 'nowhere', 'hopeless', 'giving'],
            ],
            [
                'content' => 'Self-doubt is that voice saying you\'re not good enough. But look at any cat - we never doubt our worthiness of treats and love. You deserve good things too. Own your space on the couch of life.',
                'keywords' => ['doubt', 'confidence', 'insecure', 'worthy', 'enough', 'self-esteem', 'imposter'],
            ],
            [
                'content' => 'Change is scary, like moving to a new home. But cats adapt - we explore, we claim new sunny spots, we make it ours. You too can find comfort in new beginnings.',
                'keywords' => ['change', 'new', 'different', 'moving', 'transition', 'starting', 'unknown'],
            ],
        ];

        foreach ($emotionalSupport as $i => $support) {
            $this->documents[] = new KnowledgeDocument(
                id: "emotion_{$i}",
                content: $support['content'],
                keywords: $support['keywords'],
                category: 'emotions',
                metadata: ['type' => 'emotional_support']
            );
        }
    }

    private function loadBreedDocuments(): void
    {
        $breeds = [
            [
                'content' => 'Maine Coons are gentle giants known for their friendly, dog-like personalities. They are excellent with families and other pets, and their calm demeanor makes them perfect therapy companions. They are patient listeners who offer steady, grounded advice.',
                'keywords' => ['maine', 'coon', 'gentle', 'giant', 'friendly', 'family', 'patient'],
            ],
            [
                'content' => 'Siamese cats are famous for their vocal nature and strong bond with their humans. They are chatty, social, and highly intelligent. As therapists, they offer engaging conversations and aren\'t afraid to speak their mind with loving honesty.',
                'keywords' => ['siamese', 'vocal', 'chatty', 'social', 'intelligent', 'talkative'],
            ],
            [
                'content' => 'Scottish Folds are known for their unique folded ears and sweet, playful nature. They adapt well to any environment and are incredibly affectionate. They offer warm, accepting advice and help people feel comfortable.',
                'keywords' => ['scottish', 'fold', 'sweet', 'playful', 'adaptable', 'affectionate'],
            ],
            [
                'content' => 'Ragdolls are the ultimate lap cats, known for going limp when held. They are docile, calm, and incredibly affectionate. They specialize in comfort and make people feel safe and loved.',
                'keywords' => ['ragdoll', 'lap', 'docile', 'calm', 'affectionate', 'comfort', 'cuddle'],
            ],
            [
                'content' => 'British Shorthairs are dignified, easygoing cats with a calm demeanor. They are independent yet affectionate, offering wise, measured advice without being overwhelming.',
                'keywords' => ['british', 'shorthair', 'dignified', 'calm', 'independent', 'wise'],
            ],
            [
                'content' => 'Abyssinians are active, curious, and love to explore. They are highly intelligent and playful, encouraging others to embrace curiosity and adventure in life.',
                'keywords' => ['abyssinian', 'active', 'curious', 'explorer', 'intelligent', 'playful', 'adventure'],
            ],
            [
                'content' => 'Tuxedo cats (not a breed, but a pattern) are known for their distinctive black and white markings. They are often described as having big personalities - confident, playful, and charming.',
                'keywords' => ['tuxedo', 'black', 'white', 'confident', 'charming', 'personality'],
            ],
            [
                'content' => 'Domestic Shorthairs are the most common cats, with diverse personalities and looks. They are adaptable, resilient survivors who remind us that your background doesn\'t define your potential.',
                'keywords' => ['domestic', 'shorthair', 'common', 'adaptable', 'resilient', 'survivor'],
            ],
        ];

        foreach ($breeds as $i => $breed) {
            $this->documents[] = new KnowledgeDocument(
                id: "breed_{$i}",
                content: $breed['content'],
                keywords: $breed['keywords'],
                category: 'breeds',
                metadata: ['type' => 'breed_info']
            );
        }
    }

    private function loadCatProfileDocuments(): void
    {
        // Load dynamic cat profiles from database
        try {
            $cats = $this->catRepository->findAll();
            foreach ($cats as $cat) {
                $profile = sprintf(
                    '%s is a %d-year-old %s %s. %s %s is currently feeling %s. ' .
                    'When interacting with %s, expect %s.',
                    $cat->getName(),
                    $cat->getAge(),
                    $cat->getColor(),
                    $cat->getBreed(),
                    $cat->getDescription() ?? 'A wonderful cafe cat.',
                    $cat->getName(),
                    $cat->getMood(),
                    $cat->getName(),
                    $this->getMoodExpectation($cat->getMood())
                );

                $this->documents[] = new KnowledgeDocument(
                    id: "cat_{$cat->getId()}",
                    content: $profile,
                    category: 'cat_profile',
                    keywords: [
                        strtolower($cat->getName()),
                        strtolower($cat->getBreed()),
                        strtolower($cat->getColor()),
                        $cat->getMood(),
                    ],
                    metadata: [
                        'type' => 'cat_profile',
                        'cat_id' => $cat->getId(),
                        'cat_name' => $cat->getName(),
                        'breed' => $cat->getBreed(),
                        'mood' => $cat->getMood(),
                    ]
                );
            }
        } catch (\Exception $e) {
            // Database might not be available during initial setup
        }
    }

    private function getMoodExpectation(string $mood): string
    {
        return match ($mood) {
            'happy' => 'energetic, positive responses full of enthusiasm and joy',
            'content' => 'calm, thoughtful advice delivered with peaceful wisdom',
            'grumpy' => 'blunt but caring honesty with a touch of sass',
            'upset' => 'empathetic understanding from someone who knows struggle',
            'hungry' => 'advice peppered with food metaphors and treat references',
            'sleepy' => 'gentle, dreamy wisdom with a relaxed, soothing tone',
            default => 'unique insights based on their current mood',
        };
    }
}
