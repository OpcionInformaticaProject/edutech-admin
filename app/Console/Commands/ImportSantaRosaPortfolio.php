<?php

namespace App\Console\Commands;

use App\Models\Campus;
use App\Models\Import;
use App\Models\Organization;
use App\Models\User;
use App\Services\PortfolioSpreadsheetImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('edutech:import-cartera {file} {--confirm}')]
#[Description('Previsualiza o confirma una importación de cartera desde Excel')]
class ImportSantaRosaPortfolio extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PortfolioSpreadsheetImporter $importer): int
    {
        $path = realpath((string) $this->argument('file'));
        if (! $path) {
            $this->error('No se encontró el archivo.');

            return self::FAILURE;
        }
        $organization = Organization::where('name', 'EDUTECH')->firstOrFail();
        $campus = Campus::where('organization_id', $organization->id)->where('name', 'Santa Rosa')->firstOrFail();
        $hash = hash_file('sha256', $path);
        $import = Import::firstOrCreate(['organization_id' => $organization->id, 'file_hash' => $hash], ['campus_id' => $campus->id, 'uploaded_by' => User::where('organization_id', $organization->id)->where('role', 'superadmin')->value('id'), 'original_filename' => basename($path), 'stored_path' => $path, 'status' => 'previewed']);
        if ($import->rows()->doesntExist()) {
            $importer->preview($path, $import);
        }
        $this->table(['Métrica', 'Cantidad'], collect($import->fresh()->summary)->map(fn ($value, $key) => [$key, $value])->values());
        if ($this->option('confirm')) {
            $importer->confirm($import->fresh());
            $this->info('Importación confirmada.');
        } else {
            $this->warn('Previsualización únicamente. Use --confirm para persistir los registros.');
        }

        return self::SUCCESS;
    }
}
