<?php

namespace App;

use App\Events\ThreadReceivedNewReply;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Thread extends Model
{
    use RecordsActivity;

    protected $guarded = [];

    protected $with = ['creator', 'channel'];

    protected $appends = ['isSubscribedTo'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($thread) {
            $thread->replies->each->delete();
        });
    }

    public function path()
    {
        return "/threads/{$this->channel->slug}/{$this->slug}";
    }

    public function replies()
    {
        return $this->hasMany(Reply::class)->withCount('favorites');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * @param array $reply
     *
     * @return Reply
     */
    public function addReply($reply)
    {
        $reply = $this->replies()->create($reply);

        event(new ThreadReceivedNewReply($reply));

        return $reply;
    }

    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }

    public function subscribe($userId = null)
    {
        $this->subscriptions()->create([
            'user_id' => $userId ?: auth()->id()
        ]);

        return $this;
    }

    public function unsubscribe($userId = null)
    {
        $this->subscriptions()
             ->where('user_id', $userId ?: auth()->id())
             ->delete();
    }

    public function subscriptions()
    {
        return $this->hasMany(ThreadSubscriptions::class);
    }

    public function getIsSubscribedToAttribute()
    {
        return $this->subscriptions()
                    ->where('user_id', auth()->id())
                    ->exists();
    }

    /**
     * Determine if the thread has been updated since the user last read it
     */

    public function hasUpdatesFor($user)
    {
        $key = $user->visitedThreadCacheKey($this);

        return $this->updated_at > cache($key);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function setSlugAttribute($value)
    {
//        if(static::whereSlug($slug = str_slug($value))->exists()){
//            $slug = $this->incrementSlug($slug);
//        }

        $slug = $this->incrementSlug($value);

        $this->attributes['slug'] = $slug;
    }

    public function incrementSlug($slug)
    {
        $slug = str_slug($slug);

        $threads = static::where('slug', 'like', $slug . '%')->get();

        $i = 1;

        $incrementSlug = $slug;

        while($threads->contains('slug', $incrementSlug))
        {
            $i++;
            $incrementSlug = $slug . '-' . $i;
        }

        return $incrementSlug;

//        $max = static::whereTitle($this->title)->latest('id')->value('slug');
//
//        if (is_numeric($max[-1])){
//            return preg_replace_callback('/(\d+)$/', function ($matches){
//                return $matches[1] + 1;
//            }, $max);
//        }
//
//        return "{$slug}-2";
    }

}
