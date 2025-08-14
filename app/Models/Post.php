<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Post extends Model
{
    use InteractsWithMedia;
    
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'price',
        'is_paid',
        'visibility',
    ];
            
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

        /**
     * Define your media collections
     */
    public function registerMediaCollections(): void
    {
        // One cover image; new upload replaces the old automatically
        $this->addMediaCollection('cover')->singleFile();

        // Multiple attachments (images, pdfs, etc.)
        $this->addMediaCollection('attachments');
    }

}
