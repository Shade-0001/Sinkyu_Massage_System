<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tables = DB::select('SHOW TABLES');
foreach($tables as $t) { $v = array_values((array)$t); if (strpos($v[0], 'consent') !== false) echo $v[0] . PHP_EOL; }
