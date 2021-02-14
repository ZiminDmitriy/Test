<?php
declare(strict_types=1);

require dirname(__DIR__).'/../vendor/autoload.php';

use App\Handler\Command\GenerateLeads\Handler;
use Dotenv\Dotenv;

(Dotenv::createImmutable(__DIR__.'/../../'))->load();

try {
    (new Handler())->handle();
} catch (Throwable $throwable) {
    echo $throwable->getMessage();
    echo $throwable->getTraceAsString();
}

echo "Done. \r\n";
