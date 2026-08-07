<?php

namespace App\Console\Commands;

use App\Models\NewsFeed;
use App\Services\News\RssFeedImporter;
use Illuminate\Console\Command;
use Throwable;

class RefreshNewsFeedsCommand extends Command
{
    protected $signature = 'news:refresh-feeds';

    protected $description = 'Refresh all active RSS and Atom news feeds';

    public function handle(RssFeedImporter $importer): int
    {
        $imported = 0;
        $updated = 0;
        $failed = 0;

        NewsFeed::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->each(function (NewsFeed $feed) use ($importer, &$imported, &$updated, &$failed): void {
                try {
                    $result = $importer->import($feed);
                    $imported += $result['imported'];
                    $updated += $result['updated'];
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                    $this->error("{$feed->name}: {$exception->getMessage()}");
                }
            });

        $this->info("News feeds refreshed: {$imported} imported, {$updated} updated, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
