<?php

namespace App\Service;

use App\Entity\Cat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * AI-powered service for generating unique public websites for each cat.
 * Uses randomly selected Tailwind CSS layouts and AI-generated content.
 */
class CatWebsiteGeneratorService
{
    private const LAYOUTS = [
        'hero-centered',
        'split-layout',
        'card-grid',
        'magazine-style',
        'minimal-elegant',
        'playful-colorful',
    ];

    private const COLOR_SCHEMES = [
        'warm' => [
            'primary' => 'orange',
            'secondary' => 'amber',
            'accent' => 'rose',
            'bg' => 'orange-50',
            'text' => 'orange-900',
        ],
        'cool' => [
            'primary' => 'blue',
            'secondary' => 'cyan',
            'accent' => 'indigo',
            'bg' => 'blue-50',
            'text' => 'blue-900',
        ],
        'nature' => [
            'primary' => 'emerald',
            'secondary' => 'teal',
            'accent' => 'lime',
            'bg' => 'emerald-50',
            'text' => 'emerald-900',
        ],
        'sunset' => [
            'primary' => 'rose',
            'secondary' => 'pink',
            'accent' => 'orange',
            'bg' => 'rose-50',
            'text' => 'rose-900',
        ],
        'royal' => [
            'primary' => 'purple',
            'secondary' => 'violet',
            'accent' => 'fuchsia',
            'bg' => 'purple-50',
            'text' => 'purple-900',
        ],
        'earth' => [
            'primary' => 'amber',
            'secondary' => 'yellow',
            'accent' => 'orange',
            'bg' => 'amber-50',
            'text' => 'amber-900',
        ],
    ];

    public function __construct(
        private AgentInterface $catPersonalityAgent,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Get or generate an AI-powered website for a cat.
     * Uses cached version if available, generates new one if not.
     */
    public function getWebsite(Cat $cat, bool $forceRegenerate = false): string
    {
        if (!$forceRegenerate && $cat->getAiWebsiteHtml() !== null) {
            return $cat->getAiWebsiteHtml();
        }

        $html = $this->generateWebsite($cat);

        $cat->setAiWebsiteHtml($html);
        $cat->setAiWebsiteGeneratedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $html;
    }

    /**
     * Generate a complete website for a cat with random layout.
     */
    public function generateWebsite(Cat $cat): string
    {
        // Select random layout and color scheme
        $layout = self::LAYOUTS[array_rand(self::LAYOUTS)];
        $colorScheme = array_rand(self::COLOR_SCHEMES);
        $colors = self::COLOR_SCHEMES[$colorScheme];

        // Store the layout choice
        $cat->setAiWebsiteLayout($layout);

        // Generate AI content for the website
        $content = $this->generateWebsiteContent($cat);

        // Build HTML based on layout
        return match ($layout) {
            'hero-centered' => $this->buildHeroCenteredLayout($cat, $content, $colors),
            'split-layout' => $this->buildSplitLayout($cat, $content, $colors),
            'card-grid' => $this->buildCardGridLayout($cat, $content, $colors),
            'magazine-style' => $this->buildMagazineLayout($cat, $content, $colors),
            'minimal-elegant' => $this->buildMinimalLayout($cat, $content, $colors),
            'playful-colorful' => $this->buildPlayfulLayout($cat, $content, $colors),
            default => $this->buildHeroCenteredLayout($cat, $content, $colors),
        };
    }

    /**
     * Generate AI content for the website.
     */
    private function generateWebsiteContent(Cat $cat): array
    {
        $context = $this->buildWebsiteContext($cat);

        $messages = new MessageBag(Message::forSystem($context));
        $messages->add(Message::ofUser(
            "Generate website content for {$cat->getName()}'s personal page. Include:\n" .
            "1. A catchy tagline (5-10 words)\n" .
            "2. An about section (2-3 sentences)\n" .
            "3. Three personality traits\n" .
            "4. A fun quote from the cat's perspective\n" .
            "5. Three hobbies or favorite activities\n\n" .
            "Format as JSON with keys: tagline, about, traits (array), quote, hobbies (array)"
        ));

        try {
            $response = $this->catPersonalityAgent->call($messages);
            $responseText = $response->getContent();

            // Try to extract JSON from the response
            if (preg_match('/\{[\s\S]*\}/', $responseText, $matches)) {
                $data = json_decode($matches[0], true);
                if ($data) {
                    return $this->sanitizeContent($data, $cat);
                }
            }

            return $this->getFallbackContent($cat);
        } catch (\Throwable $e) {
            return $this->getFallbackContent($cat);
        }
    }

    private function buildWebsiteContext(Cat $cat): string
    {
        return <<<CONTEXT
You are a creative website content writer for cats at "Whiskers & Wonders Cat Cafe".
Create engaging, fun content that showcases each cat's unique personality.

Cat Details:
- Name: {$cat->getName()}
- Breed: {$cat->getBreed()}
- Age: {$cat->getAge()} years old
- Color: {$cat->getColor()}
- Current Mood: {$cat->getMood()}
- Favorite Activity: {$cat->getPreferredInteractionLabel()}
- Personality: {$cat->getDescription()}

Write charming, playful content. Use cat puns and humor where appropriate.
Always respond with valid JSON only, no additional text or markdown code blocks.
CONTEXT;
    }

    private function sanitizeContent(array $data, Cat $cat): array
    {
        return [
            'tagline' => $data['tagline'] ?? "{$cat->getName()}: Living the Purrfect Life",
            'about' => $data['about'] ?? "Meet {$cat->getName()}, a wonderful {$cat->getBreed()} looking for love and treats.",
            'traits' => array_slice($data['traits'] ?? ['Friendly', 'Playful', 'Curious'], 0, 3),
            'quote' => $data['quote'] ?? "Every day is a good day for naps and snacks!",
            'hobbies' => array_slice($data['hobbies'] ?? ['Napping', 'Bird watching', 'Treat hunting'], 0, 3),
        ];
    }

    private function getFallbackContent(Cat $cat): array
    {
        $traits = match ($cat->getMood()) {
            'happy' => ['Joyful', 'Energetic', 'Affectionate'],
            'content' => ['Calm', 'Friendly', 'Relaxed'],
            'grumpy' => ['Independent', 'Discerning', 'Regal'],
            'sleepy' => ['Peaceful', 'Dreamy', 'Cuddly'],
            'hungry' => ['Enthusiastic', 'Food-motivated', 'Expressive'],
            default => ['Charming', 'Unique', 'Lovable'],
        };

        $hobbies = match ($cat->getPreferredInteraction()) {
            'feed' => ['Taste-testing treats', 'Mealtime supervision', 'Kitchen patrol'],
            'pet' => ['Cuddle sessions', 'Head bonks', 'Lap claiming'],
            'play' => ['Toy hunting', 'Zoomies', 'String chasing'],
            'rest' => ['Professional napping', 'Sunbeam seeking', 'Cozy spot finding'],
            default => ['Being adorable', 'Bird watching', 'Box exploration'],
        };

        return [
            'tagline' => "{$cat->getName()}: {$cat->getBreed()} Extraordinaire",
            'about' => "{$cat->getName()} is a {$cat->getAge()}-year-old {$cat->getColor()} {$cat->getBreed()} who has stolen hearts at Whiskers & Wonders Cat Cafe. " .
                "Known for their love of {$cat->getPreferredInteractionLabel()}, this special feline is ready to meet you!",
            'traits' => $traits,
            'quote' => "Life is better with whiskers, warm laps, and the occasional treat!",
            'hobbies' => $hobbies,
        ];
    }

    private function buildHeroCenteredLayout(Cat $cat, array $content, array $colors): string
    {
        $name = htmlspecialchars($cat->getName());
        $breed = htmlspecialchars($cat->getBreed());
        $age = $cat->getAge();
        $color = htmlspecialchars($cat->getColor());
        $mood = $cat->getMoodEmoji();
        $tagline = htmlspecialchars($content['tagline']);
        $about = htmlspecialchars($content['about']);
        $quote = htmlspecialchars($content['quote']);
        $traits = array_map('htmlspecialchars', $content['traits']);
        $hobbies = array_map('htmlspecialchars', $content['hobbies']);
        $p = $colors['primary'];
        $s = $colors['secondary'];
        $bg = $colors['bg'];
        $text = $colors['text'];

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}'s Website | Whiskers & Wonders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-{$bg} min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-12">
        <!-- Hero Section -->
        <header class="text-center mb-12">
            <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-{$p}-400 to-{$s}-500 flex items-center justify-center text-6xl shadow-xl">
                {$mood}
            </div>
            <h1 class="text-5xl font-bold text-{$text} mb-4">{$name}</h1>
            <p class="text-xl text-{$p}-600 font-medium">{$tagline}</p>
            <div class="mt-4 flex justify-center gap-2">
                <span class="px-3 py-1 bg-{$p}-200 text-{$p}-800 rounded-full text-sm">{$breed}</span>
                <span class="px-3 py-1 bg-{$s}-200 text-{$s}-800 rounded-full text-sm">{$age} years old</span>
                <span class="px-3 py-1 bg-{$p}-200 text-{$p}-800 rounded-full text-sm">{$color}</span>
            </div>
        </header>

        <!-- About Section -->
        <section class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-{$text} mb-4">About Me</h2>
            <p class="text-gray-700 text-lg leading-relaxed">{$about}</p>
        </section>

        <!-- Traits Section -->
        <section class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-{$p}-500 text-white rounded-xl p-6 text-center shadow-lg">
                <div class="text-3xl mb-2">✨</div>
                <p class="font-semibold">{$traits[0]}</p>
            </div>
            <div class="bg-{$s}-500 text-white rounded-xl p-6 text-center shadow-lg">
                <div class="text-3xl mb-2">💫</div>
                <p class="font-semibold">{$traits[1]}</p>
            </div>
            <div class="bg-{$p}-600 text-white rounded-xl p-6 text-center shadow-lg">
                <div class="text-3xl mb-2">🌟</div>
                <p class="font-semibold">{$traits[2]}</p>
            </div>
        </section>

        <!-- Quote Section -->
        <section class="bg-gradient-to-r from-{$p}-500 to-{$s}-500 rounded-2xl p-8 mb-8 text-white text-center shadow-xl">
            <div class="text-4xl mb-4">💭</div>
            <blockquote class="text-xl italic">"{$quote}"</blockquote>
            <p class="mt-4 text-{$s}-100">— {$name}</p>
        </section>

        <!-- Hobbies Section -->
        <section class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-{$text} mb-6">My Favorite Things</h2>
            <div class="space-y-4">
                <div class="flex items-center gap-4 p-4 bg-{$bg} rounded-xl">
                    <span class="text-2xl">🐾</span>
                    <span class="text-gray-700">{$hobbies[0]}</span>
                </div>
                <div class="flex items-center gap-4 p-4 bg-{$bg} rounded-xl">
                    <span class="text-2xl">😸</span>
                    <span class="text-gray-700">{$hobbies[1]}</span>
                </div>
                <div class="flex items-center gap-4 p-4 bg-{$bg} rounded-xl">
                    <span class="text-2xl">🎀</span>
                    <span class="text-gray-700">{$hobbies[2]}</span>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="text-center mt-12 text-{$p}-600">
            <p>Visit me at <strong>Whiskers & Wonders Cat Cafe</strong></p>
            <p class="text-sm mt-2">Website generated with AI magic ✨</p>
        </footer>
    </div>
</body>
</html>
HTML;
    }

    private function buildSplitLayout(Cat $cat, array $content, array $colors): string
    {
        $name = htmlspecialchars($cat->getName());
        $breed = htmlspecialchars($cat->getBreed());
        $age = $cat->getAge();
        $color = htmlspecialchars($cat->getColor());
        $mood = $cat->getMoodEmoji();
        $tagline = htmlspecialchars($content['tagline']);
        $about = htmlspecialchars($content['about']);
        $quote = htmlspecialchars($content['quote']);
        $traits = array_map('htmlspecialchars', $content['traits']);
        $hobbies = array_map('htmlspecialchars', $content['hobbies']);
        $p = $colors['primary'];
        $s = $colors['secondary'];
        $bg = $colors['bg'];
        $text = $colors['text'];

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}'s Website | Whiskers & Wonders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Left Panel -->
        <div class="w-1/3 bg-gradient-to-b from-{$p}-500 to-{$s}-600 text-white p-8 flex flex-col justify-center items-center sticky top-0 h-screen">
            <div class="w-40 h-40 rounded-full bg-white/20 flex items-center justify-center text-7xl mb-6 backdrop-blur-sm">
                {$mood}
            </div>
            <h1 class="text-4xl font-bold mb-2">{$name}</h1>
            <p class="text-{$s}-100 text-center">{$tagline}</p>
            <div class="mt-6 space-y-2 text-center">
                <p class="text-sm opacity-80">{$breed}</p>
                <p class="text-sm opacity-80">{$age} years • {$color}</p>
            </div>
            <div class="mt-8 flex gap-2">
                {$this->renderTraitBadges($traits, 'white/20')}
            </div>
        </div>

        <!-- Right Content -->
        <div class="w-2/3 p-12 overflow-y-auto">
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-{$text} mb-6 flex items-center gap-3">
                    <span class="w-12 h-1 bg-{$p}-500"></span>
                    About Me
                </h2>
                <p class="text-gray-600 text-lg leading-relaxed">{$about}</p>
            </section>

            <section class="mb-12 bg-{$bg} rounded-2xl p-8">
                <div class="text-5xl text-center mb-4">💭</div>
                <blockquote class="text-xl text-center text-{$text} italic">"{$quote}"</blockquote>
            </section>

            <section>
                <h2 class="text-3xl font-bold text-{$text} mb-6 flex items-center gap-3">
                    <span class="w-12 h-1 bg-{$p}-500"></span>
                    Things I Love
                </h2>
                <div class="grid grid-cols-1 gap-4">
                    <div class="flex items-center gap-4 p-6 bg-white rounded-xl shadow-md border-l-4 border-{$p}-500">
                        <span class="text-3xl">🎯</span>
                        <span class="text-gray-700 text-lg">{$hobbies[0]}</span>
                    </div>
                    <div class="flex items-center gap-4 p-6 bg-white rounded-xl shadow-md border-l-4 border-{$s}-500">
                        <span class="text-3xl">💝</span>
                        <span class="text-gray-700 text-lg">{$hobbies[1]}</span>
                    </div>
                    <div class="flex items-center gap-4 p-6 bg-white rounded-xl shadow-md border-l-4 border-{$p}-500">
                        <span class="text-3xl">🌈</span>
                        <span class="text-gray-700 text-lg">{$hobbies[2]}</span>
                    </div>
                </div>
            </section>

            <footer class="mt-12 pt-8 border-t border-gray-200 text-center text-gray-500">
                <p>Find me at <strong class="text-{$p}-600">Whiskers & Wonders Cat Cafe</strong></p>
            </footer>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function buildCardGridLayout(Cat $cat, array $content, array $colors): string
    {
        $name = htmlspecialchars($cat->getName());
        $breed = htmlspecialchars($cat->getBreed());
        $age = $cat->getAge();
        $color = htmlspecialchars($cat->getColor());
        $mood = $cat->getMoodEmoji();
        $tagline = htmlspecialchars($content['tagline']);
        $about = htmlspecialchars($content['about']);
        $quote = htmlspecialchars($content['quote']);
        $traits = array_map('htmlspecialchars', $content['traits']);
        $hobbies = array_map('htmlspecialchars', $content['hobbies']);
        $p = $colors['primary'];
        $s = $colors['secondary'];
        $bg = $colors['bg'];
        $text = $colors['text'];

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}'s Website | Whiskers & Wonders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-{$p}-500 via-{$s}-500 to-{$p}-600 rounded-3xl p-8 mb-6 text-white shadow-2xl">
            <div class="flex items-center gap-6">
                <div class="w-24 h-24 bg-white/20 rounded-2xl flex items-center justify-center text-5xl backdrop-blur-sm">
                    {$mood}
                </div>
                <div>
                    <h1 class="text-4xl font-bold">{$name}</h1>
                    <p class="text-{$s}-100 mt-1">{$tagline}</p>
                    <div class="flex gap-2 mt-3">
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">{$breed}</span>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">{$age} yrs</span>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">{$color}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Layout -->
        <div class="grid grid-cols-3 gap-6">
            <!-- About Card -->
            <div class="col-span-2 bg-white rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold text-{$text} mb-4 flex items-center gap-2">
                    <span class="text-2xl">📖</span> My Story
                </h2>
                <p class="text-gray-600 leading-relaxed">{$about}</p>
            </div>

            <!-- Traits Card -->
            <div class="bg-{$bg} rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold text-{$text} mb-4">Personality</h2>
                <div class="space-y-3">
                    <div class="bg-white rounded-lg p-3 text-center font-medium text-{$p}-700">{$traits[0]}</div>
                    <div class="bg-white rounded-lg p-3 text-center font-medium text-{$s}-700">{$traits[1]}</div>
                    <div class="bg-white rounded-lg p-3 text-center font-medium text-{$p}-700">{$traits[2]}</div>
                </div>
            </div>

            <!-- Hobbies Cards -->
            <div class="bg-{$p}-500 text-white rounded-2xl p-6 shadow-lg">
                <span class="text-3xl">🎮</span>
                <h3 class="font-bold mt-2">{$hobbies[0]}</h3>
            </div>
            <div class="bg-{$s}-500 text-white rounded-2xl p-6 shadow-lg">
                <span class="text-3xl">💤</span>
                <h3 class="font-bold mt-2">{$hobbies[1]}</h3>
            </div>
            <div class="bg-{$p}-600 text-white rounded-2xl p-6 shadow-lg">
                <span class="text-3xl">🎁</span>
                <h3 class="font-bold mt-2">{$hobbies[2]}</h3>
            </div>

            <!-- Quote Card -->
            <div class="col-span-3 bg-white rounded-2xl p-8 shadow-lg text-center">
                <div class="text-4xl mb-4">💬</div>
                <blockquote class="text-2xl text-{$text} italic">"{$quote}"</blockquote>
                <p class="mt-4 text-{$p}-500 font-medium">— {$name}</p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center mt-8 text-gray-500">
            <p>Visit {$name} at <strong class="text-{$p}-600">Whiskers & Wonders Cat Cafe</strong></p>
        </footer>
    </div>
</body>
</html>
HTML;
    }

    private function buildMagazineLayout(Cat $cat, array $content, array $colors): string
    {
        $name = htmlspecialchars($cat->getName());
        $breed = htmlspecialchars($cat->getBreed());
        $age = $cat->getAge();
        $color = htmlspecialchars($cat->getColor());
        $mood = $cat->getMoodEmoji();
        $tagline = htmlspecialchars($content['tagline']);
        $about = htmlspecialchars($content['about']);
        $quote = htmlspecialchars($content['quote']);
        $traits = array_map('htmlspecialchars', $content['traits']);
        $hobbies = array_map('htmlspecialchars', $content['hobbies']);
        $p = $colors['primary'];
        $s = $colors['secondary'];
        $bg = $colors['bg'];
        $text = $colors['text'];

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}'s Website | Whiskers & Wonders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen">
    <!-- Magazine Header -->
    <header class="border-b-4 border-{$p}-500 py-4">
        <div class="max-w-5xl mx-auto px-4">
            <p class="text-center text-{$p}-500 font-serif text-sm uppercase tracking-widest">Whiskers & Wonders Presents</p>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-12">
        <!-- Hero -->
        <div class="text-center mb-16">
            <div class="w-48 h-48 mx-auto mb-8 rounded-full border-4 border-{$p}-500 flex items-center justify-center text-8xl bg-{$bg}">
                {$mood}
            </div>
            <h1 class="text-6xl font-serif font-bold text-gray-900 mb-4">{$name}</h1>
            <p class="text-2xl text-{$p}-600 font-light italic">{$tagline}</p>
            <div class="flex justify-center gap-4 mt-6 text-gray-500">
                <span>{$breed}</span>
                <span>•</span>
                <span>{$age} Years Old</span>
                <span>•</span>
                <span>{$color} Coat</span>
            </div>
        </div>

        <!-- Feature Article -->
        <article class="mb-16">
            <h2 class="text-3xl font-serif font-bold text-gray-900 mb-6 pb-2 border-b-2 border-{$p}-200">The {$name} Story</h2>
            <p class="text-xl text-gray-700 leading-relaxed first-letter:text-5xl first-letter:font-serif first-letter:font-bold first-letter:text-{$p}-500 first-letter:float-left first-letter:mr-3">{$about}</p>
        </article>

        <!-- Two Column -->
        <div class="grid grid-cols-2 gap-12 mb-16">
            <div>
                <h3 class="text-2xl font-serif font-bold text-gray-900 mb-4 pb-2 border-b-2 border-{$s}-200">Defining Traits</h3>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3 text-lg text-gray-700">
                        <span class="w-8 h-8 bg-{$p}-500 text-white rounded-full flex items-center justify-center text-sm">1</span>
                        {$traits[0]}
                    </li>
                    <li class="flex items-center gap-3 text-lg text-gray-700">
                        <span class="w-8 h-8 bg-{$s}-500 text-white rounded-full flex items-center justify-center text-sm">2</span>
                        {$traits[1]}
                    </li>
                    <li class="flex items-center gap-3 text-lg text-gray-700">
                        <span class="w-8 h-8 bg-{$p}-600 text-white rounded-full flex items-center justify-center text-sm">3</span>
                        {$traits[2]}
                    </li>
                </ul>
            </div>
            <div>
                <h3 class="text-2xl font-serif font-bold text-gray-900 mb-4 pb-2 border-b-2 border-{$s}-200">Favorite Pastimes</h3>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3 text-lg text-gray-700">
                        <span class="text-xl">🐾</span>
                        {$hobbies[0]}
                    </li>
                    <li class="flex items-center gap-3 text-lg text-gray-700">
                        <span class="text-xl">🐾</span>
                        {$hobbies[1]}
                    </li>
                    <li class="flex items-center gap-3 text-lg text-gray-700">
                        <span class="text-xl">🐾</span>
                        {$hobbies[2]}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Pull Quote -->
        <div class="bg-{$bg} py-12 px-16 mb-16 relative">
            <div class="absolute top-4 left-8 text-8xl text-{$p}-200 font-serif">"</div>
            <blockquote class="text-2xl font-serif text-center text-{$text} italic relative z-10">{$quote}</blockquote>
            <p class="text-center mt-4 text-{$p}-600 font-medium">— {$name}</p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t-4 border-{$p}-500 py-6 text-center text-gray-500">
        <p class="font-serif">Visit {$name} at <strong class="text-{$p}-600">Whiskers & Wonders Cat Cafe</strong></p>
    </footer>
</body>
</html>
HTML;
    }

    private function buildMinimalLayout(Cat $cat, array $content, array $colors): string
    {
        $name = htmlspecialchars($cat->getName());
        $breed = htmlspecialchars($cat->getBreed());
        $age = $cat->getAge();
        $color = htmlspecialchars($cat->getColor());
        $mood = $cat->getMoodEmoji();
        $tagline = htmlspecialchars($content['tagline']);
        $about = htmlspecialchars($content['about']);
        $quote = htmlspecialchars($content['quote']);
        $traits = array_map('htmlspecialchars', $content['traits']);
        $hobbies = array_map('htmlspecialchars', $content['hobbies']);
        $p = $colors['primary'];
        $s = $colors['secondary'];
        $text = $colors['text'];

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}'s Website | Whiskers & Wonders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen">
    <div class="max-w-2xl mx-auto px-6 py-20">
        <!-- Minimal Header -->
        <header class="mb-20 text-center">
            <div class="text-8xl mb-8">{$mood}</div>
            <h1 class="text-5xl font-light text-gray-900 tracking-tight">{$name}</h1>
            <div class="w-16 h-0.5 bg-{$p}-500 mx-auto my-6"></div>
            <p class="text-gray-500">{$breed} · {$age} years · {$color}</p>
        </header>

        <!-- Tagline -->
        <p class="text-center text-2xl text-{$p}-600 font-light mb-16">{$tagline}</p>

        <!-- About -->
        <section class="mb-16">
            <p class="text-lg text-gray-600 leading-relaxed text-center">{$about}</p>
        </section>

        <!-- Traits -->
        <section class="mb-16">
            <div class="flex justify-center gap-6">
                <span class="text-{$p}-600 font-medium">{$traits[0]}</span>
                <span class="text-gray-300">·</span>
                <span class="text-{$s}-600 font-medium">{$traits[1]}</span>
                <span class="text-gray-300">·</span>
                <span class="text-{$p}-600 font-medium">{$traits[2]}</span>
            </div>
        </section>

        <!-- Quote -->
        <section class="mb-16 py-12 border-t border-b border-gray-100">
            <blockquote class="text-xl text-center text-gray-700 italic">"{$quote}"</blockquote>
        </section>

        <!-- Hobbies -->
        <section class="mb-16">
            <h2 class="text-xs uppercase tracking-widest text-gray-400 text-center mb-6">What I Love</h2>
            <div class="space-y-4 text-center">
                <p class="text-gray-700">{$hobbies[0]}</p>
                <p class="text-gray-700">{$hobbies[1]}</p>
                <p class="text-gray-700">{$hobbies[2]}</p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="text-center text-sm text-gray-400">
            <div class="w-8 h-0.5 bg-{$p}-300 mx-auto mb-6"></div>
            <p>Whiskers & Wonders Cat Cafe</p>
        </footer>
    </div>
</body>
</html>
HTML;
    }

    private function buildPlayfulLayout(Cat $cat, array $content, array $colors): string
    {
        $name = htmlspecialchars($cat->getName());
        $breed = htmlspecialchars($cat->getBreed());
        $age = $cat->getAge();
        $color = htmlspecialchars($cat->getColor());
        $mood = $cat->getMoodEmoji();
        $tagline = htmlspecialchars($content['tagline']);
        $about = htmlspecialchars($content['about']);
        $quote = htmlspecialchars($content['quote']);
        $traits = array_map('htmlspecialchars', $content['traits']);
        $hobbies = array_map('htmlspecialchars', $content['hobbies']);
        $p = $colors['primary'];
        $s = $colors['secondary'];
        $accent = $colors['accent'];
        $text = $colors['text'];

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}'s Website | Whiskers & Wonders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .bounce-slow { animation: bounce-slow 2s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-{$p}-100 via-{$s}-100 to-{$accent}-100 min-h-screen overflow-x-hidden">
    <!-- Decorative Elements -->
    <div class="fixed top-10 left-10 text-6xl opacity-20 bounce-slow">🐾</div>
    <div class="fixed top-20 right-20 text-4xl opacity-20 bounce-slow" style="animation-delay: 0.5s">✨</div>
    <div class="fixed bottom-20 left-20 text-5xl opacity-20 bounce-slow" style="animation-delay: 1s">🧶</div>
    <div class="fixed bottom-10 right-10 text-6xl opacity-20 bounce-slow" style="animation-delay: 0.3s">🐾</div>

    <div class="max-w-4xl mx-auto px-4 py-12 relative z-10">
        <!-- Fun Header -->
        <header class="text-center mb-12">
            <div class="inline-block">
                <div class="w-40 h-40 mx-auto mb-6 rounded-3xl bg-white shadow-2xl flex items-center justify-center text-7xl transform rotate-3 hover:rotate-0 transition-transform">
                    {$mood}
                </div>
            </div>
            <h1 class="text-6xl font-black text-{$text} mb-2 transform -rotate-2">
                {$name}
            </h1>
            <div class="inline-block bg-{$p}-500 text-white px-6 py-2 rounded-full transform rotate-1 shadow-lg">
                {$tagline}
            </div>
        </header>

        <!-- Info Badges -->
        <div class="flex justify-center gap-4 mb-12 flex-wrap">
            <span class="bg-white px-6 py-3 rounded-full shadow-lg font-bold text-{$p}-600 transform -rotate-2 hover:rotate-0 transition-transform">🐱 {$breed}</span>
            <span class="bg-white px-6 py-3 rounded-full shadow-lg font-bold text-{$s}-600 transform rotate-2 hover:rotate-0 transition-transform">🎂 {$age} years</span>
            <span class="bg-white px-6 py-3 rounded-full shadow-lg font-bold text-{$accent}-600 transform -rotate-1 hover:rotate-0 transition-transform">🎨 {$color}</span>
        </div>

        <!-- About Bubble -->
        <section class="bg-white rounded-3xl shadow-2xl p-8 mb-8 transform -rotate-1 hover:rotate-0 transition-transform">
            <h2 class="text-2xl font-black text-{$text} mb-4 flex items-center gap-2">
                <span class="text-3xl">📢</span> Hey There!
            </h2>
            <p class="text-gray-700 text-lg">{$about}</p>
        </section>

        <!-- Traits Cards -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-{$p}-500 text-white rounded-2xl p-6 text-center shadow-xl transform rotate-2 hover:rotate-0 transition-transform">
                <div class="text-4xl mb-2">⭐</div>
                <p class="font-bold text-lg">{$traits[0]}</p>
            </div>
            <div class="bg-{$s}-500 text-white rounded-2xl p-6 text-center shadow-xl transform -rotate-2 hover:rotate-0 transition-transform">
                <div class="text-4xl mb-2">💫</div>
                <p class="font-bold text-lg">{$traits[1]}</p>
            </div>
            <div class="bg-{$accent}-500 text-white rounded-2xl p-6 text-center shadow-xl transform rotate-1 hover:rotate-0 transition-transform">
                <div class="text-4xl mb-2">🌈</div>
                <p class="font-bold text-lg">{$traits[2]}</p>
            </div>
        </div>

        <!-- Quote -->
        <section class="bg-gradient-to-r from-{$p}-500 via-{$s}-500 to-{$accent}-500 rounded-3xl p-8 mb-8 text-white text-center shadow-2xl transform rotate-1 hover:rotate-0 transition-transform">
            <div class="text-5xl mb-4">💬</div>
            <blockquote class="text-2xl font-bold italic">"{$quote}"</blockquote>
            <p class="mt-4 text-white/80">~ {$name} 🐱</p>
        </section>

        <!-- Hobbies -->
        <section class="bg-white rounded-3xl shadow-2xl p-8 transform -rotate-1 hover:rotate-0 transition-transform">
            <h2 class="text-2xl font-black text-{$text} mb-6 text-center">
                <span class="text-3xl">🎉</span> My Favorite Things!
            </h2>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-{$p}-100 rounded-2xl p-4">
                    <div class="text-3xl mb-2">🏆</div>
                    <p class="text-{$p}-700 font-medium">{$hobbies[0]}</p>
                </div>
                <div class="bg-{$s}-100 rounded-2xl p-4">
                    <div class="text-3xl mb-2">💝</div>
                    <p class="text-{$s}-700 font-medium">{$hobbies[1]}</p>
                </div>
                <div class="bg-{$accent}-100 rounded-2xl p-4">
                    <div class="text-3xl mb-2">🎊</div>
                    <p class="text-{$accent}-700 font-medium">{$hobbies[2]}</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="text-center mt-12">
            <p class="text-{$text} font-bold text-lg">
                Come visit me at <span class="text-{$p}-600">Whiskers & Wonders Cat Cafe</span>! 🐾✨
            </p>
        </footer>
    </div>
</body>
</html>
HTML;
    }

    private function renderTraitBadges(array $traits, string $bgClass): string
    {
        $html = '';
        foreach ($traits as $trait) {
            $html .= "<span class=\"px-3 py-1 bg-{$bgClass} rounded-full text-sm\">" . htmlspecialchars($trait) . "</span>";
        }
        return $html;
    }
}
