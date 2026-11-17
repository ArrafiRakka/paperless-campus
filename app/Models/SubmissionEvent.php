<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submission_id', 'actor_id', 'event', 'metadata', 'occurred_at'])]
class SubmissionEvent extends Model
{
    protected function casts(): array { return ['metadata' => 'array', 'occurred_at' => 'datetime']; }
    public function submission(): BelongsTo { return $this->belongsTo(Submission::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
