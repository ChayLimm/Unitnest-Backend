<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LogController extends Controller
{
    public function index()
    {
        $logFile = storage_path('logs/laravel.log');
        $logEntries = [];
        $stats = [
            'total' => 0,
            'errors' => 0,
            'warnings' => 0,
            'others' => 0
        ];

        if (File::exists($logFile)) {
            $logContent = File::get($logFile);
            $lines = explode("\n", $logContent);
            
            // Reverse to get latest first, then take the last 200 lines
            $lines = array_reverse($lines);
            $lines = array_slice($lines, 0, 200);
            $lines = array_reverse($lines);

            $currentEntry = '';
            
            foreach ($lines as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})].*?\.(\w+):/', $line, $matches)) {
                    // If we have a previous entry, process it
                    if (!empty($currentEntry)) {
                        $logEntries[] = $this->parseLogEntry($currentEntry);
                        $currentEntry = '';
                    }
                }
                $currentEntry .= $line . "\n";
            }
            
            // Don't forget the last entry
            if (!empty($currentEntry)) {
                $logEntries[] = $this->parseLogEntry($currentEntry);
            }

            // Reverse to show latest first
            $logEntries = array_reverse($logEntries);

            // Calculate stats
            foreach ($logEntries as $entry) {
                $stats['total']++;
                switch ($entry['level']) {
                    case 'error':
                        $stats['errors']++;
                        break;
                    case 'warning':
                        $stats['warnings']++;
                        break;
                    default:
                        $stats['others']++;
                        break;
                }
            }
        }

        return view('logs.index', compact('logEntries', 'stats'));
    }

    private function parseLogEntry($entry)
    {
        $timestamp = '';
        $level = 'info';
        $content = $entry;

        // Extract timestamp and level
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})].*?\.(\w+):/', $entry, $matches)) {
            $timestamp = $matches[1];
            $level = strtolower($matches[2]);
            $content = preg_replace('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}].*?\.\w+:/', '', $entry);
            $content = trim($content);
        }

        // Highlight errors and warnings
        $highlightedContent = $this->highlightLogContent($content, $level);

        return [
            'timestamp' => $timestamp,
            'level' => $level,
            'content' => $content,
            'highlighted_content' => $highlightedContent,
            'level_class' => 'log-' . $level
        ];
    }

    private function highlightLogContent($content, $level)
    {
        // Convert to HTML entities for safety
        $content = htmlspecialchars($content);

        // Highlight specific patterns
        $patterns = [
            '/Stack trace:/' => '<span class="text-red-600 font-bold">Stack trace:</span>',
            '/Exception:/' => '<span class="text-red-600 font-bold">Exception:</span>',
            '/Error:/' => '<span class="text-red-600 font-bold">Error:</span>',
            '/Warning:/' => '<span class="text-yellow-600 font-bold">Warning:</span>',
            '/Notice:/' => '<span class="text-blue-600 font-bold">Notice:</span>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Highlight file paths
        $content = preg_replace(
            '/(\/[a-zA-Z0-9_\-\.\/]+\.php)(:\d+)/',
            '<span class="text-purple-600 font-mono">$1</span><span class="text-green-600 font-mono">$2</span>',
            $content
        );

        // Highlight URLs
        $content = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<span class="text-blue-500 underline">$1</span>',
            $content
        );

        return nl2br($content);
    }

    public function clearLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return response()->json(['success' => true]);
    }
}