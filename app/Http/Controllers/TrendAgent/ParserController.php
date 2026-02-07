<?php

namespace App\Http\Controllers\TrendAgent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ParserController extends Controller
{
    /**
     * Запустить парсинг
     */
    public function start(Request $request)
    {
        $region = $request->input('region', 'spb');
        $type = $request->input('type', 'complexes');
        $limit = $request->input('limit', 100000);
        $details = $request->boolean('details', true);
        $saveRaw = $request->boolean('save_raw', true);
        
        // Проверяем, не запущен ли уже парсер
        if ($this->isParserRunning()) {
            return response()->json([
                'success' => false,
                'message' => 'Парсер уже запущен',
            ], 400);
        }
        
        // Формируем команду
        $command = 'cd ' . base_path() . ' && php artisan trendagent:parse';
        $command .= " --region={$region}";
        $command .= " --type={$type}";
        $command .= " --limit={$limit}";
        
        if ($details) {
            $command .= ' --details';
        }
        
        if ($saveRaw) {
            $command .= ' --save-raw';
        }
        
        // Лог файл
        $logFile = storage_path("logs/parser_{$region}_{$type}_" . date('Y-m-d_H-i-s') . ".log");
        $command .= " > {$logFile} 2>&1 & echo $!";
        
        // Запускаем в фоне
        $pid = exec($command);
        
        // Сохраняем PID и инфо
        Storage::put('parser_pid.txt', $pid);
        Storage::put('parser_log.txt', $logFile);
        Storage::put('parser_started_at.txt', now()->toIso8601String());
        
        return response()->json([
            'success' => true,
            'message' => 'Парсинг запущен',
            'pid' => $pid,
            'log_file' => basename($logFile),
        ]);
    }
    
    /**
     * Остановить парсинг
     */
    public function stop()
    {
        if (!Storage::exists('parser_pid.txt')) {
            return response()->json([
                'success' => false,
                'message' => 'Парсер не запущен',
            ], 400);
        }
        
        $pid = Storage::get('parser_pid.txt');
        
        // Пытаемся остановить процесс
        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /F /PID {$pid}");
        } else {
            exec("kill {$pid}");
        }
        
        Storage::delete(['parser_pid.txt', 'parser_log.txt', 'parser_started_at.txt']);
        
        return response()->json([
            'success' => true,
            'message' => 'Парсинг остановлен',
        ]);
    }
    
    /**
     * Получить статус парсера
     */
    public function status()
    {
        return response()->json($this->getParserStatus());
    }
    
    /**
     * Получить лог парсера
     */
    public function logs(Request $request)
    {
        $lines = $request->input('lines', 100);
        
        if (!Storage::exists('parser_log.txt')) {
            return response()->json([
                'success' => false,
                'message' => 'Лог файл не найден',
            ], 404);
        }
        
        $logFile = Storage::get('parser_log.txt');
        
        if (!file_exists($logFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Лог файл не найден',
            ], 404);
        }
        
        // Читаем последние N строк
        $content = file($logFile);
        $lastLines = array_slice($content, -$lines);
        
        return response()->json([
            'success' => true,
            'logs' => implode('', $lastLines),
            'total_lines' => count($content),
        ]);
    }
    
    /**
     * Получить статистику парсинга
     */
    public function statistics()
    {
        return response()->json($this->getStatistics());
    }
    
    /**
     * Проверить, запущен ли парсер
     */
    protected function isParserRunning(): bool
    {
        if (!Storage::exists('parser_pid.txt')) {
            return false;
        }
        
        $pid = Storage::get('parser_pid.txt');
        
        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq {$pid}\" /NH", $output);
            return count($output) > 0 && strpos($output[0], (string)$pid) !== false;
        } else {
            exec("ps -p {$pid}", $output);
            return count($output) > 1;
        }
    }
    
    /**
     * Получить статус парсера
     */
    protected function getParserStatus(): array
    {
        $isRunning = $this->isParserRunning();
        
        $status = [
            'running' => $isRunning,
            'pid' => null,
            'started_at' => null,
            'duration' => null,
        ];
        
        if ($isRunning && Storage::exists('parser_pid.txt')) {
            $status['pid'] = Storage::get('parser_pid.txt');
            
            if (Storage::exists('parser_started_at.txt')) {
                $startedAt = Storage::get('parser_started_at.txt');
                $status['started_at'] = $startedAt;
                $status['duration'] = now()->diffInSeconds($startedAt);
            }
        }
        
        return $status;
    }
    
    /**
     * Получить статистику парсинга
     */
    protected function getStatistics(): array
    {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'by_type' => [],
        ];
        
        $basePath = 'trendagent/parsing/spb/raw';
        
        if (!Storage::disk('local')->exists($basePath)) {
            return $stats;
        }
        
        $types = ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial'];
        
        foreach ($types as $type) {
            $path = "{$basePath}/{$type}";
            
            if (Storage::disk('local')->exists($path)) {
                $files = Storage::disk('local')->files($path);
                $count = count($files);
                $size = 0;
                
                foreach ($files as $file) {
                    $size += Storage::disk('local')->size($file);
                }
                
                $stats['by_type'][$type] = [
                    'files' => $count,
                    'size' => $size,
                    'size_mb' => round($size / 1024 / 1024, 2),
                ];
                
                $stats['total_files'] += $count;
                $stats['total_size'] += $size;
            }
        }
        
        $stats['total_size_mb'] = round($stats['total_size'] / 1024 / 1024, 2);
        
        return $stats;
    }
}
