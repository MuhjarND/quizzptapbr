<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#3f5eff"><meta name="description" content="{{ config('app.name') }} — buat kuis interaktif dan mainkan bersama teman, kelas, atau tim Anda.">
    <title>@yield('title', config('app.name')) · {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}?v=cerdas-bersama-1">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/characters.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand-music.css') }}?v={{ filemtime(public_path('css/brand-music.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/standings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/podium.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('head')
</head>
<body class="@yield('body-class')">
<a class="skip-link" href="#main">Lewati ke konten</a>
@if(View::hasSection('sidebar'))
<aside class="sidebar">
    @include('partials.brand', ['brandHref'=>route('dashboard')])
    <nav aria-label="Navigasi utama" class="side-nav">
        <a class="{{ request()->routeIs('dashboard') ? 'selected' : '' }}" href="{{ route('dashboard') }}">@include('partials.icon', ['name' => 'home']) Dasbor</a>
        <a class="{{ request()->routeIs('quizzes.*') ? 'selected' : '' }}" href="{{ route('quizzes.index') }}">@include('partials.icon', ['name' => 'quiz']) Kuis saya</a>
        <a class="{{ request()->routeIs('history', 'games.result') ? 'selected' : '' }}" href="{{ route('history') }}">@include('partials.icon', ['name' => 'clock']) Riwayat permainan</a>
    </nav>
    <div class="sidebar-bottom">
        <a class="join-nav" href="{{ route('join') }}">@include('partials.icon', ['name' => 'game']) Gabung permainan</a>
        <div class="profile"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>Host</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-btn" aria-label="Keluar akun" title="Keluar akun">@include('partials.icon', ['name' => 'logout'])</button></form></div>
    </div>
</aside>
@else
<header class="public-header">@include('partials.brand', ['brandHref'=>route('home')])<nav aria-label="Navigasi">@auth<a href="{{ route('dashboard') }}">Dasbor</a>@else<a href="{{ route('login') }}">Masuk</a>@endauth<a class="btn btn-outline-primary" href="{{ route('join') }}">@include('partials.icon', ['name' => 'game']) Gabung permainan</a></nav></header>
@endif
<main id="main" class="{{ View::hasSection('sidebar') ? 'app-main' : 'public-main' }}">
@if(session('status'))<div class="notice success" role="status">@include('partials.icon', ['name' => 'check']) {{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error error-summary" role="alert" tabindex="-1"><strong>Periksa kembali isian Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
<dialog id="confirm-dialog" aria-labelledby="confirm-title"><form method="dialog"><div class="dialog-icon">@include('partials.icon', ['name' => 'trash'])</div><h2 id="confirm-title">Hapus kuis?</h2><p id="confirm-description"></p><div class="dialog-actions"><button class="btn btn-light" value="cancel" autofocus>Batal</button><button class="btn btn-danger" value="confirm" id="confirm-action">Hapus kuis</button></div></form></dialog>
@stack('scripts')
</body></html>
