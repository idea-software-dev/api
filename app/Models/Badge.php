<?php

namespace App\Models;

use App\Badges\Bronze\BronzeBadgesInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Badge extends Model
{
    protected $table = 'badges';

    protected $fillable = [
        'enabled',
        'name',
        'description',
        'meta_description',
        'meta_title',
        'points',
        'category',
        'award',
        'url_handle',
        'dev_status',
        'is_event',
        'tags',
        'icon_url',
        'user_group_id',
        'attempts',
        'passes',
        'show_confidence_start',
        'show_confidence_end',
        'confidence_text_start',
        'confidence_text_end',
    ];

    protected $visible = [
        'enabled', 'name', 'description', 'tags', 'points', 'category', 'award', 'url_handle', 'dev_status', 'is_event', 'icon_url', 'redemptions',
    ];

    public function results()
    {
        return $this->hasMany(ResultStarted::class);
    }

    public function resultStarted()
    {
        return $this->hasMany(ResultStarted::class);
    }

    public function resultPassed()
    {
        return $this->hasMany(ResultPassed::class);
    }

    public function codes()
    {
        return $this->hasMany(BadgeCode::class);
    }

    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    /**
     * Get similar badges.
     */
    public function getSimilarBadgesAttribute()
    {
        // If silver then no similar badges
        if (2 == $this->award) {
            return [];
        }

        return cache()->remember("badge:{$this->id}:similar-badges", 60 * 24, function () {
            return self::where('award', $this->award)
                ->whereNotIn('id', [$this->id])
                ->where('enabled', 1)
                ->where('dev_status', 'live')
                ->where('is_event', 0)
                ->where('category', $this->category)
                ->get()
            ;
        });
    }

    public function getPassesAttribute()
    {
        return number_format($this->resultPassed()->where('passed', 1)->count());
    }

    /**
     * Get next badge in category.
     */
    public function getNextBadgeAttribute()
    {
        $badge = resolve(BronzeBadgesInterface::class);

        $category_badges = $badge->byCategory($this->category)->sortByDesc('id');
        // If silver then no similar badges
        if (2 == $this->award) {
            return [];
        }

        foreach ($category_badges as $badge) {
            if ($badge->id === $this->id) {
                $next_badge = $category_badges->where('id', '<', $badge->id)->first();
            }
        }
        if (!isset($next_badge)) {
            $next_badge = $category_badges->first();
        }

        return $next_badge;
    }

    /**
     * Get next badge in category.
     */
    public function getPreviousBadgeAttribute()
    {
        $badge = resolve(BronzeBadgesInterface::class);

        $category_badges = $badge->byCategory($this->category)->sortByDesc('id');

        // If silver then no similar badges
        if (2 == $this->award) {
            return [];
        }

        foreach ($category_badges as $badge) {
            if ($badge->id === $this->id) {
                $prev_badge = $category_badges->where('id', '>', $badge->id)->last();
            }
        }
        if (!isset($prev_badge)) {
            $prev_badge = $category_badges->last();
        }

        return $prev_badge;
    }

    /**
     * Checks preview url contains full url.
     *
     * @param mixed $value
     */
    public function getPreviewUrlAttribute($value)
    {
        if (null === $value || empty($value)) {
            return;
        }

        if (Str::contains($value, 'http')) {
            return $value;
        }

        return env('APP_URL').$value;
    }

    /**
     * Checks icon url contains full url.
     *
     * @param mixed $value
     */
    public function getIconUrlAttribute($value)
    {
        if (null === $value || empty($value)) {
            return;
        }

        if (Str::contains($value, 'http')) {
            return $value;
        }

        return env('APP_URL').$value;
    }

    /**
     * Checks completed url contains full url.
     *
     * @param mixed $value
     */
    public function getCompletedIconUrlAttribute($value)
    {
        if (null === $value || empty($value)) {
            return $this->icon_url;
        }

        if (Str::contains($value, 'http')) {
            return $value;
        }

        return env('APP_URL').$value;
    }

    /**
     * Sets a type of badge.
     *
     * @param mixed $value
     */
    public function getTypeAttribute($value)
    {
        if ($this->is_event) {
            return 'experience';
        }

        return 'digital';
    }

    /**
     * Get Redemption Count.
     */
    public function getRedemptionCountAttribute()
    {
        return $this->resultPassed()->count();
        // return Result::where('badge_id', $this->id)->where('passed', '1')->count();
    }

    /**
     * Has progress API implemented.
     */
    public function getHasProgressAttribute()
    {
        return cache()->remember("badge:{$this->id}:has-progress", 60 * 24, function () {
            return ResultStarted::where('badge_id', $this->id)->whereNotNull('progress')->limit(10)->count() > 3;
        });
    }

    public function getParentAttribute()
    {
        return self::find($this->parent_badge_id);
    }

    public function getAwardName()
    {
        $awards = [
            1 => 'bronze',
            2 => 'silver',
            3 => 'gold',
        ];

        return $awards[$this->award];
    }
}
