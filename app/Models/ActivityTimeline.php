<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityTimeline extends Model
{
    protected $table = 'activity_timeline';

    protected $fillable = [
        'company_id', 'user_id', 'subject_type', 'subject_id',
        'action', 'description', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function subject(): MorphTo { return $this->morphTo(); }

    public static function log(Model $subject, string $action, ?string $description = null, ?array $metadata = null): static
    {
        $companyId = $subject->company_id ?? Auth::user()?->company_id;

        return static::create([
            'company_id' => $companyId,
            'user_id' => Auth::id(),
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public static function forSubject(string $type, int $id, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('subject_type', $type)
            ->where('subject_id', $id)
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
