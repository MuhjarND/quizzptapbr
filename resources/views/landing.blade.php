@extends('layouts.app')
@section('title', 'Bikin belajar jadi permainan')
@section('body-class', 'landing-page')
@section('content')
<section class="landing-hero"><div><h1>Ide seru,<br><span>jadi kuis seru.</span></h1><p>Bawa suasana baru ke kelas, acara, atau kumpul bareng. Buat kuis interaktif dan mainkan bersama, dari perangkat masing-masing.</p><div class="landing-actions"><a class="btn btn-primary btn-lg" href="{{ auth()->check() ? route('quizzes.create') : route('login') }}">@include('partials.icon', ['name' => 'plus']) Buat Kuis</a><a class="btn btn-outline-primary btn-lg" href="{{ route('join') }}">Gabung Permainan @include('partials.icon', ['name' => 'arrow'])</a></div><div class="landing-note">Tanpa akun untuk peserta. Cukup PIN, langsung main.</div></div><img src="{{ asset('images/trophy.png') }}" alt="Piala {{ config('app.name') }} berwarna biru dengan bintang emas" width="640" height="440"></section>
<section class="how-it-works"><h2>Dari ide ke arena, dalam tiga langkah.</h2><div class="steps"><article><span>1</span><h3>Rangkai kuis Anda</h3><p>Tulis pertanyaan, pilih jawaban benar, dan atur waktu bermain.</p></article><article><span>2</span><h3>Ajak semua bergabung</h3><p>Bagikan PIN permainan. Peserta cukup membuka browser dan memasukkan nama.</p></article><article><span>3</span><h3>Mulai keseruannya</h3><p>Jawab dengan cepat, kumpulkan poin, dan rayakan podium bersama.</p></article></div></section>
<footer class="public-footer">Dibuat untuk rasa ingin tahu. <span>{{ config('app.name') }} © {{ date('Y') }}</span></footer>
@endsection
