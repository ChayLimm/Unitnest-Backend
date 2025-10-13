<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->format('Y-m-d');
        $logFiles = $this->getLogFiles();
        $todayLogs = $this->getTodayLogs();
        
        return view('logs.index', compact('todayLogs', 'today', 'logFiles'));
    }

    public function show($date = null)
    {
        $date = $date ?: Carbon::today()->format('Y-m-d');
        $logFiles = $this->getLogFiles();
        $todayLogs = $this->getTodayLogs($date);
        
        return view('logs.index', compact('todayLogs', 'today', 'logFiles'));
    }

    private function getLogFiles()
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        
        $logFiles = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                $logFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'modified' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s')
                ];
            }
        }
        
        return $logFiles;
    }

    private function getTodayLogs($date = null)
    {
        $date = $date ?: Carbon::today()->format('Y-m-d');
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        
        $todayLogs = [];
        $targetDate = Carbon::parse($date)->format('Y-m-d');
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                $content = File::get($file);
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        // Extract date from log line (assuming standard Laravel format)
                        if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                            $logDate = $matches[1];
                            if ($logDate === $targetDate) {
                                $todayLogs[] = [
                                    'file' => $file->getFilename(),
                                    'line' => $line,
                                    'timestamp' => $this->extractTimestamp($line),
                                    'level' => $this->extractLogLevel($line)
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        // Sort logs by timestamp
        usort($todayLogs, function($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });
        
        return $todayLogs;
    }

    private function extractTimestamp($line)
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function extractLogLevel($line)
    {
        $levels = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
        
        foreach ($levels as $level) {
            if (strpos($line, ".{$level}:") !== false) {
                return $level;
            }
        }
        
        return 'UNKNOWN';
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}