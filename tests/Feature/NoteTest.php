<?php

use App\Jobs\AnalyzeNote;
use App\Models\Note;
use App\Models\User;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Queue;

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

it('creates a note and dispatches analyze note job', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/notes', [
        'content' => 'Laravel 12 features a streamlined file structure and performance enhancements.',
    ]);

    $response->assertRedirect('/notes');

    $this->assertDatabaseHas('notes', [
        'user_id' => $user->id,
        'content' => 'Laravel 12 features a streamlined file structure and performance enhancements.',
        'status' => 'pending',
    ]);

    Queue::assertPushed(AnalyzeNote::class);
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
