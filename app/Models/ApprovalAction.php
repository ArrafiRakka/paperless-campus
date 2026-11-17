<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submission_id', 'verifier_id', 'step_number', 'action', 'note', 'acted_at'])]
class ApprovalAction extends Model
{
    protected function casts(): array { return ['acted_at' => 'datetime']; }
    public function submission(): BelongsTo { return $this->belongsTo(Submission::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verifier_id'); }
}
