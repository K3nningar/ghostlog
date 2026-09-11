<?php

// app/Services/ManifestParserService.php
class ManifestParserService
{
    public function __construct(
        protected ItemCategorizerService $categorizer
    ) {}

    public function parse(string $sqlitePath, ManifestVersion $version): void
    {
        $pdo = new PDO("sqlite:{$sqlitePath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Table type: DestinyInventoryItemDefinition (D1)
        $stmt = $pdo->query("SELECT json FROM DestinyInventoryItemDefinition");

        $count = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = json_decode($row['json'], true);

            ParseInventoryItemJob::dispatch($data, $version->id)
                ->onQueue('parsing');

            $count++;
        }

        Log::info("Manifest parsing: {$count} items dispatchés en queue.");
    }
}