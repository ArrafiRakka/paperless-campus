<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['registration_number', 'user_id', 'document_type_id', 'data', 'purpose', 'status', 'current_step', 'submitted_at', 'completed_at'])]
class Submission extends Model
{
    protected function casts(): array
    {
        return ['data' => 'array', 'submitted_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function documentType(): BelongsTo { return $this->belongsTo(DocumentType::class); }
    public function attachments(): HasMany { return $this->hasMany(SubmissionAttachment::class); }
    public function approvals(): HasMany { return $this->hasMany(ApprovalAction::class)->orderBy('step_number')->orderBy('acted_at'); }
    public function events(): HasMany { return $this->hasMany(SubmissionEvent::class)->orderBy('occurred_at'); }
    public function generatedDocument(): HasOne { return $this->hasOne(GeneratedDocument::class); }
}
