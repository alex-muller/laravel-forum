<?php

namespace App;

use Illuminate\Support\Facades\Redis;

class Visits
{
    private $thread;

    public function __construct($thread)
    {
        $this->thread = $thread;
    }

    public function record()
    {
        Redis::incr("threads.{$this->thread->id}.visits");
    }

    public function reset()
    {
        Redis::del($this->cacheKey());
    }

    public function count()
    {
        return Redis::get($this->cacheKey()) ?: 0;
    }

    protected function cacheKey()
    {
        return "threads.{$this->thread->id}.visits";
    }
}