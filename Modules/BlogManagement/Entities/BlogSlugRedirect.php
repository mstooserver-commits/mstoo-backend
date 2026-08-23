<?php

namespace Modules\BlogManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogSlugRedirect extends Model
{
    use HasUuid;

    protected $fillable = ['blog_id', 'old_slug'];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
