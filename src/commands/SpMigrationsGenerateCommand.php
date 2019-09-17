<?php

namespace Shahid\SpMigrationsGenerator;

use Illuminate\Console\Command;
use DB;
class SpMigrationsGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spmigration:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sp Migration Generator generates migrations from sps from sql';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getAbsolutePath(){
        $path = $this->argument('path');
        $value = explode('=', $path);
        if(count($value) > 1){
            return $value[1];
        }
        $this->info('Absolute path of file is required');
        return;
    }

    function dashesToCamelCase($string, $capitalizeFirstCharacter = false) 
    {
        $string = strtolower($string);
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }

    public function generateMigrationFromContent($contents, $type = 'sp'){
        $prepared = '';
        $prepared = preg_replace('/DELIMITER $$/', '', $contents);
        $prepared = preg_replace('/\$\$/', ';', $prepared);

        $prepared = preg_replace('/USE (.+)\n/', '', $prepared);
        $prepared = preg_replace('/DELIMITER\s*;/', '', $prepared);
        $prepared = preg_replace('/DEFINER=[`a-zA-z@%]+/', '', $prepared);
        $matches = [];
        preg_match('/(CREATE (.*) (PROCEDURE|FUNCTION))(\s*\`*)(\w+)(\s*\`*)/', $prepared, $matches);
        $drop_type = ($type == 'sp' ? 'PROCEDURE' : 'FUNCTION');
        $prepared = "DROP $drop_type IF EXISTS `".$matches[5]."`;\n" . $prepared;
        // $prepared = addslashes($prepared);
        $prepared = '$sp = <<<SQL
        '. $prepared .'
SQL;
        DB::unprepared($sp);';
        $sp_name = date('Y_m_d') . '_create_' . $matches[5] . ".php";
        $this->info('generating migration '. $sp_name);
        $class_name = $this->dashesToCamelCase($matches[5], true);
        $migration_file = 
"<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class $class_name extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prepared
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP $drop_type IF EXISTS `".$matches[5]."`');
    }
}
";
        $target_path = base_path() . "/database/migrations";
        $type_path = '/sps';
        if($type == 'fn'){
            $type_path = '/fns';
        }
        $this->info('migration generated '. $sp_name);
        if (!file_exists($target_path.$type_path)) {
            mkdir($target_path.$type_path);
        }
        file_put_contents($target_path.$type_path.'/'.$sp_name, $migration_file);
    }

    public function generateMigrationFromFile($path){
        $contents = file_get_contents($path);
        $prepared = '';
        $prepared = preg_replace('/DELIMITER $$/', '', $contents);
        $prepared = preg_replace('/\$\$/', ';', $prepared);

        $prepared = preg_replace('/USE (.+)\n/', '', $prepared);
        $prepared = preg_replace('/DELIMITER\s*;/', '', $prepared);
        $prepared = preg_replace('/DEFINER=[`a-zA-z@%]+/', '', $prepared);
        $prepared = preg_replace('/\"/','\\"', $prepared);
        $prepared = '$sp = "' . $prepared . '";
                     DB::unprepared($sp);';
        $matches = [];
        preg_match('/(CREATE (.*) PROCEDURE)(\s*\`*)(\w+)(\s*\`*)/', $prepared, $matches);
        $sp_name = date('Y_m_d') . '_create_' . $matches[4] . ".php";
        $this->info('generating migration '. $sp_name);
        $class_name = $this->dashesToCamelCase($matches[4], true);
        $migration_file = 
"<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class $class_name extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prepared
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS `".$matches[4]."`');
    }
}
";
        $target_path = base_path() . "/database/migrations";
        $this->info('migration generated '. $sp_name);
        if (!file_exists($target_path.'/sps')) {
            mkdir($target_path.'/sps');
        }
        file_put_contents($target_path.'/sps/'.$sp_name, $migration_file);
        return $this->info("migrations generated successfully");
    }

    public function generateSps(){
        $path = $this->getAbsolutePath();
        if(strpos($path, '.sql')){
            if(file_exists($path)){
                return $this->generateMigrationFromFile($path);
            }
            return $this->error('File ' . $path . ' does not exists');
        }
        if(file_exists($path)){
            $files = glob($path . "/*.sql");
            foreach($files as $file){
                return $this->generateMigrationFromFile($file);
            }
            return $this->info("migrations generated successfully");
        }
        return $this->error('Path ' . $path . ' does not exist.');
    }

    public function generateSpsFromDB(){
        $dbname = env('DB_DATABASE');
        $procedures = DB::select("SELECT SPECIFIC_NAME 
                            FROM information_schema.routines 
                            WHERE routine_type = 'PROCEDURE'
                            AND ROUTINE_SCHEMA = '$dbname'");
        $functions = DB::select("SELECT SPECIFIC_NAME 
                            FROM information_schema.routines 
                            WHERE routine_type = 'FUNCTION'
                            AND ROUTINE_SCHEMA = '$dbname'");
        foreach($procedures as $procedure){
            $proc_name = $procedure->SPECIFIC_NAME;
            $definition = json_decode(json_encode(DB::select('SHOW CREATE PROCEDURE '.$proc_name)), true);
            $this->generateMigrationFromContent($definition[0]['Create Procedure'], 'sp');
        }
        foreach($functions as $function){
            $fn_name = $function->SPECIFIC_NAME;
            $definition = json_decode(json_encode(DB::select('SHOW CREATE FUNCTION '.$fn_name)), true);
            $this->generateMigrationFromContent($definition[0]['Create Function'], 'fn');
        }
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->generateSpsFromDB();
    }
}