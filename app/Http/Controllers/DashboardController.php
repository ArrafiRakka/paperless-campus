<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Services\SavingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SavingsService $savings): View
    {
        $user = $request->user();
        $role = $user->role;
        $base = Submission::query()->with('documentType:id,name,code')->latest();
        if ($role === 'pengaju') $base->where('user_id', $user->id);
        if ($role === 'verifikator') $base->whereIn('status', ['submitted', 'in_review']);
        $submissions = (clone $base)->limit(6)->get();
        $counts = ['total' => (clone $base)->count(), 'pending' => (clone $base)->whereIn('status', ['submitted', 'in_review', 'revision_requested'])->count(), 'completed' => (clone $base)->where('status', 'completed')->count()];

        return view('dashboard.index', [
            'role' => $role,
            'submissions' => $submissions,
            'counts' => $counts,
            'impact' => $savings->dashboard($role === 'pengaju' ? $user->id : null),
        ]);
    }
}
