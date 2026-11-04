(() => {
    'use strict';
    // Shares the existing audio context, with its own gain, preference and lifecycle.
    window.createQuizMusic = ({getContext,ensureContext,isAvailable}) => {
        const key = 'pta-pabar.music.v1';
        const url = document.querySelector('[data-music-src]')?.dataset.musicSrc;
        let enabled = true, volume = .25, stage = 'lobby', deadline = null;
        let buffer = null, source = null, gain = null, duckGain = null, graphContext = null;
        let loading = false, error = false, disposed = false, offset = 0, startedAt = 0, deadlineTimer = null;
        try {
            const saved = JSON.parse(localStorage.getItem(key) || 'null');
            if (saved && typeof saved.enabled === 'boolean') enabled = saved.enabled;
            if (saved && Number.isFinite(saved.volume)) volume = Math.max(0,Math.min(1,saved.volume));
        } catch (_) {}
        const persist = () => { try { localStorage.setItem(key,JSON.stringify({enabled,volume})); } catch (_) {} };
        const status = () => ({enabled,volume,playing:!!source,loaded:!!buffer,loading,error,stage});
        function update() {
            const ready = getContext()?.state === 'running', available = isAvailable();
            document.querySelectorAll('[data-music-toggle]').forEach(button => {
                button.disabled = !available;
                button.setAttribute('aria-pressed',String(enabled && ready));
                button.dataset.musicState = source ? 'on' : enabled && ready ? 'ready' : 'off';
                button.querySelector('[data-music-label]').textContent = !available ? 'Musik tidak tersedia' : error ? 'Coba musik lagi' : !enabled ? 'Musik mati' : !ready ? 'Aktifkan musik' : loading ? 'Memuat musik…' : source ? 'Musik aktif' : 'Musik siap';
            });
            document.querySelectorAll('[data-music-volume]').forEach(input => {
                input.value = Math.round(volume*100); input.disabled = !available;
                input.setAttribute('aria-valuetext',`${Math.round(volume*100)} persen`);
            });
        }
        function stop(reset = false) {
            if (source) {
                if (buffer) offset = (offset + Math.max(0,getContext().currentTime-startedAt)) % buffer.duration;
                try { source.stop(); } catch (_) {}
                source.disconnect(); source = null;
            }
            if (reset) offset = 0;
        }
        function graph() {
            const context = getContext();
            if (graphContext !== context) {
                stop(true); gain = context.createGain(); duckGain = context.createGain();
                gain.connect(duckGain); duckGain.connect(context.destination); graphContext = context;
                duckGain.gain.value = 1;
            }
            gain.gain.value = volume;
        }
        function reconcile() {
            const context = getContext();
            const expired = deadline !== null && Date.now() >= deadline;
            if (disposed || !enabled || !url || !buffer || volume === 0 || document.hidden || stage !== 'question_active' || expired || context?.state !== 'running') {
                stop(); update(); return;
            }
            if (!source) {
                graph(); source = context.createBufferSource(); source.buffer = buffer; source.loop = true;
                source.connect(gain); startedAt = context.currentTime; source.start(0,offset % buffer.duration);
            }
            update();
        }
        async function load() {
            if (!url || buffer || loading || error || disposed || !enabled || getContext()?.state !== 'running') return;
            loading = true; update(); const context = getContext();
            const controller = new AbortController(), timeout = setTimeout(() => controller.abort(),10000);
            try {
                const response = await fetch(url,{signal:controller.signal,credentials:'same-origin',cache:'force-cache'});
                if (!response.ok) throw new Error('Music unavailable');
                const decoded = await context.decodeAudioData(await response.arrayBuffer());
                if (!disposed && context === getContext() && context.state !== 'closed') buffer = decoded;
            } catch (_) { if (!disposed) error = true; }
            finally { clearTimeout(timeout); loading = false; reconcile(); }
        }
        function sync() { if (disposed) return; load(); reconcile(); }
        function setState(value,expires = null) {
            stage = value; deadline = Number.isFinite(expires) ? expires : null;
            clearTimeout(deadlineTimer);
            if (stage === 'question_active' && deadline !== null) deadlineTimer = setTimeout(() => reconcile(),Math.max(0,deadline-Date.now())+10);
            if (stage === 'finished') stop(true);
            sync();
        }
        function setEnabled(value) { enabled = !!value; if (!enabled) stop(); persist(); sync(); }
        function setVolume(value) {
            if (!Number.isFinite(Number(value))) return;
            volume = Math.max(0,Math.min(1,Number(value)));
            if (gain && getContext()) gain.gain.setTargetAtTime(volume,getContext().currentTime,.03);
            persist(); sync();
        }
        function duck(seconds) {
            if (!source || !duckGain) return;
            const now = getContext().currentTime;
            duckGain.gain.cancelScheduledValues(now); duckGain.gain.setValueAtTime(.3,now);
            duckGain.gain.setTargetAtTime(1,now+seconds,.09);
        }
        document.querySelectorAll('[data-music-toggle]').forEach(button => button.addEventListener('click',async () => {
            if (enabled && getContext()?.state === 'running' && !error) setEnabled(false);
            else {
                error = false; enabled = true; persist();
                await ensureContext(); sync();
            }
        }));
        document.querySelectorAll('[data-music-volume]').forEach(input => input.addEventListener('input',() => setVolume(Number(input.value)/100)));
        document.addEventListener('visibilitychange',sync);
        window.addEventListener('pagehide',() => { disposed = true; clearTimeout(deadlineTimer); stop(true); });
        update(); return {setState,setEnabled,setVolume,sync,duck,status};
    };
})();
