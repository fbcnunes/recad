<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\RecadSepladFuncImporter;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recad:import-seplad-func {--path=} {--dry-run} {--limit=}', function () {
    $path = (string) ($this->option('path') ?: base_path('RecadSeplad-Func.txt'));
    $dryRun = (bool) $this->option('dry-run');
    $limitOpt = $this->option('limit');
    $limit = $limitOpt !== null ? (int) $limitOpt : null;

    $this->info('Import RecadSeplad-Func');
    $this->line('path: ' . $path);
    $this->line('dry-run: ' . ($dryRun ? 'yes' : 'no'));
    $this->line('limit: ' . ($limit ?? 'none'));

    $importer = new RecadSepladFuncImporter();
    $summary = $importer->import($path, $dryRun, $limit, function (string $msg): void {
        $this->warn($msg);
    });

    $this->info('Summary:');
    foreach ($summary as $k => $v) {
        $this->line("{$k}: {$v}");
    }
})->purpose('Importa RecadSeplad-Func.txt (TSV) para o banco, zerando campos conflitantes.');
