(() => {
    'use strict';
    const crown = '<svg viewBox="0 0 64 52" aria-hidden="true"><path d="M8 12 21 25 32 5 43 25 56 12 50 43H14Z" fill="currentColor"/><path d="M17 49h30" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg>';
    function mount(root,state,{host,avatar,esc,points,sound,control,now,isBusy=()=>false,animate=false,leaderboard,links,appName='Cerdas Bersama PTA Papua Barat'}) {
        const p=state.podium, current=p.winners[p.winners.length-1], intro=p.step===1?1600:0;
        const countdown = Number(p.countdown_seconds) === 5 ? 5000 : 0;
        const revealDelay = intro + countdown;
        const elapsed=Math.max(0,now()-Date.parse(p.step_at)), duration=revealDelay+(p.complete?3000:1400);
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const moving=animate&&!document.hidden&&!reduced&&elapsed<duration;
        const slots=Array.from({length:p.total},(_,i)=>i+1);
        const order=p.total===3?[2,1,3]:p.total===2?[2,1]:[1];
        const confetti=Array.from({length:36},(_,i)=>`<i style="--x:${(i*29)%100}%;--drift:${(i%7-3)*36}px;--delay:${i%9*65}ms;--turn:${i*47}deg"></i>`).join('');
        root.innerHTML=`<section class="podium-ceremony ${moving?'is-moving':''} ${p.complete?'has-champion':''} ${elapsed<revealDelay?'is-counting':''}" style="--reveal-delay:${Math.max(0,revealDelay-elapsed)}ms;--opening-delay:${-elapsed}ms" aria-labelledby="podium-heading">
            <header class="podium-header"><p class="podium-eyebrow">${esc(appName)} · Podium juara</p><h1 id="podium-heading">${p.complete?'Inilah sang juara!':'Tepuk tangan untuk para juara'}</h1><p>${p.complete?'Usaha terbaik, momen yang layak dirayakan.':'Dari jawaban terbaik, menuju panggung juara.'}</p><span class="podium-counter">${p.step} dari ${slots.length} juara diumumkan</span></header>
            <div class="ceremony-stage" data-podium-stage>
                <div class="stage-beam beam-left" aria-hidden="true"></div><div class="stage-beam beam-right" aria-hidden="true"></div><div class="stage-orbit" aria-hidden="true"></div>
                ${p.complete?`<div class="champion-confetti" aria-hidden="true">${confetti}</div>`:''}
                <div class="ceremony-podium podium-size-${p.total}">${order.map(rank=>{
                    const row=p.winners.find(w=>w.rank===rank),fresh=row&&row.id===current.id;
                    return `<div class="ceremony-slot slot-${rank} ${row?'is-revealed':'is-secret'} ${fresh?'is-current':''}" data-podium-rank="${rank}">${row?`<div class="ceremony-winner" data-winner-id="${row.id}">${rank===1?`<div class="champion-halo" aria-hidden="true"></div><div class="champion-crown">${crown}</div>`:''}<span class="podium-place-label">Juara ${rank}</span><div class="ceremony-avatar">${avatar(row)}</div><strong class="winner-name">${esc(row.nickname)}</strong><span class="winner-score">${points(row.score)} <small>poin</small></span></div>`:'<div class="ceremony-secret" aria-label="Juara belum diumumkan"><span aria-hidden="true">?</span><small>Belum diumumkan</small></div>'}<div class="ceremony-step"><span>${rank}</span><small>${rank===1?'JUARA PERTAMA':rank===2?'JUARA KEDUA':'JUARA KETIGA'}</small></div></div>`;
                }).join('')}</div>
                ${moving&&p.step===1?'<div class="podium-opening" aria-hidden="true"><div class="stage-curtain curtain-left"></div><div class="stage-curtain curtain-right"></div><div class="opening-title">Siapkan tepuk tangan!<span>Panggung juara akan dibuka</span></div></div>':''}
                ${countdown&&elapsed<revealDelay?`<div class="podium-countdown" data-podium-countdown role="status"><p>Siapakah juara ${current.rank}?</p><strong data-countdown-number>5</strong><span>Pengumuman dalam hitungan detik</span></div>`:''}
            </div>
            <div class="podium-announcement" role="status" data-podium-announcement>${moving?'Mengumumkan juara…':`Juara ${current.rank}: ${esc(current.nickname)}. Selamat!`}</div>
            <div class="ceremony-controls">${!p.complete?(host?'<button class="btn btn-primary btn-lg" id="reveal-winner" disabled>Juara selanjutnya →</button><p>Setiap juara mendapat momennya.</p>':'<p>Menunggu host mengumumkan juara selanjutnya…</p>'):'<p>Terima kasih sudah bermain. Kalian luar biasa!</p>'}</div>
            ${p.complete?`<div data-podium-results ${moving?'hidden':''}>${leaderboard(state.leaderboard,'Peringkat akhir')}<div class="finish-links">${links}</div></div>`:''}
        </section>`;
        let disposed = false, quiet = !animate || reduced || document.hidden || elapsed >= duration, timer = null, lastSecond = null, winnerSoundPlayed = false;
        const button=root.querySelector('#reveal-winner');
        const ceremony = root.querySelector('.podium-ceremony');
        const countdownPanel = root.querySelector('[data-podium-countdown]');
        const countdownNumber = root.querySelector('[data-countdown-number]');
        const currentWinner = root.querySelector('.is-current .ceremony-winner');
        const announcement = root.querySelector('[data-podium-announcement]');
        const results = root.querySelector('[data-podium-results]');
        function update() {
            clearTimeout(timer);
            if (disposed) return;
            const elapsedNow = Math.max(0, now() - Date.parse(p.step_at));
            const counting = (countdown > 0 || moving && !quiet) && elapsedNow < revealDelay;
            const second = Math.min(5, Math.max(1, Math.ceil((revealDelay - elapsedNow) / 1000)));
            if (countdownPanel) countdownPanel.hidden = !counting || elapsedNow < intro;
            if (countdownNumber && String(countdownNumber.textContent) !== String(second)) countdownNumber.textContent = second;
            // HTML hidden also protects names and scores from screen readers.
            if (currentWinner) currentWinner.hidden = counting;
            if (!counting) ceremony?.classList.remove('is-counting');
            if (!counting && !winnerSoundPlayed) {
                winnerSoundPlayed = true;
                if (!quiet && !document.hidden) sound(current.rank === 1 ? 'champion' : current.rank === 2 ? 'podium_second' : 'podium_third');
            }
            if (countdown && counting && elapsedNow >= intro && second !== lastSecond) {
                lastSecond = second;
                if (!quiet && !document.hidden) sound('tick');
            }
            const settled = !counting && (!moving || quiet || elapsedNow >= duration);
            if (settled) ceremony?.classList.remove('is-moving');
            if (elapsedNow >= intro || quiet) root.querySelector('.podium-opening')?.remove();
            if (results) results.hidden = !settled;
            const message = counting ? `Juara ${current.rank} akan diumumkan. Bersiaplah!` : `Juara ${current.rank}: ${current.nickname}. Selamat!`;
            if (announcement && announcement.textContent !== message) announcement.textContent = message;
            const ready = now() >= Date.parse(p.ready_at);
            if (button) button.disabled = !ready || isBusy();
            if (!settled || button && (!ready || isBusy())) timer = setTimeout(update, 100);
        }
        if (button) button.addEventListener('click', () => control('reveal'));
        if (moving && p.step === 1 && elapsed < intro) sound('podium_open');
        update();
        return {
            update,
            finish() { quiet = true; update(); },
            dispose() { disposed = true; clearTimeout(timer); }
        };
    }
    window.QuizPodium={mount};
})();
