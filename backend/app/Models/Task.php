<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\TaskCacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        $bumpVersion = static function (): void {
            app(TaskCacheService::class)->incrementTasksVersion();
        };

        static::created($bumpVersion);
        static::updated($bumpVersion);
        static::deleted($bumpVersion);
        static::restored($bumpVersion);
        static::forceDeleted($bumpVersion);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'assigned_to',
        'due_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'assigned_to' => 'integer',
            'due_date' => 'date',
        ];
    }

    /**
     * The user this task is assigned to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Assignee relationship.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
