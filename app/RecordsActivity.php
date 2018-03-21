<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 3/21/18
 * Time: 9:44 AM
 */

namespace App;


trait RecordsActivity
{
    protected static function bootRecordsActivity()
    {

        if (auth()->guest()) return;
        foreach (static::getRecordEvents() as $event){
            static::$event(function($thread) use ($event){
                $thread->recordActivity($event);
            });
        }
    }

    protected static function getRecordEvents()
    {
        return ['created'];
    }

    protected function recordActivity($event)
    {
        $this->activity()->create([
            'user_id'      => auth()->id(),
            'type'         => $this->getActivityType($event),
        ]);
    }

    public function activity()
    {
        return $this->morphMany('App\Activity', 'subject');
    }

    protected function getActivityType($event)
    {
        $type = strtolower((new \ReflectionClass($this))->getShortName());
        return "{$event}_{$type}";
    }
}