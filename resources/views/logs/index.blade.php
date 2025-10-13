<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer - Today's Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-file-alt mr-2"></i>
                    Log Viewer
                </h1>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-calendar-day mr-1"></i>
                        Showing logs for: {{ $today }}
                    </span>
                    <form method="GET" action="{{ route('logs.show') }}" class="flex items-center space-x-2">
                        <input type="date" name="date" value="{{ $today }}" 
                               class="border rounded px-3 py-1 text-sm">
                        <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                            <i class="fas fa-search mr-1"></i>View
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar - Log Files -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-files mr-2"></i>
                        Log Files
                    </h2>
                    <div class="space-y-2">
                        @foreach($logFiles as $file)
                        <div class="border rounded p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-sm text-gray-800">{{ $file['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $file['size'] }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $file['modified'] }}
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Main Content - Today's Logs -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="border-b p-4">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-list mr-2"></i>
                            Today's Logs ({{ count($todayLogs) }} entries)
                        </h2>
                    </div>

                    @if(count($todayLogs) > 0)
                    <div class="overflow-y-auto max-h-screen">
                        @foreach($todayLogs as $index => $log)
                        <div class="border-b p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center space-x-3">
                                    <span class="log-level-badge log-level-{{ strtolower($log['level']) }} 
                                                px-2 py-1 rounded text-xs font-medium">
                                        {{ $log['level'] }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $log['timestamp'] }}
                                    </span>
                                </div>
                                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">
                                    {{ $log['file'] }}
                                </span>
                            </div>
                            <div class="log-content text-sm text-gray-700 font-mono bg-gray-50 p-3 rounded">
                                {{ $log['line'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">No logs found for today</p>
                        <p class="text-gray-400 text-sm mt-2">Logs will appear here as they are generated</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .log-level-badge {
            color: white;
        }
        .log-level-debug { background-color: #6b7280; }
        .log-level-info { background-color: #3b82f6; }
        .log-level-notice { background-color: #10b981; }
        .log-level-warning { background-color: #f59e0b; }
        .log-level-error { background-color: #ef4444; }
        .log-level-critical { background-color: #dc2626; }
        .log-level-alert { background-color: #b91c1c; }
        .log-level-emergency { background-color: #7f1d1d; }
        .log-level-unknown { background-color: #6b7280; }
        
        .log-content {
            word-break: break-all;
            white-space: pre-wrap;
            font-size: 0.875rem;
            line-height: 1.25;
        }
    </style>
</body>
</html>