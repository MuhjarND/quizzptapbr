@extends('layouts.app')
@section('title', $game->title)
@section('body-class', 'game-page')
@section('content')
<div class="game-topline"><div><span class="quiz-status ready"><span></span>{{ $game->title }}</span></div><div class="game-meta">@if(!$isHost)<span class="player-name">@include('partials.character', ['characterKey'=>$participant->character, 'characterClass'=>'player-character']) <span>{{ $participant->nickname }}</span></span>@endif<span>PIN: <strong>{{ $game->pin }}</strong></span>@if($isHost)<button class="btn btn-outline-danger btn-sm" id="finish-game">Akhiri permainan</button>@endif</div></div>
<div class="game-audio-bar">@include('partials.sound-controls', ['musicControls'=>true])<span class="sound-hint" data-sound-hint>Ketuk untuk mengaktifkan audio.</span></div>
<div id="connection-status" role="status" class="connection-status" hidden></div><div id="game-error" role="alert" class="notice error" hidden></div>
<div id="game-root" class="game-root" aria-live="polite"><div class="loading-panel"><span class="loader"></span><p>Menghubungkan ke arena…</p></div></div>
<div class="game-bottomline"><span>@include('partials.icon', ['name' => 'game']) Belajar bareng, seru bareng.</span><span id="sync-status">Menghubungkan…</span></div>
@endsection
@php
    $gameConfig = ['host' => $isHost, 'playerId' => $participant ? $participant->id : null, 'stateUrl' => $isHost ? route('games.host-state', $game) : route('games.state', $game), 'controlUrl' => $isHost ? route('games.control', $game) : null, 'answerUrl' => route('games.answer', $game), 'resultUrl' => route('games.result', $game), 'dashboardUrl' => route('dashboard'), 'joinUrl' => route('join'), 'trophyUrl' => asset('images/trophy.png'), 'storageUrl' => asset('storage'), 'characters'=>\App\Support\Characters::forBrowser()];
@endphp
@push('scripts')<script id="game-config" type="application/json">@json($gameConfig)</script><script src="{{ asset('js/music.js') }}" defer></script><script src="{{ asset('js/sound.js') }}" defer></script><script src="{{ asset('js/standings.js') }}" defer></script><script src="{{ asset('js/podium.js') }}" defer></script><script src="{{ asset('js/game.js') }}" defer></script>@endpush
