@extends('layouts.app')
@section('title', 'Masuk')
@section('body-class', 'auth-page')
@section('content')
<div class="auth-layout">
    <section class="auth-visual">
        <h1>Keseruan berikutnya<br><span>dimulai dari Anda.</span></h1>
        <img src="{{ asset('images/trophy.png') }}" alt="Piala {{ config('app.name') }}" width="500" height="360">
        <p>Satu kuis. Banyak ide. Belajar bersama.</p>
    </section>
    <section class="auth-card">
        <a class="back-link" href="{{ route('home') }}">@include('partials.icon', ['name' => 'back']) Beranda</a>
        <h2>Selamat datang kembali.</h2>
        <p class="muted">Masuk untuk melanjutkan kuis dan permainan Anda.</p>
        <form action="{{ route('login.store') }}" method="POST" novalidate>
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required placeholder="nama@email.com" @error('email') aria-invalid="true" @enderror>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <div class="password-field">
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan kata sandi">
                    <button type="button" class="password-toggle" aria-controls="password" aria-pressed="false">Lihat</button>
                </div>
            </div>
            <label class="check-label"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button class="btn btn-primary w-100 auth-submit">Masuk @include('partials.icon', ['name' => 'arrow'])</button>
        </form>
        <div class="auth-divider"></div>
        <a class="guest-link" href="{{ route('join') }}">@include('partials.icon', ['name' => 'game']) Mau ikut main? Gabung dengan PIN</a>
    </section>
</div>
@endsection