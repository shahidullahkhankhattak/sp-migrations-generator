<?php

namespace Shahid\SpMigrationsGenerator;

use Illuminate\Console\Command;
use DB;
use Schema;
class SpMigrationsInsertToMigrationTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spmigration:inserttables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sp Migration Insert Tables insert table migrations to migration table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function addMigrationsToDB()
    {
        $target_path = base_path() . "/database/migrations/";
        $files = glob($target_path . "*.php");
        $hasTable = Schema::hasTable('migrations');
        if(!$hasTable){
            Schema::create('migrations', function (Blueprint $table) {
                $table->string('migration');
                $table->integer('batch');
            });
        }
        $changed = false;
        foreach ($files as $filename) {
            $matches = [];
            preg_match('/(\w+)(.php)/', $filename, $matches);
            if($matches && count($matches) >= 1){
                $filename = $matches[1];
                $record = DB::table('migrations')->select('migration')->where('migration', $filename)->first();
                if(!$record){
                    DB::table('migrations')->insert([
                        'migration' => $filename,
                        'batch' => 0
                    ]);
                    $changed = true;
                    $this->info("$filename inserted into migration table");
                }
            }
        }
        if(!$changed){
            $this->info('no changes made to migration table');
        }
    }

    public function handle()
    {
        $this->addMigrationsToDB();
    }
}