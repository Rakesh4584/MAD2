<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Usamamuneerchaudhary\Commentify\Traits\Commentable;

class Product extends Model implements HasMedia
{
    use HasFactory,
        SoftDeletes,
        InteractsWithMedia,
        Commentable;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'quantity',
        'price',
        'status',
        'thumbnail',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->singleFile();

        $this->addMediaCollection('gallery');
    }

    public function getThumbnail()
    {
        if (str_starts_with($this->thumbnail, 'http')) {
            return $this->thumbnail;
        }

        return '/storage/' . $this->thumbnail;
    }

    /**
     * Register media conversions for a given media.
     *
     * @param Media|null $media The media object to register conversions for.
     * @return void
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('250x250')
            ->width(250)
            ->height(250);

        $this->addMediaConversion('500x500')
            ->width(500)
            ->height(500);
    }


}
