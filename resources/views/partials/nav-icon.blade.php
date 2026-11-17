<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('home') <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/> @break
        @case('list') <path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/> @break
        @case('archive') <path d="M3 4h18v4H3zM5 8v12h14V8M9 12h6"/> @break
        @case('leaf') <path d="M20 4c-8 0-14 3-14 10a6 6 0 0 0 6 6c7 0 8-8 8-16Z"/><path d="M4 21c3-5 6-8 11-11"/> @break
        @case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/> @break
        @case('user') <circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/> @break
        @case('users') <path d="M16 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM17 4.3a3.5 3.5 0 0 1 0 6.8M21 20v-1a4 4 0 0 0-3-3.9"/> @break
        @case('settings') <path d="M4 6h16M4 12h16M4 18h16"/><circle cx="9" cy="6" r="2" fill="var(--surface)"/><circle cx="15" cy="12" r="2" fill="var(--surface)"/><circle cx="11" cy="18" r="2" fill="var(--surface)"/> @break
        @case('flask') <path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3M8 15h8"/> @break
    @endswitch
</svg>
