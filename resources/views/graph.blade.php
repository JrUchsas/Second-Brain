<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                {{ __('Interactive Knowledge Graph') }}
            </h2>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">Click any node to navigate to note</span>
                <a href="{{ route('notes.index') }}" class="px-3.5 py-1.5 rounded-xl border border-gray-200 dark:border-slate-800 text-xs font-semibold text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Include Vis.js Network CDN for 2D Physics Graph -->
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

    <div
        x-data="{
            loading: true,
            network: null,
            initGraph() {
                fetch('{{ route('graph.data') }}')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        const container = document.getElementById('knowledge-graph-canvas');
                        
                        const graphData = {
                            nodes: new vis.DataSet(data.nodes),
                            edges: new vis.DataSet(data.edges)
                        };

                        const isDark = document.documentElement.classList.contains('dark');

                        const options = {
                            nodes: {
                                font: {
                                    color: isDark ? '#f8fafc' : '#0f172a',
                                    size: 14,
                                    face: 'Inter, system-ui, sans-serif'
                                },
                                shadow: true
                            },
                            edges: {
                                smooth: { type: 'continuous' }
                            },
                            physics: {
                                solver: 'forceAtlas2Based',
                                forceAtlas2Based: {
                                    gravitationalConstant: -35,
                                    centralGravity: 0.015,
                                    springLength: 100,
                                    springConstant: 0.08
                                }
                            },
                            interaction: {
                                hover: true,
                                zoomView: true
                            }
                        };

                        this.network = new vis.Network(container, graphData, options);

                        // Click node handler
                        this.network.on('click', function(params) {
                            if (params.nodes.length > 0) {
                                const nodeId = params.nodes[0];
                                if (nodeId.startsWith('note_')) {
                                    const rawId = nodeId.replace('note_', '');
                                    window.location.href = '/notes/' + rawId;
                                }
                            }
                        });
                    });
            }
        }"
        x-init="initGraph()"
        class="py-8 bg-gray-50/50 dark:bg-slate-950 min-h-screen transition-colors duration-200"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            
            <!-- Graph Container Box -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-xl overflow-hidden relative">
                
                <!-- Loading Spinner -->
                <div x-show="loading" class="absolute inset-0 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm flex items-center justify-center">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-indigo-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="font-semibold text-gray-700 dark:text-slate-300 text-sm">Building Knowledge Graph...</span>
                    </div>
                </div>

                <!-- Canvas Legend Header -->
                <div class="p-4 border-b border-gray-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50 dark:bg-slate-900/50">
                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                            <span class="text-gray-700 dark:text-slate-300">Notes (Click to open)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-gray-700 dark:text-slate-300">#Tags</span>
                        </div>
                    </div>

                    <button
                        @click="if (network) network.fit()"
                        type="button"
                        class="px-3 py-1 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition"
                    >
                        🔍 Reset Zoom & View
                    </button>
                </div>

                <!-- Graph Canvas Height 600px -->
                <div id="knowledge-graph-canvas" class="w-full h-[600px] bg-slate-900/5 dark:bg-slate-950"></div>
            </div>

        </div>
    </div>
</x-app-layout>
