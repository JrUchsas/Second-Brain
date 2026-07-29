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
        x-data="{ showNewNotebookModal: false }"
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

            <!-- Feature 5: Notebooks Filter Tabs & Add Notebook Button -->
            <div class="mb-8 flex items-center justify-between gap-4 overflow-x-auto pb-2 border-b border-gray-200/60 dark:border-slate-800">
                <div class="flex items-center gap-2 shrink-0">
                    <a
                        href="{{ route('notes.index') }}"
                        class="px-4 py-2 rounded-xl text-sm font-semibold transition border {{ is_null($activeNotebookId) ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800' }}"
                    >
                        📚 All Notes
                    </a>

                    @foreach ($notebooks as $nb)
                        <div class="relative group flex items-center">
                            <a
                                href="{{ route('notes.index', ['notebook' => $nb->id]) }}"
                                class="px-4 py-2 rounded-xl text-sm font-semibold transition border flex items-center gap-1.5 {{ $activeNotebookId === $nb->id ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800' }}"
                            >
                                📁 {{ $nb->name }}
                            </a>
                        </div>
                    @endforeach
                </div>

                <button
                    @click="showNewNotebookModal = true"
                    type="button"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/80 transition flex items-center gap-1 shrink-0"
                >
                    + New Notebook
                </button>
            </div>

            @if (!empty($searchQuery))
                <div class="mb-6 flex items-center justify-between bg-indigo-50/50 dark:bg-indigo-950/30 p-4 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                    <p class="text-sm text-indigo-900 dark:text-indigo-200 font-medium">
                        Search results for <span class="font-bold text-indigo-700 dark:text-indigo-400">"{{ $searchQuery }}"</span>
                        <span class="text-xs text-indigo-500 dark:text-indigo-400 font-normal ml-1">({{ $notes->count() }} matches)</span>
                    </p>
                    <a href="{{ route('notes.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Clear Search</a>
                </div>
            @endif

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
                        <div
                            x-data="{
                                id: {{ $note->id }},
                                status: '{{ $note->status }}',
                                title: @js($note->title ?? 'Untitled Note'),
                                summary: @js($note->summary ?? Str::limit($note->content, 140)),
                                tags: @js($note->tags->pluck('name')->all()),
                                pollTimer: null,
                                checkStatus() {
                                    if (this.status === 'completed' || this.status === 'failed') return;
                                    fetch('/notes/' + this.id + '/status')
                                        .then(res => res.json())
                                        .then(data => {
                                            this.status = data.status;
                                            if (data.title) this.title = data.title;
                                            if (data.summary) this.summary = data.summary;
                                            if (data.tags) this.tags = data.tags;
                                            if (data.status === 'completed' || data.status === 'failed') {
                                                clearInterval(this.pollTimer);
                                            }
                                        })
                                        .catch(() => {});
                                },
                                init() {
                                    if (this.status === 'pending' || this.status === 'processing') {
                                        this.pollTimer = setInterval(() => this.checkStatus(), 1000);
                                    }
                                }
                            }"
                            @click="if (!$event.target.closest('button') && !$event.target.closest('form')) window.location.href = '/notes/' + id"
                            class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group cursor-pointer"
                        >
                            <!-- Header status & title -->
                            <div class="p-6 pb-4 border-b border-gray-50 dark:border-slate-800/60 flex-1">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    
                                    <!-- Dynamic Real-Time Status Badge -->
                                    <template x-if="status === 'completed'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition-all">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Completed
                                        </span>
                                    </template>

                                    <template x-if="status === 'processing'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition-all">
                                            <svg class="w-3 h-3 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Processing AI...
                                        </span>
                                    </template>

                                    <template x-if="status === 'failed'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition-all">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Processing Failed
                                        </span>
                                    </template>

                                    <template x-if="status === 'pending'">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 transition-all">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            Queued (Processing...)
                                        </span>
                                    </template>

                                    <div class="flex items-center gap-2">
                                        @if($note->notebook)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                📁 {{ $note->notebook->name }}
                                            </span>
                                        @endif
                                        <form action="{{ route('notes.destroy', $note) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this note?');" class="inline" @click.stop>
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
                                    <a :href="'/notes/' + id" x-text="title" class="hover:underline"></a>
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-slate-400 mt-2 line-clamp-3 leading-relaxed" x-text="summary">
                                </p>
                            </div>

                            <!-- Real-Time Dynamic Tags -->
                            <div class="px-6 py-3 bg-gray-50/50 dark:bg-slate-900/50 flex flex-wrap gap-1.5 min-h-[44px] items-center">
                                <template x-for="t in tags" :key="t">
                                    <span class="inline-block px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/50" x-text="'#' + t">
                                    </span>
                                </template>
                                <template x-if="tags.length === 0">
                                    <span class="text-xs text-gray-400 dark:text-slate-500 italic" x-text="status === 'completed' ? 'No tags generated' : 'Generating AI tags...'"></span>
                                </template>
                            </div>

                            <!-- Card Footer -->
                            <div class="px-6 py-3 border-t border-gray-100 dark:border-slate-800/80 flex items-center justify-between text-xs font-medium text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-900">
                                <span>{{ Str::wordCount($note->content) }} words</span>
                                <a :href="'/notes/' + id" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold inline-flex items-center gap-1">
                                    Read Note &rarr;
                                </a>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

        </div>

        <!-- New Notebook Modal -->
        <div x-show="showNewNotebookModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div @click.away="showNewNotebookModal = false" class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-gray-100 dark:border-slate-800">
                <h3 class="font-bold text-lg text-gray-900 dark:text-slate-100 mb-4">Create New Notebook</h3>
                
                <form action="{{ route('notebooks.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Notebook Name</label>
                        <input
                            type="text"
                            name="name"
                            required
                            placeholder="e.g. Work, Gaming, Personal, Study"
                            class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200/50"
                        />
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showNewNotebookModal = false"
                            class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-md transition"
                        >
                            Create Notebook
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
