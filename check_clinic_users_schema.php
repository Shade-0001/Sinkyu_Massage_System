<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$cols = DB::connection('sinkyu_massage_system_db')->select("DESCRIBE clinic_users");
foreach ($cols as $c) {
  echo "{$c->Field}: {$c->Type}, Null={$c->Null}, Default=" . ($c->Default ?? 'NULL') . "\n";
}
