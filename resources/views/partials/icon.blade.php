<svg class="icon {{ $class ?? '' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('home')<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>@break
@case('quiz')<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('game')<path d="M7 7h10c3 0 5 11 3 12-2 1-4-3-5-3H9c-1 0-3 4-5 3-2-1 0-12 3-12Z"/><path d="M7 10v5M4.5 12.5h5M16 11h.01M18 14h.01"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('arrow')<path d="M5 12h14m-5-5 5 5-5 5"/>@break
@case('back')<path d="M19 12H5m5-5-5 5 5 5"/>@break
@case('play')<path d="m9 5 11 7-11 7Z" fill="currentColor" stroke-width="0"/>@break
@case('check')<path d="m5 12 4 4L19 6"/>@break
@case('search')<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>@break
@case('users')<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4c2 0 3 2 3 4v2"/>@break
@case('more')<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>@break
@case('list')<path d="M8 6h12M8 12h12M8 18h12M3 6h.01M3 12h.01M3 18h.01"/>@break
@case('edit')<path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m13 6 5 5"/>@break
@case('copy')<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V3H3v13h5"/>@break
@case('trash')<path d="M3 6h18M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7M14 10v7"/>@break
@case('logout')<path d="M10 4H4v16h6M9 12h12m-4-4 4 4-4 4"/>@break
@case('trophy')<path d="M7 3h10v7a5 5 0 0 1-10 0ZM7 5H3v3a4 4 0 0 0 4 4m10-7h4v3a4 4 0 0 1-4 4M12 15v6M8 21h8"/>@break
@case('close')<path d="m6 6 12 12M6 18 18 6"/>@break
@case('up')<path d="m6 14 6-6 6 6"/>@break
@case('down')<path d="m6 10 6 6 6-6"/>@break
@case('image')<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1"/><path d="m3 17 6-6 4 4 3-3 5 5"/>@break
@endswitch
</svg>
