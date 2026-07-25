<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorialProgress extends Model
{
    protected $table = 'tutorial_progress';

    protected $fillable = ['user_id', 'tutorial_key', 'current_step', 'completed', 'completed_at'];

    protected function casts(): array
    {
        return ['completed' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function advance(int $userId, string $key, int $totalSteps): static
    {
        $progress = static::firstOrCreate(
            ['user_id' => $userId, 'tutorial_key' => $key],
            ['current_step' => 0],
        );

        if (!$progress->completed) {
            $progress->current_step = min($progress->current_step + 1, $totalSteps);
            if ($progress->current_step >= $totalSteps) {
                $progress->completed = true;
                $progress->completed_at = now();
                User::withoutGlobalScopes()->where('id', $userId)->increment('tutorial_score', 10);
            }
            $progress->save();
        }

        return $progress;
    }
}
