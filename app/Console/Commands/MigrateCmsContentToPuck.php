<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use App\Services\CmsLayoutService;
use Illuminate\Console\Command;

class MigrateCmsContentToPuck extends Command
{
    protected $signature = 'cms:migrate-to-puck {--dry-run : Preview changes without saving}';

    protected $description = 'Migrate legacy HTML CMS content into Puck layout_json blocks';

    public function __construct(private CmsLayoutService $layoutService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $migrated = 0;
        $skipped = 0;

        CmsPage::query()
            ->where(function ($query) {
                $query->whereNull('layout_json')
                    ->orWhere('layout_json', '[]')
                    ->orWhere('layout_json', 'null')
                    ->orWhere('layout_json', '{"content":[],"root":{"props":{}}}');
            })
            ->chunkById(50, function ($pages) use ($dryRun, &$migrated, &$skipped) {
                foreach ($pages as $page) {
                    if ($this->layoutService->hasRenderableContent($page->layout_json)) {
                        $skipped++;

                        continue;
                    }

                    if (! $this->layoutService->needsLegacyMigration($page)) {
                        $skipped++;

                        continue;
                    }

                    $layout = $this->layoutService->buildFromLegacy($page);

                    if ($dryRun) {
                        $this->line("[dry-run] Would migrate page #{$page->id}: {$page->title}");
                        $migrated++;

                        continue;
                    }

                    $page->update([
                        'layout_json' => $layout,
                        'layout_version' => 'puck-1',
                    ]);

                    $this->info("Migrated page #{$page->id}: {$page->title}");
                    $migrated++;
                }
            });

        $this->newLine();
        $this->info("Done. Migrated: {$migrated}, Skipped: {$skipped}".($dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}
