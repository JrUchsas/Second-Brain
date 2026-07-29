<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('notes.index') }}" class="p-2 rounded-xl border border-gray-200 dark:border-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 dark:text-slate-100 leading-tight">
                        {{ $note->title ?? 'Untitled Note' }}
                    </h2>
                    
                    <!-- Move Note to Notebook Dropdown -->
                    <form action="{{ route('notes.notebook', $note) }}" method="POST" class="inline-flex items-center gap-1.5 mt-1">
                        @csrf
                        @method('PATCH')
                        <span class="text-xs text-gray-400 dark:text-slate-500 font-medium">Notebook:</span>
                        <select
                            name="notebook_id"
                            onchange="this.form.submit()"
                            class="text-xs rounded-lg border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 py-1 pl-2.5 pr-7 font-semibold focus:ring-indigo-500 focus:border-indigo-500 shadow-sm cursor-pointer"
                        >
                            <option value="">None (General)</option>
                            @foreach($notebooks as $nb)
                                <option value="{{ $nb->id }}" {{ $note->notebook_id === $nb->id ? 'selected' : '' }}>
                                    📁 {{ $nb->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if ($note->status === 'completed')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        AI Completed
                    </span>
                @elseif ($note->status === 'processing')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                        <svg class="w-3.5 h-3.5 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Processing...
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        Queued for AI
                    </span>
                @endif

                <form action="{{ route('notes.destroy', $note) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this note?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 font-semibold text-xs transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Note
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div
        x-data="{
            status: '{{ $note->status }}',
            init() {
                if (this.status === 'pending' || this.status === 'processing') {
                    setInterval(async () => {
                        const res = await fetch('{{ route('notes.status', $note) }}');
                        const data = await res.json();
                        if (data.status === 'completed' || data.status === 'failed') {
                            window.location.reload();
                        }
                    }, 1000);
                }
            }
        }}"
        class="py-12 bg-gray-50/50 dark:bg-slate-950 min-h-screen transition-colors duration-200"
    >
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- AI Summary Callout Box -->
            @if ($note->summary)
                <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-violet-950 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden border border-indigo-800/40">
                    <div class="absolute right-0 top-0 -mr-6 -mt-6 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 text-indigo-300 font-semibold text-xs uppercase tracking-wider mb-2">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            AI Executive Summary
                        </div>
                        <p class="text-indigo-50 font-medium leading-relaxed text-base">
                            {{ $note->summary }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Main Note Body -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl shadow-slate-200/40 dark:shadow-none p-8">
                
                <!-- Tags Row -->
                @if ($note->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-6 pb-4 border-b border-gray-100 dark:border-slate-800">
                        @foreach ($note->tags as $tag)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/50">
                                #{{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center justify-between pb-6 mb-6 border-b border-gray-100 dark:border-slate-800 text-xs text-gray-400 dark:text-slate-500 font-medium">
                    <span>Created {{ $note->created_at->format('F j, Y \a\t g:i A') }}</span>
                    <span>{{ Str::wordCount($note->content) }} words</span>
                </div>

                <!-- Full Content -->
                <div class="prose max-w-none text-gray-800 dark:text-slate-200 leading-relaxed font-sans whitespace-pre-wrap">
                    {{ $note->content }}
                </div>
            </div>

            <!-- Related / Linked Notes -->
            @if ($note->linkedNotes->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm p-6">
                    <h4 class="font-bold text-gray-900 dark:text-slate-100 text-base mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        Related Notes (Auto-Linked)
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($note->linkedNotes as $related)
                            <a href="{{ route('notes.show', $related) }}" class="p-4 rounded-xl border border-gray-100 dark:border-slate-800 hover:border-indigo-200 dark:hover:border-indigo-800 hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition group">
                                <h5 class="font-semibold text-gray-900 dark:text-slate-100 text-sm group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                    {{ $related->title ?? 'Untitled Note' }}
                                </h5>
                                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 line-clamp-2">
                                    {{ $related->summary ?? Str::limit($related->content, 80) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
