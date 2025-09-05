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
        'media_type',
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
        $this->addMediaCollection('images')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->singleFile()
            ->withResponsiveImages();

        // Allow multiple MP4s per post (public disk by default)
        $this->addMediaCollection('videos')
            ->useDisk('public')	
            ->acceptsMimeTypes(['video/mp4', 'video/quicktime'])
            ->singleFile();
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->sharpen(10);
    }

    // Clean Blade usage: $post->image_url / $post->image_thumb_url
    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('image') ?: null;
    }

    public function getImageThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('image', 'thumb') ?: $this->image_url;
    }

}
