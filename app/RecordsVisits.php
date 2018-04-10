<?php

namespace App;

use Illuminate\Support\Facades\Redis;

trait RecordsVisits
{

    public function recordVisit()
    {
        Redis::incr("threads.{$this->id}.visits");

        return $this;
    }

    public function visits()
    {
        return Redis::get($this->visitCacheKey()) ?: 0;

    }

    public function resetVisits()
    {
        Redis::del($this->visitCacheKey());
    }

    protected function visitCacheKey()
    {
        return "threads.{$this->id}.visits";
    }
}