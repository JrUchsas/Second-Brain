<?php

use App\Jobs\AnalyzeNote;
use App\Models\Note;
use App\Models\Notebook;
use App\Models\User;
use App\Services\OpenAIService;

it('redirects unauthenticated users to login page', function () {
    $response = $this->get('/notes');

    $response->assertRedirect('/login');
});

it('allows authenticated user to view notes index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/notes');

    $response->assertStatus(200);
    $response->assertSee('Second Brain Notes');
});

it('creates a note and redirects to notes index page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/notes', [
        'content' => 'Laravel 12 features a streamlined file structure and performance enhancements.',
    ]);

    $note = Note::where('user_id', $user->id)->first();

    $response->assertRedirect('/notes');

    $this->assertDatabaseHas('notes', [
        'id' => $note->id,
        'user_id' => $user->id,
    ]);
});

it('analyzes note content using OpenAIService', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create([
        'user_id' => $user->id,
        'content' => 'Artificial intelligence is revolutionizing note taking and knowledge management.',
        'status' => 'pending',
    ]);

    $mockService = Mockery::mock(OpenAIService::class);
    $mockService->shouldReceive('analyze')
        ->once()
        ->with($note->content)
        ->andReturn([
            'title' => 'AI Knowledge Management',
            'summary' => 'AI is changing note taking.',
            'tags' => ['ai', 'knowledge', 'productivity'],
            'generated_ideas' => '• AI Knowledge Graph Integration',
            'tasks' => [],
        ]);

    $job = new AnalyzeNote($note);
    $job->handle($mockService);

    $note->refresh();

    expect($note->status)->toBe('completed');
    expect($note->title)->toBe('AI Knowledge Management');
    expect($note->summary)->toBe('AI is changing note taking.');
    expect($note->tags)->toHaveCount(3);
    expect($note->tags->pluck('name')->all())->toContain('ai', 'knowledge', 'productivity');
});

it('detects gibberish input and marks analysis as invalid input', function () {
    $service = new OpenAIService;
    $result = $service->analyze('asdasfasf');

    expect($result['title'])->toBe('Meaningless / Invalid Input');
    expect($result['tags'])->toContain('invalid-input');
});

it('auto-corrects typos and misspelled words before analyzing note', function () {
    $service = new OpenAIService;
    $result = $service->analyze('valornt videoes watch in yotube');

    expect($result['title'])->toContain('Valorant');
    expect($result['tags'])->toContain('valorant');
});

it('creates a notebook and filters notes by notebook', function () {
    $user = User::factory()->create();
    $notebook = Notebook::create(['user_id' => $user->id, 'name' => 'Gaming']);
    $note = Note::factory()->create(['user_id' => $user->id, 'notebook_id' => $notebook->id]);

    $response = $this->actingAs($user)->get("/notes?notebook={$notebook->id}");

    $response->assertStatus(200);
    $response->assertSee('Gaming');
});

it('allows moving an existing note to a different notebook', function () {
    $user = User::factory()->create();
    $notebook = Notebook::create(['user_id' => $user->id, 'name' => 'Gaming']);
    $note = Note::factory()->create(['user_id' => $user->id, 'notebook_id' => null]);

    $response = $this->actingAs($user)->patch("/notes/{$note->id}/notebook", [
        'notebook_id' => $notebook->id,
    ]);

    $response->assertRedirect();
    expect($note->fresh()->notebook_id)->toBe($notebook->id);
});

it('returns knowledge graph data as json', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id, 'title' => 'Graph Test Note']);

    $response = $this->actingAs($user)->getJson('/graph/data');

    $response->assertStatus(200);
    $response->assertJsonStructure(['nodes', 'edges']);
});

it('returns note status payload via JSON endpoint', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
        'title' => 'Sample Note Title',
        'summary' => 'Sample summary.',
    ]);

    $response = $this->actingAs($user)->getJson("/notes/{$note->id}/status");

    $response->assertStatus(200);
    $response->assertJson([
        'id' => $note->id,
        'status' => 'completed',
        'title' => 'Sample Note Title',
        'summary' => 'Sample summary.',
    ]);
});

it('allows user to delete their note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete("/notes/{$note->id}");

    $response->assertRedirect('/notes');
    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});
