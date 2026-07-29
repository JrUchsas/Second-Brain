<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {{ __('Capture Raw Thought') }}
            </h2>
            <a href="{{ route('notes.index') }}" class="text-sm text-gray-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-1 font-medium">
                &larr; Back to Notes
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50/50 dark:bg-slate-950 min-h-screen transition-colors duration-200">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-gray-100 dark:border-slate-800 p-8 transition-all">
                
                <div class="mb-6 p-4 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 flex items-start gap-3">
                    <div class="p-2 bg-indigo-600 dark:bg-indigo-500 rounded-lg text-white shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-indigo-950 dark:text-indigo-200 text-sm">AI Instant Processing</h4>
                        <p class="text-xs text-indigo-700 dark:text-indigo-300 mt-0.5">Dump your raw ideas, meeting notes, articles, or rough text below. Our background workers will automatically distill a title, summary, project ideas, and relevant tags.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('notes.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="content" class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">
                            Raw Note Content
                        </label>
                        <textarea
                            id="content"
                            name="content"
                            rows="12"
                            required
                            placeholder="Type or paste anything here... (e.g. key takeaways from a meeting, raw project idea, tech article notes)"
                            class="w-full rounded-xl border-gray-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 shadow-sm font-mono leading-relaxed p-4 text-base"
                        >{{ old('content') }}</textarea>

                        @error('content')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-slate-800">
                        <a href="{{ route('notes.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 text-sm font-medium transition">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center gap-2 px-7 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-semibold text-sm shadow-md hover:shadow-lg shadow-indigo-500/25 transition-all transform active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Process with AI
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
