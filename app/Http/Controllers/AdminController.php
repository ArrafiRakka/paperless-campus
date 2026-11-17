<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\EmissionFactor;
use App\Models\EmissionFactorRevision;
use App\Models\User;
use App\Services\SavingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function users(): View { return view('admin.users', ['users' => User::query()->orderBy('name')->paginate(15)]); }

    public function saveUser(Request $request, ?User $user = null): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user?->id], 'role' => ['required', 'in:pengaju,verifikator,admin'], 'nim' => ['nullable', 'string', 'max:40'], 'unit' => ['nullable', 'string', 'max:120'], 'password' => [$user ? 'nullable' : 'required', 'string', 'min:10']]);
        if ($user && $user->id === $request->user()->id && $data['role'] !== 'admin') return back()->withErrors(['role' => 'Admin tidak dapat menghapus hak akses dirinya sendiri.']);
        if (filled($data['password'] ?? null)) $data['password'] = Hash::make($data['password']); else unset($data['password']);
        User::query()->updateOrCreate(['id' => $user?->id], $data);

        return back()->with('success', 'Pengguna berhasil disimpan.');
    }

    public function toggleUser(User $user, Request $request): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Admin tidak dapat menonaktifkan akun sendiri.');
        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('success', 'Status akun diperbarui.');
    }

    public function services(): View { return view('admin.services', ['types' => DocumentType::query()->orderBy('id')->get()]); }

    public function saveService(Request $request, DocumentType $type): RedirectResponse
    {
        $data = $request->validate(['estimated_sheets' => ['required', 'integer', 'between:0,500'], 'estimated_trips' => ['required', 'integer', 'between:0,100'], 'approval_steps' => ['required', 'integer', 'between:1,3'], 'is_active' => ['nullable', 'boolean'], 'description' => ['nullable', 'string', 'max:1000'], 'baseline_source' => ['nullable', 'string', 'max:1000'], 'baseline_effective_at' => ['nullable', 'date']]);
        $type->update($data + ['is_active' => $request->boolean('is_active')]);
        app(SavingsService::class)->forget();

        return back()->with('success', 'Konfigurasi layanan disimpan.');
    }

    public function factors(): View { return view('admin.factors', ['factors' => EmissionFactor::query()->with('revisions')->orderBy('code')->get()]); }

    public function saveFactor(Request $request, EmissionFactor $factor, SavingsService $savings): RedirectResponse
    {
        $data = $request->validate(['value' => ['required', 'numeric', 'min:0', 'max:100000000'], 'unit' => ['required', 'string', 'max:80'], 'source' => ['nullable', 'string', 'max:255'], 'source_year' => ['nullable', 'integer', 'between:1990,2100'], 'description' => ['nullable', 'string', 'max:2000'], 'note' => ['nullable', 'string', 'max:255']]);
        DB::transaction(function () use ($request, $factor, $data): void {
            $old = $factor->value;
            $factor->update(collect($data)->except('note')->all() + ['updated_by' => $request->user()->id]);
            EmissionFactorRevision::create(['emission_factor_id' => $factor->id, 'changed_by' => $request->user()->id, 'old_value' => $old, 'new_value' => $factor->value, 'note' => $data['note'] ?? null, 'changed_at' => now()]);
        });
        $savings->forget();

        return back()->with('success', 'Faktor emisi dan riwayat perubahannya berhasil disimpan.');
    }
}
