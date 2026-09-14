<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'yandex_url',
        'yandex_id',
        'name',
        'rating',
        'ratings_count',
        'reviews_count',
        'last_parsed_at',
        'parse_status',
        'parse_progress',
        'parse_error',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'last_parsed_at' => 'datetime',
        'parse_progress' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
