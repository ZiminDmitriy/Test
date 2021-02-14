<?php
declare(strict_types=1);

require dirname(__DIR__).'/../vendor/autoload.php';

use App\Handler\Command\ProcessLeads\Handler;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Dotenv\Dotenv;
use App\Exception\Redis\AbstractRedisException;

(Dotenv::createImmutable(__DIR__.'/../../'))->load();

if (!file_exists(Handler::LOG_FILE_PATH)) {
    mkdir(Handler::LOG_FILE_PATH, 0777);
}

recursiveHandle();

function recursiveHandle(): void
{
    try {
        (new Handler())->handle();
    } catch (AMQPExceptionInterface | AbstractRedisException | RuntimeException $exception) {
        echo $exception->getMessage(). "\r\n";
    } catch (Throwable $throwable) {
        echo $throwable->getMessage();
        echo $throwable->getTraceAsString();

        recursiveHandle();
    }
}