<?php
// Isolated regression checks; never connects to a live client database.
$loader = require $argv[1];
$loader->setPsr4('App\\', dirname(__DIR__).'/app');
$db = new Illuminate\Database\Capsule\Manager;
$db->addConnection(['driver'=>'sqlite', 'database'=>':memory:']);
$db->setAsGlobal();
$db->bootEloquent();
$db->schema()->create('application_status', function ($table) {
    $table->increments('id');
    $table->integer('job_id')->nullable();
    $table->string('status');
});
$db->table('application_status')->insert([
    ['id'=>7, 'job_id'=>null, 'status'=>'Applied'],
    ['id'=>8, 'job_id'=>42, 'status'=>'Private job stage'],
]);
$setting = new App\ApplicationSetting;
foreach ([null, '', 'invalid json'] as $value) {
    $setting->setRawAttributes(['mail_setting'=>$value]);
    if ($setting->mail_setting !== [7=>['name'=>'Applied', 'status'=>true]]) {
        throw new RuntimeException('Missing configuration must use workspace global stages');
    }
}
$saved = [7=>['name'=>'Applied', 'status'=>false]];
$setting->setRawAttributes(['mail_setting'=>json_encode($saved)]);
if ($setting->mail_setting !== $saved) { throw new RuntimeException('Saved disabled choice changed'); }
$setting->setRawAttributes(['mail_setting'=>'[]']);
if ($setting->mail_setting !== []) { throw new RuntimeException('Saved empty configuration changed'); }
$db->table('application_status')->delete();
$setting->setRawAttributes(['mail_setting'=>null]);
if ($setting->mail_setting !== []) { throw new RuntimeException('Workspace without stages must return an array'); }
echo "Application mail settings checks passed: 6\n";
