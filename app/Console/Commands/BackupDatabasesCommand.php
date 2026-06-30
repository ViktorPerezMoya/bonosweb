<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use ZipArchive;
use Symfony\Component\Process\Process;

class BackupDatabasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:databases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realiza un backup completo de la DB central y las DBs de tenants, lo comprime y sube a Google Cloud Storage.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de backup SQL...');
        Log::info('[BackupSQL] Iniciando backup de bases de datos.');

        // 1. Obtener lista de bases de datos a respaldar
        $databases = [];
        
        // Base de datos central
        $centralDb = env('DB_DATABASE');
        if ($centralDb) {
            $databases[] = $centralDb;
        }

        // Bases de datos de tenants
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            if ($tenant->tenancy_db_name && !in_array($tenant->tenancy_db_name, $databases)) {
                $databases[] = $tenant->tenancy_db_name;
            }
        }

        if (empty($databases)) {
            $this->warn('No se encontraron bases de datos para respaldar.');
            return;
        }

        // 2. Preparar directorio temporal
        $tempDir = storage_path('app/private/temp_backups_' . time());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbHost = env('DB_HOST', '127.0.0.1');

        $errors = 0;
        $dateStr = date('Y_m_d_His');
        $gcsDisk = Storage::disk('gcs_sql_backups');

        // 3. Volcar, comprimir y subir cada base de datos
        foreach ($databases as $dbName) {
            $this->info("Procesando base de datos: {$dbName}...");
            $sqlFile = "{$tempDir}/{$dbName}.sql";
            $zipName = "{$dbName}_backup_{$dateStr}.zip";
            $zipPath = "{$tempDir}/{$zipName}";

            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $dumpCommand = [
                'mysqldump',
                '-h', $dbHost,
                '-u', $dbUser,
                '--column-statistics=0',
            ];

            if (!empty($dbPass)) {
                $dumpCommand[] = '-p' . $dbPass;
            }
            $dumpCommand[] = $dbName;
            
            $process = new Process($dumpCommand);
            $process->setTimeout(3600); // 1 hora de timeout

            try {
                $process->run();

                if ($process->isSuccessful()) {
                    file_put_contents($sqlFile, $process->getOutput());
                    $this->info("✅ {$dbName} exportada (SQL).");

                    // Comprimir en ZIP individual
                    $zip = new ZipArchive();
                    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                        $zip->addFile($sqlFile, basename($sqlFile));
                        $zip->close();
                        $this->info("✅ {$dbName} comprimida ({$zipName}).");

                        // Subir a GCS
                        $stream = fopen($zipPath, 'r');
                        if ($gcsDisk->put($zipName, $stream)) {
                            $this->info("✅ {$zipName} subido a GCS.");
                            Log::info("[BackupSQL] Backup subido a GCS: {$zipName}");
                        } else {
                            throw new \Exception("El disco retornó false al subir el archivo {$zipName}.");
                        }
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    } else {
                        throw new \Exception("Error al crear el archivo ZIP para {$dbName}.");
                    }
                } else {
                    $errors++;
                    $errorOutput = $process->getErrorOutput();
                    $this->error("❌ Error exportando {$dbName}: {$errorOutput}");
                    Log::error("[BackupSQL] Error exportando {$dbName}", ['error' => $errorOutput]);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Excepción procesando {$dbName}: {$e->getMessage()}");
                Log::error("[BackupSQL] Excepción procesando {$dbName}", ['error' => $e->getMessage()]);
            }

            // Limpiar archivos de esta iteración para no acumular espacio
            if (file_exists($sqlFile)) @unlink($sqlFile);
            if (file_exists($zipPath)) @unlink($zipPath);
        }

        // 4. Limpiar temporales
        $this->cleanup($tempDir);

        if ($errors > 0) {
            $this->warn("El proceso finalizó con {$errors} errores parciales.");
        } else {
            $this->info('¡Backup completado de manera exitosa!');
        }
    }

    /**
     * Limpia el directorio temporal y sus archivos.
     */
    protected function cleanup(string $dir)
    {
        $this->info("Limpiando archivos temporales...");
        if (!file_exists($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
