<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        return $user->is_active && ($user->isRole('admin', 'verifikator') || $submission->user_id === $user->id);
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->is_active && $user->isRole('pengaju') && $submission->user_id === $user->id
            && in_array($submission->status, ['draft', 'revision_requested'], true);
    }

    public function decide(User $user, Submission $submission): bool
    {
        return $user->is_active && $user->isRole('verifikator') && $submission->user_id !== $user->id
            && in_array($submission->status, ['submitted', 'in_review'], true);
    }

    public function download(User $user, Submission $submission): bool
    {
        return $user->is_active && ($submission->status === 'completed')
            && ($user->id === $submission->user_id || $user->isRole('admin', 'verifikator'));
    }
}
