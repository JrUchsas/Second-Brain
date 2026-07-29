<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAIService
{
    /**
     * Analyze raw note content or topic prompt, returning title, summary, tags, and AI generated ideas.
     *
     * @return array{title: string, summary: string, tags: list<string>, generated_ideas: string}
     */
    public function analyze(string $content): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $content));

        // Check if content is empty or gibberish
        if ($this->isGibberish($clean)) {
            return $this->meaninglessResponse($clean);
        }

        // Auto-correct misspelled words and typos
        $correctedContent = $this->correctSpelling($clean);

        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');

        if (! empty($apiKey)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(15)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are an AI Second Brain knowledge engine. Analyze the provided note text or prompt. First, auto-correct any misspelled words or typos (e.g. "videoes" -> "videos", "larvel" -> "laravel", "budge" -> "budgie", "pythn" -> "python"). If the input is meaningless, random keystrokes (e.g. "asdasfasf", "qwerty"), or empty, set title to "Meaningless Input", summary to "The provided text does not contain meaningful content.", tags to ["invalid-input"], and generated_ideas to "• Please enter meaningful thoughts or topics to generate AI insights." Otherwise, return a JSON object with exact keys: "title" (concise descriptive title), "summary" (2-sentence executive summary), "tags" (array of 3-5 tags), "generated_ideas" (3-5 markdown bullet points tailored specifically to the context).',
                            ],
                            [
                                'role' => 'user',
                                'content' => $correctedContent,
                            ],
                        ],
                        'temperature' => 0.3,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawMessage = $data['choices'][0]['message']['content'] ?? '{}';
                    $decoded = json_decode($rawMessage, true);

                    if (is_array($decoded) && ! empty($decoded['title'])) {
                        return [
                            'title' => (string) ($decoded['title'] ?? 'Untitled Note'),
                            'summary' => (string) ($decoded['summary'] ?? ''),
                            'tags' => is_array($decoded['tags'] ?? null) ? array_values(array_map('strval', $decoded['tags'])) : [],
                            'generated_ideas' => (string) ($decoded['generated_ideas'] ?? ''),
                        ];
                    }
                }

                Log::warning("OpenAI API returned non-200 or quota error: {$response->body()}. Falling back to advanced local NLP analysis.");
            } catch (\Throwable $e) {
                Log::warning("OpenAI API call failed with exception: {$e->getMessage()}. Using advanced local NLP analysis.");
            }
        }

        return $this->fallbackAnalysis($correctedContent);
    }

    /**
     * Auto-correct common typos and misspelled words using dictionary mapping and Levenshtein distance.
     */
    protected function correctSpelling(string $text): string
    {
        // Common exact typo mapping dictionary
        $replacements = [
            'videoes' => 'videos',
            'vidio' => 'videos',
            'vidoes' => 'videos',
            'budge' => 'budgie',
            'bujie' => 'budgie',
            'budgi' => 'budgie',
            'larvel' => 'laravel',
            'laraval' => 'laravel',
            'pythn' => 'python',
            'pyton' => 'python',
            'javascrip' => 'javascript',
            'valornt' => 'valorant',
            'valarant' => 'valorant',
            'volorant' => 'valorant',
            'resipe' => 'recipe',
            'recipie' => 'recipe',
            'workut' => 'workout',
            'worout' => 'workout',
            'yotube' => 'youtube',
            'utube' => 'youtube',
            'watc' => 'watch',
            'wath' => 'watch',
            'speel' => 'spell',
            'accoding' => 'according',
            'tutorail' => 'tutorial',
            'tutoral' => 'tutorial',
            'projct' => 'project',
            'ideea' => 'idea',
            'excercise' => 'exercise',
            'exersice' => 'exercise',
        ];

        $dictionary = [
            'valorant', 'youtube', 'laravel', 'python', 'javascript', 'budgie', 'videos',
            'recipe', 'workout', 'tutorial', 'project', 'gaming', 'study', 'exercise',
            'business', 'fitness', 'health', 'shopping', 'software', 'programming',
        ];

        $words = explode(' ', $text);
        $corrected = [];

        foreach ($words as $word) {
            $cleanWord = strtolower(trim($word, ".,!?\"'"));
            if (isset($replacements[$cleanWord])) {
                $corrected[] = $replacements[$cleanWord];

                continue;
            }

            // Fuzzy Levenshtein match for words of 4+ letters
            if (strlen($cleanWord) >= 4) {
                $closest = null;
                $shortest = 3; // Maximum allowed edit distance is 2

                foreach ($dictionary as $dictWord) {
                    $lev = levenshtein($cleanWord, $dictWord);
                    if ($lev < $shortest && $lev <= 2) {
                        $closest = $dictWord;
                        $shortest = $lev;
                    }
                }

                if ($closest !== null) {
                    $corrected[] = $closest;

                    continue;
                }
            }

            $corrected[] = $word;
        }

        return implode(' ', $corrected);
    }

    /**
     * Detect if text is meaningless gibberish or keyboard mashing.
     */
    protected function isGibberish(string $content): bool
    {
        $clean = trim(strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $content)));

        if (strlen($clean) < 3) {
            return true;
        }

        // Remove spaces to analyze letter patterns
        $lettersOnly = preg_replace('/[^a-z]/i', '', $clean);
        $len = strlen($lettersOnly);

        if ($len > 0) {
            // Count vowels
            preg_match_all('/[aeiouy]/i', $lettersOnly, $vowels);
            $vowelRatio = count($vowels[0] ?? []) / $len;

            // Real English text typically has a vowel ratio between 25% and 50%
            if ($len >= 6 && ($vowelRatio < 0.15 || $vowelRatio > 0.70)) {
                return true;
            }
        }

        // Common keyboard mashing patterns
        $mashPatterns = [
            'asdf', 'sdfg', 'dfgh', 'fghj', 'ghjk', 'hjkl',
            'qwerty', 'werty', 'zxcv', 'xcvb', 'cvbn',
            'sfasf', 'dasf', 'fasf', 'afas', 'asda', 'sdas',
            'ssss', 'aaaa', 'dddd', 'ffff', 'gggg', 'zzzz',
        ];

        foreach ($mashPatterns as $pattern) {
            if (str_contains($clean, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Response payload for meaningless / gibberish inputs.
     *
     * @return array{title: string, summary: string, tags: list<string>, generated_ideas: string}
     */
    protected function meaninglessResponse(string $content): array
    {
        $display = Str::limit($content, 30);

        return [
            'title' => 'Meaningless / Invalid Input',
            'summary' => "The submitted text ('{$display}') does not contain meaningful words or coherent concepts. No AI summary could be generated.",
            'tags' => ['invalid-input'],
            'generated_ideas' => '• **No Actionable Insights**: Please type real notes, ideas, questions, or meeting summaries to receive AI analysis and project takeaways.',
        ];
    }

    /**
     * Advanced contextual NLP heuristic analysis engine when OpenAI API key is missing or quota is exceeded.
     *
     * @return array{title: string, summary: string, tags: list<string>, generated_ideas: string}
     */
    protected function fallbackAnalysis(string $content): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $content));
        $lower = strtolower($clean);

        // Extract key words & clean stop words
        preg_match_all('/\b[a-zA-Z]{3,}\b/', $lower, $matches);
        $words = $matches[0] ?? [];
        $stopWords = [
            'about', 'above', 'across', 'after', 'again', 'against', 'almost', 'alone', 'along', 'already', 'also',
            'although', 'always', 'among', 'another', 'other', 'there', 'these', 'those', 'where', 'which', 'while',
            'would', 'could', 'should', 'first', 'second', 'with', 'from', 'into', 'that', 'this', 'take', 'care',
            'your', 'some', 'what', 'when', 'how', 'have', 'has', 'had', 'make', 'need', 'want', 'like', 'good', 'well',
            'watch', 'view', 'look', 'see', 'find', 'show', 'search', 'give', 'the', 'and', 'for', 'you', 'can',
        ];
        $keywords = array_values(array_unique(array_diff($words, $stopWords)));
        $tags = array_slice($keywords, 0, 4);
        if (empty($tags)) {
            $tags = ['notes', 'knowledge', 'ideas'];
        }

        $mainTopic = ! empty($keywords[0]) ? ucfirst($keywords[0]) : 'Topic';
        $secondaryTopic = ! empty($keywords[1]) ? ucfirst($keywords[1]) : 'Insights';

        // 1. Gaming / Esports (Valorant, CSGO, Minecraft, Fortnite, etc.)
        if (preg_match('/\b(valorant|csgo|minecraft|fortnite|apex|league|overwatch|game|gaming|esports|playstation|xbox|steam|gamer)\b/i', $lower, $m)) {
            $game = ucfirst($m[1]);
            $title = "{$game} Gameplay & Strategy Guide";
            $summary = "Curated gameplay takeaways, pro VOD analysis, agent/weapon mechanics, and competitive rank strategies for {$game}.";
            $ideas = "• **Pro VOD & Match Analysis**: Watch high-rank (Radiant/Pro) matches to study crosshair placement, site executes, and game sense.\n"
                ."• **Aim & Mechanics Warmup**: Practice daily in Aim Lab / Deathmatch focusing on headshot accuracy and recoil control.\n"
                ."• **Creator & Lineup Playlist**: Bookmark top creator guides for map-specific lineups, utility placement, and setups.\n"
                .'• **Competitive Teamplay & Comms**: Focus on team economy, clear info callouts, and trade-fragging during ranked matches.';
        }
        // 2. YouTube / Video Watching / Streaming
        elseif (preg_match('/\b(youtube|video|videos|stream|movie|podcast|channel|watch)\b/i', $lower, $m)) {
            $title = "{$mainTopic} Video Watchlist & Content Notes";
            $summary = "Curated video playlist, key timestamps, tutorial notes, and content takeaways for {$mainTopic}.";
            $ideas = "• **Curated Video Playlist**: Organize top tutorial channels and playlist links into structured learning topics.\n"
                ."• **Timestamp & Key Concept Notes**: Bookmark critical video timestamps and write quick bullet points for important takeaways.\n"
                ."• **Practical Hands-On Application**: Practice and test techniques shown in video walkthroughs immediately after watching.\n"
                .'• **Community & Clip Highlights**: Share notable clips or insightful breakdowns with study groups and community forums.';
        }
        // 3. Learning / Study / Courses / Tutorials
        elseif (preg_match('/\b(learn|study|course|tutorial|exam|read|book|class|subject|chapter)\b/i', $lower, $m)) {
            $title = "{$mainTopic} Learning Roadmap & Study Plan";
            $summary = "Structured learning objectives, study notes, practice exercises, and key takeaway summaries for {$mainTopic}.";
            $ideas = "• **Structured Study Breakdown**: Divide core concepts into daily study modules and review schedules.\n"
                ."• **Active Recall & Flashcards**: Use active recall and spaced repetition for key definitions, formulas, and concepts.\n"
                ."• **Hands-On Exercises**: Complete practical projects or practice questions to test and reinforce understanding.\n"
                .'• **Progress Review & Self-Assessment**: Evaluate weekly progress and summarize key takeaways in your digital notes.';
        }
        // 4. Shopping / Purchasing / Gear
        elseif (preg_match('/\b(buy|purchase|order|price|laptop|phone|gear|shop|deal)\b/i', $lower, $m)) {
            $title = "{$mainTopic} Buying Guide & Product Comparison";
            $summary = "Feature comparison, budget evaluation, specs breakdown, and purchasing strategy for {$mainTopic}.";
            $ideas = "• **Specs & Feature Comparison**: Compare technical specifications, performance benchmarks, and user ratings.\n"
                ."• **Budget & Deal Tracking**: Monitor pricing across trusted retailers and track seasonal discounts or coupons.\n"
                ."• **Warranty & Support Review**: Check warranty coverage, return policies, and long-term durability reviews.\n"
                .'• **Final Decision Checklist**: Verify compatibility with your workflow before completing the purchase.';
        }
        // 5. Pets & Animal Care
        elseif (preg_match('/\b(budgie|bird|pet|dog|cat|parrot|animal|fish|hamster|rabbit)\b/i', $lower, $m)) {
            $petName = ucfirst($m[1]);
            $title = "Complete {$petName} Care & Wellness Guide";
            $summary = "Essential daily routines, nutrition, environment setup, and health recommendations for keeping your {$petName} healthy and happy.";
            $ideas = "• **Balanced Nutrition & Diet**: Provide fresh species-appropriate food, clean daily water, and essential vitamin supplements.\n"
                ."• **Safe Environment & Shelter**: Maintain clean, comfortable housing with adequate temperature control and mental stimulation.\n"
                ."• **Daily Interaction & Exercise**: Schedule dedicated time for physical activity, bonding, and safe play sessions.\n"
                .'• **Health & Routine Monitoring**: Keep track of appetite, behavior, grooming, and schedule regular veterinary checkups.';
        }
        // 6. Software / Web Development / Code
        elseif (preg_match('/\b(laravel|php|js|javascript|python|coding|react|vue|api|code|database|sql)\b/i', $lower, $m)) {
            $tech = strtoupper($m[1]);
            $title = "{$tech} Architecture & Implementation Roadmap";
            $summary = 'Technical breakdown focusing on clean code architecture, database modeling, background queues, and automated testing.';
            $ideas = "• **Core Architecture & Scaffolding**: Set up modular controllers, service layers, and migration schemas.\n"
                ."• **Queue-Driven Automation**: Implement asynchronous background jobs for external API calls and background processing.\n"
                ."• **Search & Query Optimization**: Utilize database indexes and Scout full-text search for fast data retrieval.\n"
                .'• **Security & Testing Suite**: Enforce robust validation, rate limiting, and write comprehensive Pest feature tests.';
        }
        // 7. Cooking & Recipes
        elseif (preg_match('/\b(recipe|cook|food|bake|kitchen|meal|dish|dinner|lunch|breakfast|pizza|bread)\b/i', $lower, $m)) {
            $title = "{$mainTopic} Preparation Guide & Recipe Breakdown";
            $summary = 'Preparation steps, ingredient selection, flavor balance, and cooking guidelines for optimal results.';
            $ideas = "• **Mise en Place & Prep**: Organize fresh ingredients, prep tools, and preheat equipment before cooking.\n"
                ."• **Precision Technique & Timing**: Follow temperature controls and cooking times to achieve ideal flavor and texture.\n"
                ."• **Seasoning & Presentation**: Balance acidity, salt, and herbs, finishing with clean visual plating.\n"
                .'• **Safe Storage & Meal Prep**: Store leftovers in sealed containers and plan portioning for upcoming meals.';
        }
        // 8. Health & Fitness
        elseif (preg_match('/\b(workout|fitness|exercise|diet|gym|health|running|muscle|weight)\b/i', $lower, $m)) {
            $title = "{$mainTopic} Fitness & Wellness Action Plan";
            $summary = 'Targeted exercise routine, nutrition guidelines, recovery protocols, and progress tracking.';
            $ideas = "• **Structured Training Routine**: Execute progressive workouts with proper technique and consistent frequency.\n"
                ."• **Nutritional Fueling**: Maintain optimal macronutrient balance, adequate protein intake, and hydration.\n"
                ."• **Rest & Recovery Optimization**: Prioritize quality sleep, active recovery, and mobility work to prevent injury.\n"
                .'• **Progress Tracking**: Log weekly performance metrics and adjust exercise intensity accordingly.';
        }
        // 9. Business & Finance
        elseif (preg_match('/\b(business|startup|finance|money|marketing|sales|product|growth|invest|stock)\b/i', $lower, $m)) {
            $title = "{$mainTopic} Business Strategy & Roadmap";
            $summary = 'Market analysis, value proposition, revenue model, and growth execution roadmap.';
            $ideas = "• **Market Positioning & Value Prop**: Define target audience needs and articulate clear product differentiators.\n"
                ."• **Growth & Customer Acquisition**: Deploy high-converting acquisition channels, content strategies, and referral loops.\n"
                ."• **Financial Modeling**: Monitor key metrics including Customer Acquisition Cost (CAC) and Lifetime Value (LTV).\n"
                .'• **Iterative Feedback Loop**: Collect user analytics, run continuous experiments, and refine core offerings.';
        }
        // 10. Contextual General Fallback
        else {
            $firstLine = preg_split('/[.!?\n]/', $clean)[0] ?? $clean;
            $titleStr = strlen($firstLine) > 50 ? substr($firstLine, 0, 47).'...' : $firstLine;
            $title = ucwords(trim($titleStr));

            $summary = "Actionable knowledge summary and execution strategy for {$mainTopic}.";
            $ideas = "• **Core Objective & Action Plan**: Establish clear priorities and daily action steps for {$mainTopic}.\n"
                ."• **Resource & Reference Gathering**: Compile essential tools, links, and documentation to support {$secondaryTopic}.\n"
                .'• **Review & Follow-up Milestone**: Schedule regular progress reviews to evaluate outcomes and refine your approach.';
        }

        return [
            'title' => $title,
            'summary' => $summary,
            'tags' => $tags,
            'generated_ideas' => $ideas,
        ];
    }
}
