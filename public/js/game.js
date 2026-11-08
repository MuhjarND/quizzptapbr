(() => {
    'use strict';
    const config = JSON.parse(document.querySelector('#game-config').textContent);
    const root = document.querySelector('#game-root'), errorBox = document.querySelector('#game-error');
    const connection = document.querySelector('#connection-status'), sync = document.querySelector('#sync-status');
    let state, versionKey = '', pollTimer, ticking, stopped = false, polling = false, busy = false, offset = 0, localAnswer = null;
    const countdownTicks = new Set();
    let standingsAnimation = null, podiumAnimation = null, focusPodium = false;
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const icon = name => ({users:'♧',trophy:'★',clock:'◷',game:'◇',check:'✓'}[name] || '');
    const points = value => Number(value).toLocaleString('id-ID');
    const sound = name => window.ArenaSound?.play(name);
    function characterAvatar(player, className = '') {
        const character = config.characters[player.character] || config.characters.nala;
        return `<img class="character-avatar ${className}" src="${esc(character.image)}" alt="Karakter ${esc(character.name)}" width="64" height="64" style="--character-color:${esc(character.color)}">`;
    }
    function showError(message) { errorBox.textContent = message; errorBox.hidden = false; }
    async function request(url, data) {
        const controller = new AbortController(), timeout = setTimeout(() => controller.abort(), 8000);
        let response;
        try { response = await fetch(url, {signal:controller.signal, method:data ? 'POST' : 'GET', credentials:'same-origin', cache:'no-store',
            headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
            body:data ? JSON.stringify(data) : undefined}); }
        catch (error) { throw new Error(error.name === 'AbortError' ? 'Server belum merespons. Coba lagi.' : 'Koneksi terputus. Periksa jaringan Anda.'); }
        finally { clearTimeout(timeout); }
        let json; try { json = await response.json(); } catch (_) { throw new Error('Koneksi ke arena terganggu. Coba muat ulang halaman.'); }
        if (!response.ok) {
            const messages = {401:'Sesi host berakhir. Masuk kembali melalui halaman login.',403:'Akses sesi tidak tersedia. Gunakan browser yang sama saat bergabung.',419:'Sesi browser berakhir. Muat ulang halaman sebelum melanjutkan.',429:'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.'};
            throw new Error(messages[response.status] || (json.errors ? Object.values(json.errors).flat()[0] : json.message) || 'Aksi belum berhasil. Coba lagi.');
        }
        return json;
    }
    function leaderboard(rows, heading = 'Peringkat sementara') {
        return `<section class="leaderboard-panel"><h2>${esc(heading)}</h2>${rows.length ? rows.map((p,i) => `<div class="leaderboard-row ${p.id === config.playerId ? 'me' : ''}"><span class="rank-number">${i+1}</span>${characterAvatar(p,'leaderboard-character')}<strong>${esc(p.nickname)}${p.id === config.playerId ? ' (Anda)' : ''}</strong><span class="points">${points(p.score)} poin</span></div>`).join('') : '<p class="muted">Belum ada peserta.</p>'}</section>`;
    }
    function podium(rows) {
        return `<div class="podium">${[1,0,2].map(i => rows[i] ? `<div class="podium-player place-${i+1}"><span class="podium-avatar">${characterAvatar(rows[i])}</span><strong>${esc(rows[i].nickname)}</strong><span>${points(rows[i].score)} poin</span><div class="podium-step">${i+1}</div></div>` : '').join('')}</div>`;
    }
    function renderRoundStandings(s,animateResult) {
        const rows=window.ArenaStandings.plan(s.leaderboard),own=rows.find(row=>row.id===config.playerId);
        const feedback=s.my_answer?.is_correct?'Jawabanmu tepat!':s.my_answer?'Belum tepat kali ini.':'Waktu habis, belum menjawab.';
        const movement=row=>row.rank_change>0?`<span class="rank-change up"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3 10 5-5 5 5"/></svg>Naik ${row.rank_change}</span>`:row.rank_change<0?`<span class="rank-change down"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3 6 5 5 5-5"/></svg>Turun ${Math.abs(row.rank_change)}</span>`:'<span class="rank-change same">Posisi tetap</span>';
        root.innerHTML=`<section class="round-scoreboard"><header class="standings-header"><div><p class="round-progress">Pertanyaan ${s.position} dari ${s.total} selesai</p><h1>Peringkat sementara</h1><p class="standings-subtitle">Lihat tambahan poin dan perubahan posisi di arena.</p></div><span class="round-participant-count">${s.participants.length} peserta</span></header>${!config.host&&own?`<div class="round-personal ${s.my_answer?.is_correct?'correct':'neutral'}"><div class="personal-identity">${characterAvatar(own)}<div><strong>${feedback}</strong><span>Peringkat ${own.rank} dari ${s.participants.length} peserta</span></div></div><div class="personal-earned"><strong aria-hidden="true" data-earned-number="${own.earned_score}">+${points(own.earned_score)}</strong><span class="visually-hidden">Tambahan ${points(own.earned_score)} poin</span><small aria-hidden="true">poin soal ini</small></div></div>`:''}<div class="round-answer">✓ Jawaban benar: <strong>${esc(s.question.choices[s.question.correct_index])}</strong></div><div class="standings-columns" aria-hidden="true"><span>#</span><span>Peserta</span><span class="earned-heading">Soal ini</span><span>Total poin</span></div><ol class="standings-list" aria-label="Poin sementara seluruh peserta">${rows.map(row=>`<li class="standing-row ${row.id===config.playerId?'me':''}" data-standing-id="${row.id}" data-direction="${row.rank_change>0?'up':row.rank_change<0?'down':'same'}"><span class="standing-rank" aria-hidden="true" data-rank-number>${row.rank}</span><span class="visually-hidden">Peringkat ${row.rank}.</span><div class="standing-person">${characterAvatar(row)}<div><strong>${esc(row.nickname)}${row.id===config.playerId?' <span class="you-label">Anda</span>':''}</strong>${movement(row)}</div></div><div class="standing-earned-slot">+${points(row.earned_score)}<span>poin</span></div><div class="standing-points"><strong aria-hidden="true" data-score-number="${row.id}">${points(row.score)}</strong><span class="visually-hidden">${points(row.score)} total poin</span><small aria-hidden="true">poin</small><span class="mobile-earned">+${points(row.earned_score)} soal ini</span></div></li>`).join('')}</ol><div class="standing-announcement visually-hidden" role="status" data-standing-announcement></div><div class="game-controls">${config.host?`<button class="btn btn-primary btn-lg" id="next-question">${s.position>=s.total?'Tampilkan podium':'Pertanyaan berikutnya'} →</button>`:'<p>Menunggu host melanjutkan permainan…</p>'}</div></section>`;
        standingsAnimation=window.ArenaStandings.animate(root,rows,{enabled:animateResult,playerId:config.playerId,sound});
        document.querySelector('#next-question')?.addEventListener('click',()=>control('next'));
    }
    function render({animateResult=false,animatePodium=false}={}) {
        standingsAnimation?.finish();standingsAnimation=null;
        podiumAnimation?.dispose();podiumAnimation=null;
        const s = state;
        if (s.status === 'lobby') {
            root.innerHTML = `<section class="lobby-panel"><h1>${config.host ? 'Arena siap. Ajak semua masuk!' : 'Anda sudah di arena!'}</h1><p class="muted">${config.host ? 'Buka halaman Gabung permainan, lalu masukkan PIN di bawah ini.' : 'Tunggu host memulai permainan. Siapkan jawaban terbaik Anda.'}</p><div class="pin-display"><span>${icon('game')}</span><div>PIN permainan<br><strong>${esc(s.pin)}</strong></div></div><div class="lobby-participants"><h2><span id="player-count">${s.participants.length}</span> peserta sudah bergabung</h2><div class="participant-chips">${s.participants.length ? s.participants.map(p => `<div class="participant-chip">${characterAvatar(p,'lobby-character')}${esc(p.nickname)}</div>`).join('') : '<p class="lobby-empty">Peserta akan muncul di sini secara otomatis.</p>'}</div></div><div class="lobby-actions">${config.host ? `<button class="btn btn-primary btn-lg" id="start-game" ${!s.participants.length ? 'disabled' : ''}>Mulai permainan →</button><p>${s.total} pertanyaan · Pastikan semua peserta sudah bergabung.</p>` : '<div class="waiting-text"><span class="loader"></span> Menunggu host memulai…</div>'}</div></section>`;
            document.querySelector('#start-game')?.addEventListener('click', () => control('start')); return;
        }
        if(s.podium){
            const links=config.host?`<a class="btn btn-primary" href="${esc(config.resultUrl)}">Lihat detail hasil →</a><a class="btn btn-light" href="${esc(config.dashboardUrl)}">Kembali ke dasbor</a>`:`<a class="btn btn-primary" href="${esc(config.joinUrl)}">Gabung permainan lain →</a>`;
            podiumAnimation=window.QuizPodium.mount(root,s,{host:config.host,avatar:characterAvatar,esc,points,sound,control,now:()=>Date.now()+offset,isBusy:()=>busy,animate:animatePodium,leaderboard,links,appName:config.appName});
            document.querySelector('#finish-game')?.setAttribute('hidden','');
            if(focusPodium){const heading=document.querySelector('#podium-heading');heading.setAttribute('tabindex','-1');heading.focus({preventScroll:true});focusPodium=false;}
            if(s.status==='finished'){stopped=true;clearTimeout(pollTimer);clearInterval(ticking);sync.textContent='Hasil tersimpan';}
            return;
        }
        if (s.status === 'finished') {
            root.innerHTML = `<section class="finished-panel"><div class="result-celebration"><div class="result-trophy">${icon('trophy')}</div><h2>Arena selesai. Kalian luar biasa!</h2><p class="muted">Setiap jawaban adalah kesempatan untuk belajar.</p>${podium(s.leaderboard)}</div>${leaderboard(s.leaderboard, 'Peringkat akhir')}<div class="finish-links">${config.host ? `<a class="btn btn-primary" href="${esc(config.resultUrl)}">Lihat detail hasil →</a><a class="btn btn-light" href="${esc(config.dashboardUrl)}">Kembali ke dasbor</a>` : `<a class="btn btn-primary" href="${esc(config.joinUrl)}">Gabung permainan lain →</a>`}</div></section>`;
            stopped = true; clearTimeout(pollTimer); clearInterval(ticking);
            const finish = document.querySelector('#finish-game'); if (finish) finish.hidden = true; sync.textContent = 'Hasil tersimpan'; return;
        }
        if(s.final_pending){
            root.innerHTML=`<section class="podium-waiting"><img src="${esc(config.trophyUrl)}" alt="" width="125" height="125"><p class="podium-eyebrow">Semua ${s.total} soal sudah selesai</p><h1>Panggung juara sudah siap!</h1><p>Hasil akhir masih menjadi kejutan. Saatnya memberi panggung untuk para juara.</p>${config.host?'<button class="btn btn-primary btn-lg" id="next-question">Lihat hasil podium →</button>':'<div class="waiting-text"><span class="loader"></span> Menunggu host membuka podium…</div>'}</section>`;
            document.querySelector('#next-question')?.addEventListener('click',()=>control('next'));return;
        }
        if(s.status==='question_result'){renderRoundStandings(s,animateResult);return;}
        const q = s.question, own = s.my_answer;
        const selected = own ? own.choice_index : (localAnswer?.questionId === q.id ? localAnswer.choice : null);
        const locked = !config.host && selected !== null;
        root.innerHTML = `<section class="question-panel"><div class="question-top"><span>Pertanyaan <strong>${s.position}</strong> dari ${s.total}</span><div class="timer" role="timer" aria-label="Waktu tersisa"><span>◷</span><span id="timer-text">--</span></div></div><div class="timer-track"><div id="timer-bar" class="timer-bar"></div></div><h1>${esc(q.text)}</h1>${q.image ? `<img class="game-question-image" src="${esc(config.storageUrl+'/'+q.image)}" alt="Gambar pendukung pertanyaan">` : ''}<div class="answer-grid">${q.choices.map((text,i) => `<button type="button" class="answer-tile ${selected === i ? 'selected' : ''}" data-choice="${i}" ${config.host || locked ? 'disabled' : ''}><span class="answer-letter">${String.fromCharCode(65+i)}</span><span>${esc(text)}</span></button>`).join('')}</div><div class="answer-foot">${config.host ? '<span id="answer-count"></span>' : locked ? '✓ Jawaban terkunci. Tunggu hasilnya.' : 'Pilih satu jawaban. Jawaban yang terkirim akan terkunci.'}</div></section>`;
        document.querySelectorAll('[data-choice]').forEach(button => button.addEventListener('click', () => answer(Number(button.dataset.choice))));
        document.querySelector('#next-question')?.addEventListener('click', () => control('next'));
        updateCounts(); tick();
    }
    function updateCounts() {
        const count = document.querySelector('#answer-count');
        if (count) count.textContent = `${state.answer_count} dari ${state.participants.length} peserta sudah menjawab`;
    }
    function applyState(next) {
        if (state && Number(next.version) < Number(state.version)) return;
        const animateResult=!!state&&next.status==='question_result'&&(state.status!==next.status||state.question?.id!==next.question?.id);
        const animatePodium=!!state&&!!next.podium&&state.podium?.step!==next.podium.step;
        window.QuizMusic?.setState(next.status==='podium'?'finished':next.status,next.deadline ? Date.parse(next.deadline)-offset : null);
        if (state) {
            const changedQuestion = state.question?.id !== next.question?.id;
            if (next.status === 'finished' && state.status !== 'finished'&&!next.podium) sound('finish');
            else if (next.status === 'question_active' && (state.status !== next.status || changedQuestion)) { countdownTicks.clear(); sound('question'); }
            else if (next.status === 'question_result' && !next.final_pending && (state.status !== next.status || changedQuestion)) {
                sound(config.host ? 'reveal' : next.my_answer?.is_correct ? 'correct' : 'incorrect');
            } else if (config.host && next.status === 'lobby' && next.participants.length > state.participants.length) sound('join');
        }
        if (state && state.question?.id !== next.question?.id) localAnswer = null;
        state = next;
        const newKey = JSON.stringify([next.status,next.version,next.question?.id,next.my_answer,localAnswer,next.status === 'lobby' ? next.participants : null]);
        if (newKey !== versionKey) { versionKey = newKey; render({animateResult,animatePodium}); }
        else updateCounts();
        podiumAnimation?.update();
    }
    function tick() {
        if (!state || state.status !== 'question_active') return;
        const remaining = Math.max(0, Date.parse(state.deadline) - (Date.now() + offset));
        if (remaining === 0) window.QuizMusic?.setState('question_result');
        const seconds = Math.ceil(remaining/1000), countdownKey = `${state.question.id}:${seconds}`;
        if (seconds >= 1 && seconds <= 5 && !countdownTicks.has(countdownKey)) { countdownTicks.add(countdownKey); sound('tick'); }
        const timer = document.querySelector('#timer-text'), bar = document.querySelector('#timer-bar');
        if (timer) { const seconds = Math.ceil(remaining/1000); timer.textContent = `00:${String(seconds).padStart(2,'0')}`; timer.parentElement.classList.toggle('urgent', seconds <= 5); }
        if (bar) bar.style.width = `${Math.min(100, remaining / (state.question.duration * 10))}%`;
        if (remaining === 0 && !config.host) document.querySelectorAll('[data-choice]').forEach(button => button.disabled = true);
    }
    async function poll() {
        if (polling || stopped || document.hidden) return;
        polling = true; const before = Date.now(); let delay = 1500;
        try {
            const next = await request(config.stateUrl); offset = Date.parse(next.server_now) - ((before + Date.now()) / 2);
            connection.hidden = true; sync.textContent = 'Tersinkron otomatis'; applyState(next);
        } catch (error) { delay = 5000; connection.textContent = `${error.message} Mencoba menghubungkan kembali…`; connection.hidden = false; sync.textContent = 'Menghubungkan kembali…'; }
        finally { polling = false; if (!stopped && !document.hidden) pollTimer = setTimeout(poll, delay); }
    }
    async function control(action) {
        if (busy || !state) return; busy = true; errorBox.hidden = true;
        focusPodium=action==='reveal'||action==='next'&&state.final_pending;
        document.querySelectorAll('#start-game,#next-question,#finish-game,#reveal-winner').forEach(b => { b.disabled = true; b.setAttribute('aria-busy','true'); });
        // A poll can observe this transition before the POST returns. Applying
        // the same version again must not replace or restart the ceremony.
        try { const next = await request(config.controlUrl, {action,version:state.version}); applyState(next); }
        catch (error) { focusPodium=false;showError(error.message); }
        finally { busy = false; document.querySelectorAll('#start-game,#next-question,#finish-game').forEach(b => { b.disabled = b.id === 'start-game' && !state.participants.length; b.removeAttribute('aria-busy'); });document.querySelector('#reveal-winner')?.removeAttribute('aria-busy');podiumAnimation?.update(); }
    }
    async function answer(choice) {
        if (busy || state.my_answer || localAnswer) return; busy = true; errorBox.hidden = true;
        const questionId=state.question.id;
        document.querySelectorAll('[data-choice]').forEach(b => b.disabled = true);
        try {
            await request(config.answerUrl, {question_id:questionId,choice_index:choice});
            // Polling may already have opened the scoreboard while this response
            // was in flight. Do not replace that screen or cancel its animation.
            if(state.status==='question_active'&&state.question.id===questionId){
                sound('answer');localAnswer = {questionId,choice}; versionKey = ''; render();
            }
            clearTimeout(pollTimer); poll();
        } catch (error) { showError(error.message); if(state.status==='question_active'&&state.question.id===questionId){versionKey = ''; render();} }
        finally { busy = false; }
    }
    document.querySelector('#finish-game')?.addEventListener('click', () => window.arenaConfirm('Akhiri permainan?', 'Permainan akan ditutup untuk semua peserta. Jawaban yang sudah terkirim tetap dihitung dan hasil akan disimpan.', 'Akhiri permainan', () => control('finish')));
    document.addEventListener('visibilitychange', () => { clearTimeout(pollTimer); if(document.hidden){standingsAnimation?.finish();podiumAnimation?.finish();} if (!document.hidden && !stopped) poll(); });
    window.addEventListener('offline', () => { connection.hidden = false; connection.textContent = 'Koneksi terputus. Periksa jaringan Anda. Jawaban baru belum dapat dikirim.'; });
    window.addEventListener('online', () => { clearTimeout(pollTimer); poll(); });
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(pollTimer); clearInterval(ticking);standingsAnimation?.finish();podiumAnimation?.dispose(); });
    window.addEventListener('pageshow', e => { if (e.persisted) window.location.reload(); });
    ticking = setInterval(tick, 100); poll();
})();
