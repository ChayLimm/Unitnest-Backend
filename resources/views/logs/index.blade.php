<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Log Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-entry {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .log-entry:hover {
            background-color: #f8fafc;
        }
        .log-error {
            border-left-color: #ef4444;
            background-color: #fef2f2;
        }
        .log-warning {
            border-left-color: #f59e0b;
            background-color: #fffbeb;
        }
        .log-info {
            border-left-color: #3b82f6;
            background-color: #eff6ff;
        }
        .log-debug {
            border-left-color: #6b7280;
            background-color: #f9fafb;
        }
        .copy-btn {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .copy-btn:hover {
            opacity: 1;
        }
        .timestamp {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Header -->
            <div class="bg-gray-800 text-white px-6 py-4">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold">
                        <i class="fas fa-file-alt mr-2"></i>
                        Laravel Log Viewer
                    </h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm bg-gray-700 px-3 py-1 rounded-full">
                            <i class="fas fa-file mr-1"></i>
                            storage/logs/laravel.log
                        </span>
                        <button onclick="refreshLogs()" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                        <button onclick="clearLogs()" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-trash mr-2"></i>Clear Logs
                        </button>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-700 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
                        <div class="text-gray-300 text-sm">Total Entries</div>
                    </div>
                    <div class="bg-red-500 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold">{{ $stats['errors'] }}</div>
                        <div class="text-gray-100 text-sm">Errors</div>
                    </div>
                    <div class="bg-yellow-500 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold">{{ $stats['warnings'] }}</div>
                        <div class="text-gray-100 text-sm">Warnings</div>
                    </div>
                    <div class="bg-blue-500 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold">{{ $stats['others'] }}</div>
                        <div class="text-gray-100 text-sm">Others</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-100 px-6 py-4 border-b">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium">Filter:</span>
                        <select id="levelFilter" onchange="filterLogs()" class="border rounded px-3 py-1 text-sm">
                            <option value="all">All Levels</option>
                            <option value="error">Errors Only</option>
                            <option value="warning">Warnings Only</option>
                            <option value="info">Info Only</option>
                            <option value="debug">Debug Only</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium">Search:</span>
                        <input type="text" id="searchInput" onkeyup="searchLogs()" placeholder="Search in logs..." 
                               class="border rounded px-3 py-1 text-sm w-64">
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium">Entries:</span>
                        <select id="limitFilter" onchange="filterLogs()" class="border rounded px-3 py-1 text-sm">
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Log Entries -->
            <div class="max-h-screen overflow-y-auto">
                @if(empty($logEntries))
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-info-circle text-4xl mb-4"></i>
                        <p class="text-lg">No log entries found or log file is empty.</p>
                    </div>
                @else
                    @foreach($logEntries as $index => $entry)
                        <div class="log-entry border-b border-gray-200 px-6 py-4 
                                    {{ $entry['level_class'] }} 
                                    {{ $entry['level'] == 'error' || $entry['level'] == 'warning' ? 'font-medium' : '' }}"
                             data-level="{{ $entry['level'] }}"
                             data-content="{{ strtolower($entry['content']) }}">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center space-x-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $entry['level'] == 'error' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $entry['level'] == 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $entry['level'] == 'info' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $entry['level'] == 'debug' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        <i class="fas 
                                            {{ $entry['level'] == 'error' ? 'fa-exclamation-circle' : '' }}
                                            {{ $entry['level'] == 'warning' ? 'fa-exclamation-triangle' : '' }}
                                            {{ $entry['level'] == 'info' ? 'fa-info-circle' : '' }}
                                            {{ $entry['level'] == 'debug' ? 'fa-bug' : '' }}
                                            mr-1"></i>
                                        {{ strtoupper($entry['level']) }}
                                    </span>
                                    <span class="timestamp text-gray-600 text-sm">{{ $entry['timestamp'] }}</span>
                                </div>
                                <button onclick="copyToClipboard('{{ $index }}')" 
                                        class="copy-btn text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="log-content text-sm text-gray-800 whitespace-pre-wrap font-mono">
                                {!! $entry['highlighted_content'] !!}
                            </div>
                            <div id="copy-target-{{ $index }}" class="hidden">
[{{ strtoupper($entry['level']) }}] {{ $entry['timestamp'] }}
{{ $entry['content'] }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <script>
        function refreshLogs() {
            window.location.reload();
        }

        function clearLogs() {
            if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                fetch('{{ route("logs.clear") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(response => {
                    if (response.ok) {
                        refreshLogs();
                    }
                });
            }
        }

        function filterLogs() {
            const levelFilter = document.getElementById('levelFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const logEntries = document.querySelectorAll('.log-entry');
            
            logEntries.forEach(entry => {
                const level = entry.getAttribute('data-level');
                const content = entry.getAttribute('data-content');
                const matchesLevel = levelFilter === 'all' || level === levelFilter;
                const matchesSearch = content.includes(searchTerm);
                
                entry.style.display = matchesLevel && matchesSearch ? 'block' : 'none';
            });
        }

        function searchLogs() {
            filterLogs();
        }

        function copyToClipboard(index) {
            const copyText = document.getElementById('copy-target-' + index).textContent;
            navigator.clipboard.writeText(copyText).then(() => {
                // Show temporary feedback
                const btn = event.target.closest('button');
                const originalIcon = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                setTimeout(() => {
                    btn.innerHTML = originalIcon;
                }, 2000);
            });
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshLogs, 30000);

        // Initialize filters from URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const levelFilter = urlParams.get('level');
            const limitFilter = urlParams.get('limit');
            
            if (levelFilter) {
                document.getElementById('levelFilter').value = levelFilter;
            }
            if (limitFilter) {
                document.getElementById('limitFilter').value = limitFilter;
            }
            
            filterLogs();
        });
    </script>
</body>
</html>