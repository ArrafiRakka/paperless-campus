<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8faf6"><title>PaperLess Campus — Administrasi Kampus Digital</title>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('paperless-theme')||'light'}catch{document.documentElement.dataset.theme='light'}</script>
    <link rel="stylesheet" href="{{ asset('paperless.css') }}">
    <script src="{{ asset('paperless.js') }}" defer></script>
</head>
<body>
<div id="app" class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="#beranda" aria-label="PaperLess Campus, beranda"><span class="brand-mark"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M8 5.5h10l6 6V26H8z"/><path d="M18 5.5v6h6M12 17h8M12 21h5"/><path d="M9 9 5 13v13h14"/></svg></span><span class="brand-copy"><strong>paperless</strong><small>UNIVERSITAS NUSANTARA</small></span></a>
        <div class="role-picker"><span class="eyebrow">MODE DEMO</span><select id="roleSelect" aria-label="Pilih peran demo"><option value="pengaju">Pengaju</option><option value="verifikator">Verifikator</option><option value="admin">Admin unit</option></select><span class="role-avatar" id="roleAvatar">RA</span></div>
        <div class="nav-label">RUANG KERJA</div><nav class="side-nav" id="sideNav"></nav>
        <div class="sidebar-bottom"><div class="impact-mini"><div class="mini-icon">✳</div><div><b>Lebih ringkas, lebih terukur.</b><span>Setiap proses punya cerita dampak.</span></div><a href="#dampak" aria-label="Lihat dampak">↗</a></div><button class="theme-toggle" id="themeToggle"><span>◐</span><span>Mode gelap</span><span class="toggle-track"><i></i></span></button><div class="profile-mini"><div class="avatar">RA</div><div class="profile-text"><b id="profileName">Rania Aditya</b><span id="profileRole">Mahasiswa · 23.11.0456</span></div><button class="icon-button" id="profileMenu" aria-label="Buka profil">···</button></div></div>
    </aside>
    <div class="mobile-scrim" id="scrim"></div>
    <main class="main-area">
        <header class="topbar"><button class="icon-button menu-button" id="menuButton" aria-label="Buka navigasi">☰</button><div class="crumb"><span>Universitas Nusantara</span><b>/</b><strong id="crumbTitle">Ringkasan</strong></div><div class="top-actions"><div class="demo-chip"><i></i> Lingkungan demo</div><button class="icon-button notification-button" id="notifications" aria-label="Notifikasi">♧<i></i></button><button class="top-user" id="topUser"><span class="avatar avatar-small">RA</span><span id="topUserName">Rania Aditya</span><span>⌄</span></button></div></header>
        <div id="view" class="page-content"></div>
    </main>
    <nav class="mobile-nav" id="mobileNav"></nav>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"><span class="toast-check">✓</span><span id="toastText">Perubahan berhasil disimpan</span></div>
<div class="modal-backdrop" id="modal" aria-hidden="true"><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle"><button class="icon-button modal-close" id="modalClose" aria-label="Tutup">×</button><div class="modal-symbol" id="modalSymbol">✓</div><h2 id="modalTitle">Konfirmasi</h2><p id="modalBody">Lanjutkan tindakan ini?</p><div class="modal-actions"><button class="button button-quiet" id="modalCancel">Kembali</button><button class="button button-primary" id="modalConfirm">Ya, lanjutkan</button></div></section></div>
</body></html>
