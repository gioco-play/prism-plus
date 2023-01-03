<?php

namespace GiocoPlus\PrismPlus\Helper;

use Swoole\Coroutine\PostgreSQL;

class PgPool
{
    /**@var \Swoole\Coroutine\Channel */
    protected $pool;

    protected $dsn;

    /**
     * RedisPool constructor.
     * @param int $size max connections
     */
    public function __construct(string $dsn, int $size = 100)
    {
        $this->pool = new \Swoole\Coroutine\Channel($size);
        for ($i = 0; $i < $size; $i++) {
            $pg = new PostgreSQL();

            $res = $pg->connect($dsn);
            if ($res === false) {
                throw new \RuntimeException("failed to connect postgres. $dsn");
            } else {
                $this->dsn = $dsn;
                $this->put($pg);
            }
        }
    }

    public function get()
    {
        $db = $this->pool->pop();
        if ($this->check($db)) {
            return $db;
        }

        try {
            return $this->reconnect();
        }catch (\Throwable $th) {
            throw new \RuntimeException("failed to connect postgres.");
        }

        return null;
    }

    public function put(\Swoole\Coroutine\PostgreSQL $pg)
    {
        $this->pool->push($pg);
    }

    public function close(): void
    {
        $this->pool->close();
        $this->pool = null;
    }

    public function check($db) : bool
    {
        $res = $db->query("SELECT 1");
        try {
            if ($res !== false) {
                var_dump("success");
                return true;
            }
        }catch (\Throwable $th) {
            var_dump($res);
            var_dump($th->getMessage());
        }

        return false;
    }

    public function reconnect()
    {
        $pg = new PostgreSQL();
        $res = $pg->connect($this->dsn);
        if ($res === false) {
            throw new \RuntimeException("failed to connect postgres.");
        } else {
            return $pg;
        }
    }

    public function length() : int
    {
        return $this->pool->length();
    }
}