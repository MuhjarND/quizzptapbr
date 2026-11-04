(() => {
    'use strict';
    const storageKey = 'quizarena.sound.v1';
    let enabled = true, volume = .45, context = null, master = null, unavailable = false;
    const voices = new Set();
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
        if (saved && typeof saved.enabled === 'boolean') enabled = saved.enabled;
        if (saved && Number.isFinite(saved.volume)) volume = Math.max(0, Math.min(1, saved.volume));
    } catch (_) { /* Storage may be blocked; the controls still work for this page. */ }
    const cues = {
        pick:[[659.25,0,.075],[880,.065,.11]],
        join:[[523.25,0,.09],[659.25,.08,.13]],
        question:[[392,0,.13],[523.25,.12,.13],[783.99,.24,.24]],
        answer:[[523.25,0,.07],[783.99,.06,.13]],
        tick:[[880,0,.055,'sine',.45]],
        correct:[[523.25,0,.14],[659.25,.12,.14],[783.99,.24,.14],[1046.5,.36,.3]],
        incorrect:[[349.23,0,.18,'sine'],[293.66,.16,.24,'sine']],
        reveal:[[523.25,0,.25],[659.25,.08,.25],[783.99,.16,.35]],
        score_tick:[[1174.66,0,.045,'sine',.19]],
        score_done:[[659.25,0,.12,'sine',.45],[783.99,.10,.18,'sine',.45]],
        rank_up:[[523.25,0,.12,'triangle',.5],[783.99,.10,.13,'triangle',.5],[1046.5,.21,.23,'sine',.5]],
        rank_down:[[440,0,.13,'sine',.35],[392,.10,.14,'sine',.35],[329.63,.22,.22,'sine',.35]],
        ranking:[[587.33,0,.14,'sine',.45],[783.99,.12,.18,'sine',.45],[987.77,.25,.22,'sine',.4]],
        podium_open:[[196,0,.28,'triangle',.35],[293.66,.22,.28,'triangle',.4],[392,.44,.28,'triangle',.4],[523.25,.7,.35,'triangle',.45],[587.33,1.05,.35,'triangle',.45]],
        podium_third:[[392,0,.17],[493.88,.17,.17],[587.33,.34,.38],[392,.34,.38,'sine',.3]],
        podium_second:[[440,0,.14],[554.37,.14,.14],[659.25,.28,.17],[880,.45,.42],[554.37,.45,.42,'sine',.3]],
        champion:[[523.25,0,.15],[659.25,.15,.15],[783.99,.3,.2],[1046.5,.5,.25],[783.99,.8,.15],[1046.5,1,.65],[659.25,1,.65,'sine',.4],[783.99,1,.65,'sine',.4],[1308.13,1.35,.55,'sine',.3]],
        finish:[[392,0,.16],[523.25,.16,.16],[659.25,.32,.16],[783.99,.48,.23],[1046.5,.7,.48],[659.25,.7,.48,'sine',.35],[783.99,.7,.48,'sine',.35]]
    };
    function persist() { try { localStorage.setItem(storageKey, JSON.stringify({enabled,volume})); } catch (_) {} }
    function status() { return {enabled,volume,ready:!!context && context.state === 'running',available:!unavailable}; }
    function updateControls() {
        const ready = status().ready;
        document.querySelectorAll('[data-sound-toggle]').forEach(button => {
            button.setAttribute('aria-pressed', String(enabled && ready));
            button.dataset.soundState = unavailable ? 'unavailable' : enabled && ready ? 'on' : 'off';
            button.disabled = unavailable;
            button.querySelector('[data-sound-label]').textContent = unavailable ? 'Suara tidak tersedia' : !enabled ? 'Suara mati' : ready ? 'Suara aktif' : 'Aktifkan suara';
            button.querySelector('[data-sound-symbol]').textContent = enabled && ready ? '♫' : '♪';
        });
        document.querySelectorAll('[data-sound-volume]').forEach(input => { input.value = Math.round(volume*100); input.setAttribute('aria-valuetext', `${Math.round(volume*100)} persen`); input.disabled = unavailable; });
        document.querySelectorAll('[data-sound-hint]').forEach(hint => { hint.hidden = !enabled || ready || unavailable; });
        document.querySelectorAll('[data-sound-description]').forEach(description => {
            description.textContent = unavailable ? 'Browser ini tidak mendukung efek suara.' : !enabled ? 'Efek suara dimatikan.' : ready ? 'Efek suara aktif. Volume bisa Anda atur.' : 'Aktifkan suara, biar makin seru.';
        });
    }
    function stopAll() { voices.forEach(voice => { try { voice.stop(); } catch (_) {} }); voices.clear(); }
    async function unlock() {
        if ((!enabled && !window.QuizMusic?.status().enabled) || unavailable) return false;
        try {
            if (!context || context.state === 'closed') {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) { unavailable = true; updateControls(); window.QuizMusic?.sync(); return false; }
                context = new AudioContext(); master = context.createGain(); master.connect(context.destination);
                master.gain.value = enabled ? volume * .22 : 0;
                context.onstatechange = updateControls;
            }
            if (context.state === 'suspended') await context.resume();
            updateControls(); window.QuizMusic?.sync(); return context.state === 'running';
        } catch (_) { unavailable = true; updateControls(); window.QuizMusic?.sync(); return false; }
    }
    function play(name) {
        if (!enabled || !context || context.state !== 'running' || volume === 0 || document.hidden || !cues[name]) return false;
        const start = context.currentTime + .01;
        window.QuizMusic?.duck(Math.max(...cues[name].map(([,delay,duration]) => delay+duration))+.04);
        cues[name].forEach(([frequency,delay,duration,type='triangle',level=.7]) => {
            const oscillator = context.createOscillator(), envelope = context.createGain(), time = start + delay;
            oscillator.type = type; oscillator.frequency.setValueAtTime(frequency,time);
            envelope.gain.setValueAtTime(0,time); envelope.gain.linearRampToValueAtTime(level,time+.008);
            envelope.gain.exponentialRampToValueAtTime(.001,time+duration);
            oscillator.connect(envelope); envelope.connect(master); voices.add(oscillator);
            oscillator.onended = () => { voices.delete(oscillator); oscillator.disconnect(); envelope.disconnect(); };
            oscillator.start(time); oscillator.stop(time+duration+.015);
        }); return true;
    }
    function setEnabled(value) {
        enabled = !!value; if (!enabled) stopAll();
        if (master && context) master.gain.setTargetAtTime(enabled ? volume*.22 : 0,context.currentTime,.015);
        persist(); updateControls();
    }
    function setVolume(value) {
        if (!Number.isFinite(Number(value))) return;
        volume = Math.max(0,Math.min(1,Number(value)));
        if (master && context) master.gain.setTargetAtTime(enabled ? volume*.22 : 0,context.currentTime,.015);
        persist(); updateControls();
    }
    document.querySelectorAll('[data-sound-toggle]').forEach(button => button.addEventListener('click', async () => {
        if (enabled && status().ready) setEnabled(false);
        else { setEnabled(true); if (await unlock()) play('pick'); }
    }));
    document.querySelectorAll('[data-sound-volume]').forEach(input => input.addEventListener('input', () => setVolume(Number(input.value)/100)));
    const activate = event => {
        if (event.target.closest('[data-sound-toggle],[data-music-toggle]')) return;
        if (event.type === 'keydown' && !['Enter',' ','ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key)) return;
        if ((enabled || window.QuizMusic?.status().enabled) && !status().ready) unlock();
    };
    document.addEventListener('pointerdown',activate,{capture:true}); document.addEventListener('keydown',activate,{capture:true});
    document.addEventListener('visibilitychange', () => { if (document.hidden) stopAll(); });
    window.addEventListener('pagehide', () => { stopAll(); if (context) context.close().catch(() => {}); });
    window.ArenaSound = {unlock,play,setEnabled,setVolume,status}; updateControls();
    if (window.createQuizMusic) window.QuizMusic = window.createQuizMusic({getContext:()=>context,ensureContext:unlock,isAvailable:()=>!unavailable});
})();
