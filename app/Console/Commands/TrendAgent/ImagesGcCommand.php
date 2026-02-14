<?php

namespace App\Console\Commands\TrendAgent;

use App\Models\TrendAgent\TrendAgentImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImagesGcCommand extends Command
{
    protected $signature = 'trendagent:images:gc
                            {--days=30 : Удалять файлы, не использовавшиеся N дней}
                            {--dry-run : Показать, что будет удалено, без удаления}';

    protected $description = 'GC изображений TrendAgent: удаление локальных файлов, не использовавшихся N дней';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        if ($dryRun) {
            $this->warn("⚠️  Dry-run: файлы не будут удалены");
        }

        $query = TrendAgentImage::whereNotNull('local_path')
            ->where(function ($q) use ($cutoff) {
                $q->where('downloaded_at', '<', $cutoff)
                    ->orWhereNull('downloaded_at');
            });

        $images = $query->get();
        $toDelete = [];
        $disk = Storage::disk('public');

        foreach ($images as $img) {
            if ($img->local_path && $disk->exists($img->local_path)) {
                $toDelete[] = $img;
            }
        }

        $this->info("Найдено изображений для удаления: " . count($toDelete) . " (не использовались {$days}+ дней)");

        if ($dryRun) {
            foreach (array_slice($toDelete, 0, 10) as $img) {
                $this->line("  - {$img->local_path}");
            }
            if (count($toDelete) > 10) {
                $this->line("  ... и ещё " . (count($toDelete) - 10));
            }
            return 0;
        }

        $deleted = 0;
        foreach ($toDelete as $img) {
            if ($disk->delete($img->local_path)) {
                $img->update(['local_path' => null, 'download_status' => 'none']);
                $deleted++;
            }
        }

        $this->info("Удалено файлов: {$deleted}");
        return 0;
    }
}
