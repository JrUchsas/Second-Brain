<?php

namespace App\Jobs;

use App\Models\Note;
use App\Models\Tag;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeNote implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Note $note)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $openAIService): void
    {
        $this->note->update(['status' => 'processing']);

        try {
            $analysis = $openAIService->analyze($this->note->content);

            $tagIds = [];
            foreach ($analysis['tags'] as $tagName) {
                $cleanTag = trim(strtolower($tagName));
                if ($cleanTag !== '') {
                    $tag = Tag::firstOrCreate(['name' => $cleanTag]);
                    $tagIds[] = $tag->id;
                }
            }

            $enrichedContent = $this->note->content;
            if (! empty($analysis['generated_ideas'])) {
                // Append generated ideas section if not already present
                if (! str_contains($enrichedContent, 'AI Generated Ideas & Key Takeaways')) {
                    $enrichedContent .= "\n\n---\n### 💡 AI Generated Ideas & Key Takeaways\n\n".$analysis['generated_ideas'];
                }
            }

            $this->note->update([
                'title' => $analysis['title'],
                'summary' => $analysis['summary'],
                'content' => $enrichedContent,
                'status' => 'completed',
            ]);

            $this->note->tags()->sync($tagIds);

            // Auto-link related notes owned by the same user sharing at least one tag
            if (! empty($tagIds)) {
                $relatedNoteIds = Note::query()
                    ->where('user_id', $this->note->user_id)
                    ->where('id', '!=', $this->note->id)
                    ->whereHas('tags', function ($query) use ($tagIds) {
                        $query->whereIn('tags.id', $tagIds);
                    })
                    ->pluck('id');

                $this->note->linkedNotes()->sync($relatedNoteIds);
            }
        } catch (Throwable $e) {
            Log::error("Failed to analyze note {$this->note->id}: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            $this->note->update(['status' => 'failed']);

            throw $e;
        }
    }
}
