@php($characterInfo = \App\Support\Characters::get($characterKey ?? 'nala'))
<img class="character-avatar {{ $characterClass ?? '' }}" src="{{ asset('images/characters/'.$characterInfo['image']) }}" alt="Karakter {{ $characterInfo['name'] }}" width="64" height="64" style="--character-color:{{ $characterInfo['color'] }}">
