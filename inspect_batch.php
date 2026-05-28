<?php
$failed = DB::table('failed_jobs')->orderByDesc('id')->take(3)->get(['id','exception','failed_at']);
foreach ($failed as $f) {
    $ex = substr($f->exception, 0, 400);
    echo "ID:{$f->id} [{$f->failed_at}]" . PHP_EOL;
    echo $ex . PHP_EOL . "---" . PHP_EOL;
}