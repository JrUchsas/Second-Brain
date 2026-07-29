<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    /**
     * Analyze raw note content or topic prompt, returning title, summary, tags, and AI generated ideas.
     *
     * @return array{title: string, summary: string, tags: list<string>, generated_ideas: string}
     */
    public function analyze(string $content): array
    {
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
                                'content' => 'You are an AI Second Brain knowledge engine. Analyze the provided note text or prompt. Return a JSON object with exact keys: "title" (string, concise descriptive title), "summary" (string, executive summary of the topic), "tags" (array of 3 to 5 string tags), and "generated_ideas" (string, 3 to 5 creative, actionable project ideas or key takeaways formatted cleanly with Markdown bullet points and bold titles).',
                            ],
                            [
                                'role' => 'user',
                                'content' => $content,
                            ],
                        ],
                        'temperature' => 0.4,
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

        return $this->fallbackAnalysis($content);
    }

    /**
     * Advanced local NLP heuristic analysis engine when OpenAI API key is missing or quota is exceeded.
     *
     * @return array{title: string, summary: string, tags: list<string>, generated_ideas: string}
     */
    protected function fallbackAnalysis(string $content): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $content));
        $lower = strtolower($clean);

        // Generate title
        $firstLine = preg_split('/[.!?\n]/', $clean)[0] ?? $clean;
        $rawTitle = strlen($firstLine) > 60 ? substr($firstLine, 0, 57).'...' : $firstLine;
        $title = ucwords(trim($rawTitle ?: 'Untitled Note'));

        // Extract key nouns & tags
        preg_match_all('/\b[a-zA-Z]{3,}\b/', $lower, $matches);
        $words = $matches[0] ?? [];
        $stopWords = [
            'about', 'above', 'across', 'after', 'again', 'against', 'almost', 'alone', 'along', 'already', 'also',
            'although', 'always', 'among', 'another', 'other', 'there', 'these', 'those', 'where', 'which', 'while',
            'would', 'could', 'should', 'first', 'second', 'with', 'from', 'into', 'that', 'this', 'take', 'care',
            'your', 'some', 'what', 'when', 'how', 'have', 'has', 'had', 'make', 'need', 'want', 'like', 'good', 'well',
        ];
        $keywords = array_values(array_unique(array_diff($words, $stopWords)));
        $tags = array_slice($keywords, 0, 4);
        if (empty($tags)) {
            $tags = ['notes', 'knowledge', 'ideas'];
        }

        $mainTopic = ! empty($keywords[0]) ? ucfirst($keywords[0]) : 'Topic';
        $secondaryTopic = ! empty($keywords[1]) ? ucfirst($keywords[1]) : 'Insights';

        // 1. Pets / Animals / Birds
        if (preg_match('/\b(budgie|bird|pet|dog|cat|parrot|animal|fish|hamster|rabbit)\b/i', $lower, $m)) {
            $petName = ucfirst($m[1]);
            $title = "Complete {$petName} Care & Wellness Guide";
            $summary = "Essential daily routines, nutrition, environment setup, and health recommendations for keeping your {$petName} healthy and happy.";
            $ideas = "• **Balanced Nutrition & Diet**: Provide fresh species-appropriate food, clean daily water, and essential vitamin supplements.\n"
                ."• **Safe Environment & Shelter**: Maintain clean, comfortable housing with adequate temperature control and mental stimulation.\n"
                ."• **Daily Interaction & Exercise**: Schedule dedicated time for physical activity, bonding, and safe play sessions.\n"
                ."• **Health & Routine Monitoring**: Keep track of appetite, behavior, grooming, and schedule regular veterinary checkups.";
        }
        // 2. Programming / Tech / Software
        elseif (preg_match('/\b(laravel|php|js|javascript|python|coding|react|vue|api|code|database|sql)\b/i', $lower, $m)) {
            $tech = strtoupper($m[1]);
            $title = "{$tech} Project Architecture & Implementation Strategy";
            $summary = "Technical breakdown focusing on clean code architecture, database modeling, background queues, and automated testing.";
            $ideas = "• **Core Architecture & Scaffolding**: Set up modular controllers, service layers, and migration schemas.\n"
                ."• **Queue-Driven Automation**: Implement asynchronous background jobs for external API calls and background processing.\n"
                ."• **Search & Query Optimization**: Utilize database indexes and Scout full-text search for fast data retrieval.\n"
                ."• **Security & Testing Suite**: Enforce robust validation, rate limiting, and write comprehensive Pest feature tests.";
        }
        // 3. Cooking / Recipe / Food
        elseif (preg_match('/\b(recipe|cook|food|bake|kitchen|meal|dish|dinner|lunch|breakfast)\b/i', $lower, $m)) {
            $title = "Culinary Guide & Recipe Breakdown for {$title}";
            $summary = "Preparation steps, ingredient selection, flavor balance, and cooking guidelines for optimal results.";
            $ideas = "• **Mise en Place & Prep**: Organize fresh ingredients, prep tools, and preheat equipment before cooking.\n"
                ."• **Precision Technique & Timing**: Follow temperature controls and cooking times to achieve ideal flavor and texture.\n"
                ."• **Seasoning & Presentation**: Balance acidity, salt, and herbs, finishing with clean visual plating.\n"
                ."• **Safe Storage & Meal Prep**: Store leftovers in sealed containers and plan portioning for upcoming meals.";
        }
        // 4. Health / Fitness / Exercise
        elseif (preg_match('/\b(workout|fitness|exercise|diet|gym|health|running|muscle|weight)\b/i', $lower, $m)) {
            $title = "Fitness & Wellness Action Plan for {$title}";
            $summary = "Targeted exercise routine, nutrition guidelines, recovery protocols, and progress tracking.";
            $ideas = "• **Structured Training Routine**: Execute progressive workouts with proper technique and consistent frequency.\n"
                ."• **Nutritional Fueling**: Maintain optimal macronutrient balance, adequate protein intake, and hydration.\n"
                ."• **Rest & Recovery Optimization**: Prioritize quality sleep, active recovery, and mobility work to prevent injury.\n"
                ."• **Progress Tracking**: Log weekly performance metrics and adjust exercise intensity accordingly.";
        }
        // 5. Business / Finance / Startup
        elseif (preg_match('/\b(business|startup|finance|money|marketing|sales|product|growth)\b/i', $lower, $m)) {
            $title = "Business Strategy & Execution Plan for {$title}";
            $summary = "Market analysis, value proposition, revenue model, and growth execution roadmap.";
            $ideas = "• **Market Positioning & Value Prop**: Define target audience needs and articulate clear product differentiators.\n"
                ."• **Growth & Customer Acquisition**: Deploy high-converting acquisition channels, content strategies, and referral loops.\n"
                ."• **Financial Modeling**: Monitor key metrics including Customer Acquisition Cost (CAC) and Lifetime Value (LTV).\n"
                ."• **Iterative Feedback Loop**: Collect user analytics, run continuous experiments, and refine core offerings.";
        }
        // 6. General / Fallback NLP
        else {
            $title = "Key Insights & Project Roadmap: {$title}";
            $summary = "Synthesized analysis for {$mainTopic} focusing on strategic goals, implementation steps, and resource planning.";
            $ideas = "• **Core Objective & Action Plan**: Establish clear milestones and daily action steps for {$mainTopic}.\n"
                ."• **Resource & Reference Gathering**: Compile key tools, documentation, and reference materials to support {$secondaryTopic}.\n"
                ."• **Execution & Feedback Loop**: Implement initial steps and schedule regular progress reviews to optimize results.";
        }

        return [
            'title' => $title,
            'summary' => $summary,
            'tags' => $tags,
            'generated_ideas' => $ideas,
        ];
    }
}
