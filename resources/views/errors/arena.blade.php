@extends('layouts.app')
@section('title', $errorTitle)
@section('content')
<section class="join-card"><div class="join-emblem">@include('partials.icon', ['name'=>'game'])</div><h1>{{ $errorTitle }}</h1><p class="muted">{{ $errorMessage }}</p><a class="btn btn-primary" href="{{ route('home') }}">Kembali ke beranda</a><p class="field-help"><a href="{{ route('join') }}">Gabung permainan</a> · <a href="{{ route('login') }}">Masuk sebagai host</a></p></section>
@endsection
