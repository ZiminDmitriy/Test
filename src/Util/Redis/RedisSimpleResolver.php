<?php
declare(strict_types=1);

namespace App\Util\Redis;

use App\Exception\Redis\KeyDoesNotExistException;
use Redis;
use RuntimeException;

final class RedisSimpleResolver
{
    private Redis $redis;

    public function __construct()
    {
        $this->redis = new Redis();
    }

    public function set(string $key, string $value): void
    {
        $this->safelyConnect();

        if (!$this->redis->set($key, $value)) {
            throw new RuntimeException('Problem with Redis "set" function', 0, null);
        }
    }

    public function get(string $key): string
    {
        $this->safelyConnect();

        if (($value = $this->redis->get($key)) === false) {
            throw new KeyDoesNotExistException('Key does not exist. Maybe, you should execute "generateLeads.php" firstly', 0, null);
        }

        return $value;
    }

    private function safelyConnect(): void
    {
        if (!$this->redis->connect($_ENV['REDIS_HOST'], (int)$_ENV['REDIS_PORT'])) {
            throw new RuntimeException('Problem with Redis connection', 0, null);
        }
    }
}