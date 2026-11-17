<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submission_id', 'document_number', 'pdf_path', 'sha256', 'generated_at'])]
class GeneratedDocument extends Model
{
    protected function casts(): array { return ['generated_at' => 'datetime']; }
    public function submission(): BelongsTo { return $this->belongsTo(Submission::class); }
}
