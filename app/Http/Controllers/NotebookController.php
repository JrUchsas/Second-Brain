<?php

namespace App\Http\Controllers;

use App\Models\Notebook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotebookController extends Controller
{
    /**
     * Store a newly created notebook in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:30'],
        ]);

        auth()->user()->notebooks()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? 'indigo',
            'icon' => $validated['icon'] ?? 'folder',
        ]);

        return redirect()
            ->back()
            ->with('status', 'Notebook created successfully!');
    }

    /**
     * Remove the specified notebook from storage.
     */
    public function destroy(Notebook $notebook): RedirectResponse
    {
        abort_if($notebook->user_id !== auth()->id(), 403);

        $notebook->delete();

        return redirect()
            ->route('notes.index')
            ->with('status', 'Notebook deleted successfully.');
    }
}
