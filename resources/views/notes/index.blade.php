<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                {{ __('Second Brain Notes') }}
            </h2>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <form action="{{ route('notes.search') }}" method="GET" class="relative flex-1 sm:w-72">
                    <input
                        type="text"
                        name="q"
                        value="{{ $searchQuery ?? '' }}"
                        placeholder="Search notes or tags..."
                        class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 shadow-sm"
                    />
                    <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </form>

                <a href="{{ route('notes.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm shadow-md hover:shadow-lg transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Note
                </a>
            </div>
        </div>
    </x-slot>

    <div
        x-data="{
            hasPending: {{ $notes->contains(fn($n) => in_array($n->status, ['pending', 'processing'])) ? 'true' : 'false' }},
            timer: null,
            init() {
                if (this.hasPending) {
                    this.timer = setInterval(() => {
                        window.location.reload();
                    }, 3000);
                }
            }
        }}"
        class="py-12 bg-gray-50/50 dark:bg-slate-950 min-h-screen transition-colors duration-200"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm font-medium flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                </div>
            @endif

            @if (!empty($searchQuery))
                <div class="mb-6 flex items-center justify-between bg-indigo-50/50 dark:bg-indigo-950/30 p-4 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                    <p class="text-sm text-indigo-900 dark:text-indigo-200 font-medium">
                        Search results for <span class="font-bold text-indigo-700 dark:text-indigo-400">"{{ $searchQuery }}"</span>
                        <span class="text-xs text-indigo-500 dark:text-indigo-400 font-normal ml-1">({{ $notes->count() }} matches)</span>
                    </p>
                    <a href="{{ route('notes.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Clear Search</a>
                </div>
            @endif

            <template x-if="hasPending">
                <div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 text-xs font-medium flex items-center gap-3 animate-pulse">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>AI workers are actively summarizing and tagging your notes. Page auto-refreshes every 3 seconds...</span>
                </div>
            </template>

            @if ($notes->isEmpty())
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-12 text-center shadow-sm border border-gray-100 dark:border-slate-800 max-w-lg mx-auto">
                    <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-950/50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">No notes found</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400 mt-1 mb-6">Start building your Second Brain by creating your first note.</p>
                    <a href="{{ route('notes.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium text-sm shadow-md hover:bg-indigo-700 transition">
                        Create First Note
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($notes as $note)
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group">
                            
                            <!-- Header status & title -->
                            <div class="p-6 pb-4 border-b border-gray-50 dark:border-slate-800/60 flex-1">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    @if ($note->status === 'completed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Completed
                                        </span>
                                    @elseif ($note->status === 'processing')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            <svg class="w-3 h-3 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Processing AI...
                                        </span>
                                    @elseif ($note->status === 'failed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Processing Failed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            Queued (Pending)
                                        </span>
                                    @endif

                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400 dark:text-slate-500 font-medium">
                                            {{ $note->created_at->diffForHumans() }}
                                        </span>
                                        <form action="{{ route('notes.destroy', $note) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this note?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 rounded-lg text-gray-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Delete Note">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <h3 class="font-bold text-gray-900 dark:text-slate-100 text-lg group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                    <a href="{{ route('notes.show', $note) }}">
                                        {{ $note->title ?? 'Untitled Note' }}
                                    </a>
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-slate-400 mt-2 line-clamp-3 leading-relaxed">
                                    {{ $note->summary ?? Str::limit($note->content, 140) }}
                                </p>
                            </div>

                            <!-- Tags -->
                            <div class="px-6 py-3 bg-gray-50/50 dark:bg-slate-900/50 flex flex-wrap gap-1.5 min-h-[44px] items-center">
                                @forelse ($note->tags as $tag)
                                    <span class="inline-block px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/50">
                                        #{{ $tag->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 dark:text-slate-500 italic">No tags generated yet</span>
                                @endforelse
                            </div>

                            <!-- Card Footer -->
                            <div class="px-6 py-3 border-t border-gray-100 dark:border-slate-800/80 flex items-center justify-between text-xs font-medium text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-900">
                                <span>{{ Str::wordCount($note->content) }} words</span>
                                <a href="{{ route('notes.show', $note) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold inline-flex items-center gap-1">
                                    Read Note &rarr;
                                </a>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
