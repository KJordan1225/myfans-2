<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Post extends Model implements HasMedia
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

        // gallery (multiple images)
        $this->addMediaCollection('images')->useDisk('public');

        // Allow multiple MP4s per post (public disk by default)
        $this->addMediaCollection('videos')
            ->useDisk('public')	
            ->acceptsMimeTypes(['video/mp4', 'video/quicktime']); // adjust if needed
    }
}
