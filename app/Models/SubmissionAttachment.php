<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submission_id', 'uploaded_by', 'path', 'original_name', 'mime', 'size_bytes'])]
class SubmissionAttachment extends Model
{
    public function submission(): BelongsTo { return $this->belongsTo(Submission::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
