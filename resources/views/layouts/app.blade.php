<!doctype html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f1412"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PaperLess Campus') - PaperLess Campus</title>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('paperless-theme')||'dark'}catch{}</script>
    <link rel="stylesheet" href="{{ asset('paperless.css') }}?v=20261117a">
    <script src="{{ asset('paperless.js') }}?v=20261117a" defer></script>
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar')
    <div class="mobile-scrim" id="scrim"></div>
    <main class="main-area">
        <header class="topbar"><button class="icon-button menu-button" id="menuButton" aria-label="Buka navigasi"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button><div class="crumb"><span>PaperLess Campus</span><b>/</b><strong>@yield('title', 'Ringkasan')</strong></div><div class="top-actions"><span class="demo-chip"><i></i> {{ config('app.env') === 'production' ? 'Layanan kampus' : 'Lingkungan demo' }}</span><span class="avatar avatar-small">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span><span class="top-user-name">{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="button button-quiet button-small">Keluar</button></form></div></header>
        <div class="page-content">
            @if(session('success'))<div class="flash flash-success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="flash flash-error" role="alert">Periksa kembali data yang dimasukkan. {{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </main>
    <nav class="mobile-nav">@foreach($navItems as $item)<a href="{{ $item['url'] }}" class="{{ request()->url() === $item['url'] ? 'active' : '' }}"><span>@include('partials.nav-icon',['name'=>$item['icon']])</span><span>{{ $item['short'] }}</span></a>@endforeach</nav>
</div>
</body></html>
