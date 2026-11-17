<?php

namespace App\Providers;

use App\Models\Submission;
use App\Policies\SubmissionPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;
use Illuminate\Support\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Paginator::useBootstrapFive();
        view()->composer('layouts.app', function (ViewContract $view): void {
            $user = auth()->user();
            $all = [
                'dashboard' => ['label' => 'Ringkasan', 'short' => 'Home', 'icon' => 'H', 'route' => 'dashboard'],
                'submissions.index' => ['label' => 'Riwayat Pengajuan', 'short' => 'Riwayat', 'icon' => '=', 'route' => 'submissions.index'],
                'archive.index' => ['label' => 'Arsip Digital', 'short' => 'Arsip', 'icon' => '[]', 'route' => 'archive.index'],
                'impact.index' => ['label' => 'Dampak Lingkungan', 'short' => 'Dampak', 'icon' => '*', 'route' => 'impact.index'],
                'impact.methodology' => ['label' => 'Metodologi', 'short' => 'Metode', 'icon' => 'i', 'route' => 'impact.methodology'],
                'profile.edit' => ['label' => 'Profil & Pengaturan', 'short' => 'Profil', 'icon' => 'o', 'route' => 'profile.edit'],
            ];
            if ($user?->isRole('pengaju')) $all['submissions.create'] = ['label' => 'Buat Pengajuan', 'short' => 'Buat', 'icon' => '+', 'route' => 'submissions.create'];
            if ($user?->isRole('verifikator')) $all['reviews.index'] = ['label' => 'Antrean Verifikasi', 'short' => 'Antrean', 'icon' => 'v', 'route' => 'reviews.index', 'count' => Submission::query()->whereIn('status', ['submitted', 'in_review'])->count()];
            if ($user?->isRole('admin')) foreach (['admin.users' => ['Manajemen Pengguna', 'Pengguna', 'U'], 'admin.services' => ['Konfigurasi Layanan', 'Layanan', '[]'], 'admin.factors' => ['Faktor Emisi', 'Faktor', '~']] as $route => [$label, $short, $icon]) $all[$route] = ['label' => $label, 'short' => $short, 'icon' => $icon, 'route' => $route];
            $items = collect($all)->map(fn (array $item) => [...$item, 'url' => route($item['route'])])->values();
            $view->with('navItems', $items);
        });
    }
}
