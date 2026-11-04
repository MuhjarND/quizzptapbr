<div class="sound-controls" aria-label="Pengaturan suara">
    <button class="sound-toggle" type="button" data-sound-toggle aria-pressed="false" title="Aktifkan atau matikan efek suara"><span data-sound-symbol aria-hidden="true">♪</span><span data-sound-label>Aktifkan suara</span></button>
    <label class="visually-hidden" for="sound-volume">Volume efek suara</label><input id="sound-volume" data-sound-volume type="range" min="0" max="100" step="5" value="45" aria-label="Volume efek suara">
</div>
@if($musicControls ?? false)
<div class="music-controls" aria-label="Pengaturan musik latar" data-music-src="{{ asset('audio/pta-pabar-theme.wav') }}">
    <button class="music-toggle" type="button" data-music-toggle aria-pressed="false" title="Musik latar selama menjawab soal"><span aria-hidden="true">♫</span><span data-music-label>Aktifkan musik</span></button>
    <label class="visually-hidden" for="music-volume">Volume musik latar</label><input id="music-volume" data-music-volume type="range" min="0" max="100" step="5" value="25" aria-label="Volume musik latar">
</div>
@endif
