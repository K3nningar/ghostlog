<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DestinyImportAllLocales extends Command
{
    protected $signature = 'destiny:import-all {--dispatch-icons} {--fresh}';
    protected $description = 'Importe les items Destiny 1 pour toutes les locales disponibles';

    protected array $expectedLocales = ['en', 'fr', 'de', 'es', 'it', 'ja', 'pt-br'];

    public function handle()
    {
        $this->info('== Récupération de la liste des locales disponibles (Destiny 1) ==');

        $response = Http::withHeaders([
            'X-API-Key' => config('services.bungie.api_key'),
        ])->get('https://www.bungie.net/Platform/Destiny/Manifest/');

        if (!$response->successful()) {
            $this->error('Impossible de récupérer le manifest Destiny 1.');
            return 1;
        }

        $paths = $response->json('Response.mobileWorldContentPaths');

        if (!$paths) {
            $this->error('Aucune locale trouvée dans le manifest.');
            return 1;
        }

        $locales = array_keys($paths);

        $this->info('Locales trouvées dans le manifest : ' . implode(', ', $locales));

        $missing = array_diff($this->expectedLocales, $locales);
        if (!empty($missing)) {
            $this->warn('Locales attendues mais absentes : ' . implode(', ', $missing));
        }

        $this->newLine();

        foreach ($locales as $locale) {
            $this->info("=== Import de la locale : {$locale} ===");

            $params = ['--locale' => $locale];

            if ($this->option('fresh')) {
                $params['--fresh'] = true;
            }

            if ($this->option('dispatch-icons')) {
                $params['--dispatch-icons'] = true;
            }

            $this->call('destiny:import', $params);

            $this->newLine();
        }

        $this->info('Import de toutes les locales Destiny 1 terminé !');

        return 0;
    }
}