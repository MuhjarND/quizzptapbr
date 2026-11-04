@extends('layouts.app')
@section('title', 'Gabung permainan')
@section('body-class', 'join-page')
@section('content')
@php($selectedCharacter = \App\Support\Characters::key(old('character', 'nala')))
<form class="character-join" action="{{ route('join.store') }}" method="POST" novalidate>@csrf
    <fieldset class="character-picker" aria-describedby="character-help">
        <legend>Pilih karaktermu.</legend><p id="character-help">Teman kecil untuk petualangan besarmu di arena.</p>
        <div class="character-preview" id="character-preview" style="--character-color:{{ config('characters.'.$selectedCharacter.'.color') }}">
            @include('partials.character', ['characterKey'=>$selectedCharacter, 'characterClass'=>'character-preview-image'])
            <div><span>JAGOAN PILIHANMU</span><h2 id="character-name">{{ config('characters.'.$selectedCharacter.'.name') }}</h2><p id="character-kind">{{ config('characters.'.$selectedCharacter.'.kind') }}</p></div>
        </div>
        <div class="character-grid">
        @foreach(config('characters') as $key => $character)
            <label class="character-option" style="--character-color:{{ $character['color'] }}">
                <input type="radio" name="character" value="{{ $key }}" @if($selectedCharacter === $key) checked @endif required aria-label="{{ $character['name'] }}, {{ $character['kind'] }}" @error('character') aria-invalid="true" @enderror>
                <span class="character-tile"><img src="{{ asset('images/characters/'.$character['image']) }}" alt="" width="90" height="90"><span class="character-option-name">{{ $character['name'] }}</span><span class="character-selected" aria-hidden="true">✓</span></span>
            </label>
        @endforeach
        </div><p class="character-selection-status" id="character-selection-status" role="status">{{ config('characters.'.$selectedCharacter.'.name') }} siap menemanimu!</p>
    </fieldset>
    <section class="join-form-panel"><div class="join-emblem">@include('partials.icon', ['name' => 'game'])</div><h1>Masuk ke arena.</h1><p class="muted">Punya PIN dari host? Keseruannya menunggu Anda.</p>
        <div class="field"><label for="pin">PIN permainan</label><input class="form-control pin-input" id="pin" name="pin" type="text" inputmode="numeric" autocomplete="off" maxlength="6" pattern="[0-9]{6}" value="{{ old('pin', request('pin')) }}" placeholder="000 000" required @error('pin') aria-invalid="true" aria-describedby="pin-error" @enderror>@error('pin')<span class="field-help danger-text" id="pin-error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="nickname">Nama panggilan</label><input class="form-control" id="nickname" name="nickname" maxlength="30" minlength="2" value="{{ old('nickname') }}" placeholder="Anda ingin dipanggil siapa?" autocomplete="nickname" required @error('nickname') aria-invalid="true" aria-describedby="nickname-error" @enderror>@error('nickname')<span class="field-help danger-text" id="nickname-error">{{ $message }}</span>@enderror</div>
        <button class="btn btn-primary btn-lg w-100">Gabung permainan @include('partials.icon', ['name' => 'arrow'])</button><p class="field-help center">Peserta tidak perlu membuat akun.</p>
        <div class="join-sound">@include('partials.sound-controls')<p data-sound-description>Aktifkan suara, biar makin seru.</p></div>
    </section>
</form><p class="join-host-link">Ingin jadi host? <a href="{{ route('register') }}">Buat kuis Anda sendiri</a></p>
@endsection
@push('scripts')
@php($characterCatalog = \App\Support\Characters::forBrowser())
<script id="character-catalog" type="application/json">@json($characterCatalog)</script>
<script src="{{ asset('js/sound.js') }}" defer></script><script src="{{ asset('js/characters.js') }}" defer></script>
@endpush
