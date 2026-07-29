<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeNote;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteController extends Controller
{
    /**
     * Display a listing of the user's notes.
     */
    public function index(): View
    {
        $notes = auth()->user()
            ->notes()
            ->with(['tags', 'linkedNotes'])
            ->latest()
            ->get();

        return view('notes.index', [
            'notes' => $notes,
            'searchQuery' => null,
        ]);
    }

    /**
     * Show the form for creating a new note.
     */
    public function create(): View
    {
        return view('notes.create');
    }

    /**
     * Store a newly created note in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:5'],
        ]);

        /** @var Note $note */
        $note = auth()->user()->notes()->create([
            'content' => $validated['content'],
            'status' => 'pending',
        ]);

        AnalyzeNote::dispatch($note);

        return redirect()
            ->route('notes.index')
            ->with('status', 'Note saved! AI is processing title, summary, and tags in the background.');
    }

    /**
     * Display the specified note.
     */
    public function show(Note $note): View
    {
        abort_if($note->user_id !== auth()->id(), 403);

        $note->load(['tags', 'linkedNotes']);

        return view('notes.show', [
            'note' => $note,
        ]);
    }

    /**
     * Check the status of a specific note for polling.
     */
    public function status(Note $note): JsonResponse
    {
        abort_if($note->user_id !== auth()->id(), 403);

        return response()->json([
            'id' => $note->id,
            'status' => $note->status,
            'title' => $note->title,
            'summary' => $note->summary,
        ]);
    }

    /**
     * Search user's notes across title, summary, content, and tags.
     */
    public function search(Request $request): View
    {
        $query = trim((string) $request->input('q'));

        if (empty($query)) {
            return view('notes.index', [
                'notes' => auth()->user()->notes()->with(['tags', 'linkedNotes'])->latest()->get(),
                'searchQuery' => null,
            ]);
        }

        // Use Scout search filtered by user_id with fallback to Eloquent
        $notes = Note::search($query)
            ->where('user_id', auth()->id())
            ->get();

        // If Scout returns empty or database driver fallback, perform Eloquent search
        if ($notes->isEmpty()) {
            $notes = auth()->user()
                ->notes()
                ->with(['tags', 'linkedNotes'])
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
            $notes->load(['tags', 'linkedNotes']);
        }

        return view('notes.index', [
            'notes' => $notes,
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
