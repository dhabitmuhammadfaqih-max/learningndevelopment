<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu notifikasi in-app milik satu user (lihat migration
 * create_notifications_table untuk perbedaannya dengan FcmNotificationLog).
 *
 * @property int         $user_id
 * @property string      $title
 * @property string      $body
 * @property string|null $url
 * @property \Carbon\Carbon|null $read_at
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
