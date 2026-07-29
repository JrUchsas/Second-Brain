<?php

use App\Http\Controllers\NotebookController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('notes.index');
    }

    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('notes.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Notes & AI Features
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
    Route::get('/notes/create', [NoteController::class, 'create'])->name('notes.create');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::get('/notes/search', [NoteController::class, 'search'])->name('notes.search');
    Route::get('/notes/{note}', [NoteController::class, 'show'])->name('notes.show');
    Route::get('/notes/{note}/status', [NoteController::class, 'status'])->name('notes.status');
    Route::patch('/notes/{note}/notebook', [NoteController::class, 'updateNotebook'])->name('notes.notebook');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    // Notebooks & Categories
    Route::post('/notebooks', [NotebookController::class, 'store'])->name('notebooks.store');
    Route::delete('/notebooks/{notebook}', [NotebookController::class, 'destroy'])->name('notebooks.destroy');

    // Interactive 2D Knowledge Graph
    Route::get('/graph', [NoteController::class, 'graph'])->name('graph');
    Route::get('/graph/data', [NoteController::class, 'graphData'])->name('graph.data');

    // User Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
