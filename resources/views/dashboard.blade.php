@extends('layouts.app')
@section('title', request()->routeIs('dashboard') ? 'Dasbor' : 'Kuis saya')
@section('sidebar', 'yes')
@section('content')
<header class="page-header"><h1>{{ request()->routeIs('dashboard') ? 'Dasbor' : 'Kuis saya' }}</h1><a class="btn btn-outline-primary" href="{{ route('join') }}">@include('partials.icon', ['name' => 'game']) Gabung permainan</a></header>
<section class="welcome-banner">
    <div class="welcome-copy"><h2>Ide seru, <span>jadi kuis seru.</span></h2><p>Buat kuis, kumpulkan teman, dan mulai keseruannya.</p><a class="btn btn-primary btn-lg" href="{{ route('quizzes.create') }}">@include('partials.icon', ['name' => 'plus']) Buat kuis baru</a></div>
    <img class="hero-art" src="{{ asset('images/trophy.png') }}" alt="Piala biru dengan bintang emas dan bentuk permainan warna-warni" width="460" height="280">
</section>
<div class="stats-row">
    <div class="stat"><span class="stat-icon blue">@include('partials.icon', ['name' => 'quiz'])</span><div><span>Kuis saya</span><strong>{{ $stats['total'] }}</strong></div></div>
    <div class="stat"><span class="stat-icon amber">@include('partials.icon', ['name' => 'play'])</span><div><span>Siap dimainkan</span><strong>{{ $stats['ready'] }}</strong></div></div>
    <div class="stat"><span class="stat-icon green">@include('partials.icon', ['name' => 'check'])</span><div><span>Permainan selesai</span><strong>{{ $stats['finished'] }}</strong></div></div>
</div>
@if($activeGames->count())<section class="active-games"><h2>Permainan aktif</h2>@foreach($activeGames as $active)<a href="{{ route('games.host', $active) }}"><span><strong>{{ $active->title }}</strong> <span class="muted">· PIN {{ $active->pin }}</span></span><span>Lanjutkan @include('partials.icon', ['name' => 'arrow'])</span></a>@endforeach</section>@endif
<section class="quiz-section">
    <div class="section-header"><h2>{{ request()->routeIs('dashboard') ? 'Kuis terbaru' : 'Koleksi kuis Anda' }}</h2>@if(request()->routeIs('dashboard'))<a href="{{ route('quizzes.index') }}">Lihat semua @include('partials.icon', ['name' => 'arrow'])</a>@else<span class="muted">{{ $quizzes->total() }} kuis</span>@endif</div>
    <div class="list-toolbar"><form class="search-form" method="GET">@include('partials.icon', ['name' => 'search'])<label for="search" class="visually-hidden">Cari judul kuis</label><input id="search" type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul kuis…"><input type="hidden" name="status" value="{{ request('status') }}"><button class="search-submit" aria-label="Cari kuis">@include('partials.icon', ['name' => 'arrow'])</button></form>
    <nav class="filters" aria-label="Filter status">@foreach(['' => 'Semua', 'ready' => 'Siap dimainkan', 'draft' => 'Draft'] as $value => $label)<a class="{{ request('status', '') === $value ? 'active' : '' }}" href="{{ request()->url().'?'.http_build_query(array_filter(['q' => request('q'), 'status' => $value])) }}" @if(request('status', '') === $value) aria-current="true" @endif>{{ $label }}</a>@endforeach</nav></div>
    <div class="quiz-grid">
    @forelse($quizzes as $quiz)
        <article class="quiz-card">
            <a class="cover-link" href="{{ route('quizzes.edit', $quiz) }}" aria-label="Edit {{ $quiz->title }}"><img class="quiz-cover" src="{{ $quiz->cover ? asset('storage/'.$quiz->cover) : asset('images/'.$quiz->theme.'.png') }}" alt="" width="400" height="164"></a>
            <div class="quiz-card-body"><a class="quiz-title" href="{{ route('quizzes.edit', $quiz) }}">{{ $quiz->title }}</a><p class="question-count">@include('partials.icon', ['name' => 'list']) {{ $quiz->questions_count }} pertanyaan</p>
            <div class="card-actions"><span class="quiz-status {{ $quiz->status }}"><span></span>{{ $quiz->status === 'ready' ? 'Siap dimainkan' : 'Draft' }}</span>
                @if($quiz->status === 'ready')<form action="{{ route('games.create', $quiz) }}" method="POST">@csrf<button class="btn btn-primary btn-sm">@include('partials.icon', ['name' => 'play']) Mainkan</button></form>@else<a class="btn btn-light btn-sm" href="{{ route('quizzes.edit', $quiz) }}">@include('partials.icon', ['name' => 'edit']) Lengkapi</a>@endif
                <details class="card-menu"><summary class="icon-btn" aria-label="Opsi {{ $quiz->title }}">@include('partials.icon', ['name' => 'more'])</summary><div class="menu-panel"><a href="{{ route('quizzes.edit', $quiz) }}">@include('partials.icon', ['name' => 'edit']) Edit kuis</a><form action="{{ route('quizzes.duplicate', $quiz) }}" method="POST">@csrf<button>@include('partials.icon', ['name' => 'copy']) Gandakan</button></form><form action="{{ route('quizzes.destroy', $quiz) }}" method="POST" data-confirm="Hapus kuis “{{ $quiz->title }}”? Pertanyaan kuis akan dihapus. Riwayat permainan tetap tersimpan." data-confirm-title="Hapus kuis?" data-confirm-action="Hapus kuis">@csrf @method('DELETE')<button class="danger-text">@include('partials.icon', ['name' => 'trash']) Hapus kuis</button></form></div></details>
            </div></div>
        </article>
    @empty
        <div class="empty-state">@include('partials.icon', ['name' => 'quiz'])<h3>{{ request('q') || request('status') ? 'Kuis tidak ditemukan' : 'Kuis pertama Anda dimulai di sini' }}</h3><p>{{ request('q') || request('status') ? 'Coba kata kunci atau filter yang berbeda.' : 'Ubah ide Anda menjadi permainan yang seru.' }}</p><a class="btn btn-primary" href="{{ request('q') || request('status') ? request()->url() : route('quizzes.create') }}">{{ request('q') || request('status') ? 'Reset pencarian' : 'Buat kuis baru' }}</a></div>
    @endforelse
    </div>
    {{ $quizzes->links() }}
    <div class="join-strip"><span class="join-strip-icon">@include('partials.icon', ['name' => 'game'])</span><div><h3>Punya PIN permainan?</h3><p>Gabung ke permainan yang sedang berlangsung dengan memasukkan PIN.</p></div><form action="{{ route('join') }}" method="GET"><label class="visually-hidden" for="quick-pin">PIN permainan</label><input class="form-control" id="quick-pin" name="pin" placeholder="Masukkan PIN" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required><button class="btn btn-primary">Gabung</button></form></div>
</section>
<footer class="app-footer"><span>Setiap jawaban, langkah baru untuk belajar.</span><span>{{ config('app.name') }} © {{ date('Y') }}</span></footer>
@endsection
