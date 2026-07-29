<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeNote;
use App\Models\Note;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NoteController extends Controller
{
    /**
     * Display a listing of the user's notes, filtered by notebook if selected.
     */
    public function index(Request $request): View
    {
        $notebookId = $request->query('notebook');

        $query = auth()->user()
            ->notes()
            ->with(['tags', 'linkedNotes', 'notebook']);

        if (! empty($notebookId)) {
            $query->where('notebook_id', $notebookId);
        }

        $notes = $query->latest()->get();
        $notebooks = auth()->user()->notebooks()->get();

        return view('notes.index', [
            'notes' => $notes,
            'notebooks' => $notebooks,
            'activeNotebookId' => $notebookId ? (int) $notebookId : null,
            'searchQuery' => null,
        ]);
    }

    /**
     * Show the form for creating a new note.
     */
    public function create(): View
    {
        $notebooks = auth()->user()->notebooks()->get();

        return view('notes.create', [
            'notebooks' => $notebooks,
        ]);
    }

    /**
     * Store a newly created note in storage and trigger asynchronous AI analysis.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:3'],
            'notebook_id' => ['nullable', 'exists:notebooks,id'],
        ]);

        /** @var Note $note */
        $note = auth()->user()->notes()->create([
            'content' => $validated['content'],
            'notebook_id' => $validated['notebook_id'] ?? null,
            'status' => 'pending',
        ]);

        dispatch(function () use ($note) {
            $note->update(['status' => 'processing']);
            sleep(1);
            app(AnalyzeNote::class, ['note' => $note])->handle(app(OpenAIService::class));
        })->afterResponse();

        return redirect()
            ->route('notes.index')
            ->with('status', 'Note submitted! Watch the real-time AI processing on your dashboard.');
    }

    /**
     * Display the specified note.
     */
    public function show(Note $note): View
    {
        abort_if($note->user_id !== auth()->id(), 403);

        $note->load(['tags', 'linkedNotes', 'notebook']);
        $notebooks = auth()->user()->notebooks()->get();

        return view('notes.show', [
            'note' => $note,
            'notebooks' => $notebooks,
        ]);
    }

    /**
     * Move an existing note to a different notebook.
     */
    public function updateNotebook(Note $note, Request $request): JsonResponse|RedirectResponse
    {
        abort_if($note->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'notebook_id' => ['nullable', 'exists:notebooks,id'],
        ]);

        $note->update([
            'notebook_id' => $validated['notebook_id'] ?: null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'notebook_id' => $note->notebook_id,
            ]);
        }

        return redirect()->back()->with('status', 'Moved note to notebook!');
    }

    /**
     * Check the status of a specific note for live real-time polling.
     */
    public function status(Note $note): JsonResponse
    {
        abort_if($note->user_id !== auth()->id(), 403);

        $note->load(['tags', 'notebook']);

        return response()->json([
            'id' => $note->id,
            'status' => $note->status,
            'title' => $note->title ?? 'Untitled Note',
            'summary' => $note->summary ?? Str::limit($note->content, 140),
            'tags' => $note->tags->pluck('name')->all(),
            'notebook' => $note->notebook ? $note->notebook->name : null,
        ]);
    }

    /**
     * Render the Interactive 2D Knowledge Graph page.
     */
    public function graph(): View
    {
        return view('graph');
    }

    /**
     * Return nodes and edges for the Knowledge Graph visualization.
     */
    public function graphData(): JsonResponse
    {
        $notes = auth()->user()->notes()->with(['tags', 'linkedNotes', 'notebook'])->get();

        $nodes = [];
        $edges = [];
        $addedTagIds = [];

        foreach ($notes as $note) {
            $nodes[] = [
                'id' => 'note_'.$note->id,
                'label' => $note->title ?? 'Untitled Note',
                'group' => 'notes',
                'note_id' => $note->id,
                'color' => '#6366f1',
                'shape' => 'dot',
                'size' => 20,
            ];

            foreach ($note->tags as $tag) {
                $tagNodeId = 'tag_'.$tag->id;
                if (! isset($addedTagIds[$tag->id])) {
                    $nodes[] = [
                        'id' => $tagNodeId,
                        'label' => '#'.$tag->name,
                        'group' => 'tags',
                        'color' => '#10b981',
                        'shape' => 'ellipse',
                        'size' => 14,
                    ];
                    $addedTagIds[$tag->id] = true;
                }

                $edges[] = [
                    'from' => 'note_'.$note->id,
                    'to' => $tagNodeId,
                    'color' => ['color' => '#cbd5e1', 'highlight' => '#818cf8'],
                ];
            }

            foreach ($note->linkedNotes as $linked) {
                if ($note->id < $linked->id) {
                    $edges[] = [
                        'from' => 'note_'.$note->id,
                        'to' => 'note_'.$linked->id,
                        'color' => ['color' => '#6366f1', 'highlight' => '#4f46e5'],
                        'width' => 2,
                    ];
                }
            }
        }

        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }

    /**
     * Search user's notes across title, summary, content, and tags.
     */
    public function search(Request $request): View
    {
        $query = trim((string) $request->input('q'));
        $notebooks = auth()->user()->notebooks()->get();

        if (empty($query)) {
            return view('notes.index', [
                'notes' => auth()->user()->notes()->with(['tags', 'linkedNotes', 'notebook'])->latest()->get(),
                'notebooks' => $notebooks,
                'activeNotebookId' => null,
                'searchQuery' => null,
            ]);
        }

        $notes = Note::search($query)
            ->where('user_id', auth()->id())
            ->get();

        if ($notes->isEmpty()) {
            $notes = auth()->user()
                ->notes()
                ->with(['tags', 'linkedNotes', 'notebook'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('summary', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%")
                        ->orWhereHas('tags', function ($tagQuery) use ($query) {
                            $tagQuery->where('name', 'like', "%{$query}%");
                        });
                })
                ->latest()
                ->get();
        } else {
            $notes->load(['tags', 'linkedNotes', 'notebook']);
        }

        return view('notes.index', [
            'notes' => $notes,
            'notebooks' => $notebooks,
            'activeNotebookId' => null,
            'searchQuery' => $query,
        ]);
    }

    /**
     * Remove the specified note from storage.
     */
    public function destroy(Note $note): RedirectResponse
    {
        abort_if($note->user_id !== auth()->id(), 403);

        $note->delete();

        return redirect()
            ->route('notes.index')
            ->with('status', 'Note deleted successfully.');
    }
}
