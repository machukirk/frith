<?php

namespace App\Models;

use App\Support\PageContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    protected static function booted(): void
    {
        // Editors expect the site to change the moment they hit save.
        static::saved(fn (Page $page) => PageContent::forget($page->slug));
        static::deleted(fn (Page $page) => PageContent::forget($page->slug));
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
