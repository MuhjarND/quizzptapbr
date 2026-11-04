(() => {
    'use strict';
    const crown = '<svg viewBox="0 0 64 52" aria-hidden="true"><path d="M8 12 21 25 32 5 43 25 56 12 50 43H14Z" fill="currentColor"/><path d="M17 49h30" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg>';
    function mount(root,state,{host,avatar,esc,points,sound,control,now,isBusy=()=>false,animate=false,leaderboard,links}) {
        const p=state.podium, current=p.winners[p.winners.length-1], intro=p.step===1?1600:0;
        const elapsed=Math.max(0,now()-Date.parse(p.step_at)), duration=intro+(p.complete?3000:1400);
        const moving=animate&&!document.hidden&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches&&elapsed<duration;
        const slots=Array.from({length:p.total},(_,i)=>i+1);
        const order=p.total===3?[2,1,3]:p.total===2?[2,1]:[1];
        const confetti=Array.from({length:36},(_,i)=>`<i style="--x:${(i*29)%100}%;--drift:${(i%7-3)*36}px;--delay:${i%9*65}ms;--turn:${i*47}deg"></i>`).join('');
        root.innerHTML=`<section class="podium-ceremony ${moving?'is-moving':''} ${p.complete?'has-champion':''}" style="--reveal-delay:${Math.max(0,intro-elapsed)}ms;--opening-delay:${-elapsed}ms" aria-labelledby="podium-heading">
            <header class="podium-header"><p class="podium-eyebrow">QUIZ by PTA Pabar · Podium juara</p><h1 id="podium-heading">${p.complete?'Inilah sang juara!':'Tepuk tangan untuk para juara'}</h1><p>${p.complete?'Usaha terbaik, momen yang layak dirayakan.':'Dari jawaban terbaik, menuju panggung juara.'}</p><span class="podium-counter">${p.step} dari ${slots.length} juara diumumkan</span></header>
            <div class="ceremony-stage" data-podium-stage>
                <div class="stage-beam beam-left" aria-hidden="true"></div><div class="stage-beam beam-right" aria-hidden="true"></div><div class="stage-orbit" aria-hidden="true"></div>
                ${p.complete?`<div class="champion-confetti" aria-hidden="true">${confetti}</div>`:''}
                <div class="ceremony-podium podium-size-${p.total}">${order.map(rank=>{
                    const row=p.winners.find(w=>w.rank===rank),fresh=row&&row.id===current.id;
                    return `<div class="ceremony-slot slot-${rank} ${row?'is-revealed':'is-secret'} ${fresh?'is-current':''}" data-podium-rank="${rank}">${row?`<div class="ceremony-winner" data-winner-id="${row.id}">${rank===1?`<div class="champion-halo" aria-hidden="true"></div><div class="champion-crown">${crown}</div>`:''}<span class="podium-place-label">Juara ${rank}</span><div class="ceremony-avatar">${avatar(row)}</div><strong class="winner-name">${esc(row.nickname)}</strong><span class="winner-score">${points(row.score)} <small>poin</small></span></div>`:'<div class="ceremony-secret" aria-label="Juara belum diumumkan"><span aria-hidden="true">?</span><small>Belum diumumkan</small></div>'}<div class="ceremony-step"><span>${rank}</span><small>${rank===1?'JUARA PERTAMA':rank===2?'JUARA KEDUA':'JUARA KETIGA'}</small></div></div>`;
                }).join('')}</div>
                ${moving&&p.step===1?'<div class="podium-opening" aria-hidden="true"><div class="stage-curtain curtain-left"></div><div class="stage-curtain curtain-right"></div><div class="opening-title">Siapkan tepuk tangan!<span>Panggung juara akan dibuka</span></div></div>':''}
            </div>
            <div class="podium-announcement" role="status" data-podium-announcement>${moving?'Mengumumkan juara…':`Juara ${current.rank}: ${esc(current.nickname)}. Selamat!`}</div>
            <div class="ceremony-controls">${!p.complete?(host?'<button class="btn btn-primary btn-lg" id="reveal-winner" disabled>Juara selanjutnya →</button><p>Setiap juara mendapat momennya.</p>':'<p>Menunggu host mengumumkan juara selanjutnya…</p>'):'<p>Terima kasih sudah bermain. Kalian luar biasa!</p>'}</div>
            ${p.complete?`<div data-podium-results ${moving?'hidden':''}>${leaderboard(state.leaderboard,'Peringkat akhir')}<div class="finish-links">${links}</div></div>`:''}
        </section>`;
        let disposed=false,done=false,readyTimer=null;const timers=[];
        const later=(fn,delay)=>timers.push(setTimeout(()=>{if(!disposed)fn();},Math.max(0,delay)));
        const button=root.querySelector('#reveal-winner');
        function unlockButton(){
            clearTimeout(readyTimer);
            if(!button||disposed)return;
            const remaining=Date.parse(p.ready_at)-now();button.disabled=remaining>0||isBusy();
            // Clock offset can change on polling. Recheck an early timer rather
            // than leaving the host locked after its single callback has fired.
            if(button.disabled)readyTimer=setTimeout(unlockButton,Math.max(80,Math.min(1000,remaining+80)));
        }
        function finish(){
            if(done)return;done=true;
            timers.forEach(clearTimeout);
            root.querySelector('.podium-ceremony')?.classList.remove('is-moving');
            root.querySelector('.podium-opening')?.remove();
            const results=root.querySelector('[data-podium-results]');if(results)results.hidden=false;
            const announcement=root.querySelector('[data-podium-announcement]');if(announcement)announcement.textContent=`Juara ${current.rank}: ${current.nickname}. Selamat!`;
            unlockButton();
        }
        if(button){button.addEventListener('click',()=>control('reveal'));unlockButton();}
        if(moving){
            if(p.step===1&&elapsed<1600)sound('podium_open');
            const cue=()=>sound(current.rank===1?'champion':current.rank===2?'podium_second':'podium_third');
            if(elapsed<intro)later(cue,intro-elapsed);else cue();
            later(finish,duration-elapsed);
        }else finish();
        return {update:unlockButton,finish(){finish();},dispose(){disposed=true;clearTimeout(readyTimer);timers.forEach(clearTimeout);finish();}};
    }
    window.QuizPodium={mount};
})();
