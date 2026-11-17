<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'estimated_sheets', 'estimated_trips', 'approval_steps', 'is_active', 'baseline_source', 'baseline_effective_at'])]
class DocumentType extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'baseline_effective_at' => 'date'];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
