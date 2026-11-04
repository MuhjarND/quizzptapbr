(() => {
    'use strict';
    const formatter = new Intl.NumberFormat('id-ID');
    const integer = (value,fallback=0) => Number.isFinite(Number(value)) ? Math.max(0,Math.round(Number(value))) : fallback;
    function plan(rows) {
        return rows.map((row,index) => {
            const rank = index+1, score = integer(row.score);
            const earned = Math.min(score,integer(row.earned_score));
            const previousScore = Math.min(score,integer(row.previous_score,score-earned));
            const candidate = integer(row.previous_rank,rank);
            const previousRank = candidate >= 1 && candidate <= rows.length ? candidate : rank;
            return {...row,rank,score,earned_score:earned,previous_score:previousScore,previous_rank:previousRank,rank_change:previousRank-rank};
        });
    }
    function cueFor(rows,playerId) {
        if (playerId === null || playerId === undefined) return rows.some(row => row.rank_change !== 0) ? 'ranking' : rows.some(row => row.earned_score > 0) ? 'score_done' : null;
        const own = rows.find(row => Number(row.id) === Number(playerId));
        if (!own) return null;
        return own.rank_change > 0 ? 'rank_up' : own.rank_change < 0 ? 'rank_down' : own.earned_score > 0 ? 'score_done' : null;
    }
    function animate(container,rawRows,{enabled=false,playerId=null,sound=()=>{}}={}) {
        const rows = plan(rawRows), nodes = [...container.querySelectorAll('[data-standing-id]')];
        const byId = new Map(rows.map(row => [String(row.id),row]));
        const numberNodes = [...container.querySelectorAll('[data-score-number]')];
        const earnedNodes = [...container.querySelectorAll('[data-earned-number]')];
        let frame = null, moveTimer = null, endTimer = null, finished = false;
        const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        function finalize() {
            if (finished) return; finished = true;
            cancelAnimationFrame(frame); clearTimeout(moveTimer); clearTimeout(endTimer);
            container.dataset.animating = 'false';
            numberNodes.forEach(node => { const row=byId.get(node.dataset.scoreNumber); if(row)node.textContent=formatter.format(row.score); });
            earnedNodes.forEach(node => { node.textContent=`+${formatter.format(integer(node.dataset.earnedNumber))}`; });
            nodes.forEach(node => {
                node.style.transition='none'; node.style.transform=''; node.style.zIndex='';
                const row=byId.get(node.dataset.standingId); if(row)node.querySelector('[data-rank-number]').textContent=row.rank;
            });
        }
        if (!enabled || reduced || document.hidden || !nodes.length) { finalize(); return {finish:finalize}; }
        container.dataset.animating='true';
        const tops=nodes.map(node=>node.offsetTop);
        nodes.forEach((node,index)=>{
            const row=byId.get(node.dataset.standingId); if(!row)return;
            node.style.transition='none'; node.style.transform=`translateY(${tops[row.previous_rank-1]-tops[index]}px)`;
            node.style.zIndex=row.rank_change>0?'3':row.rank_change<0?'2':'1';
            node.querySelector('[data-rank-number]').textContent=row.previous_rank;
        });
        numberNodes.forEach(node=>{const row=byId.get(node.dataset.scoreNumber);if(row)node.textContent=formatter.format(row.previous_score);});
        earnedNodes.forEach(node=>{node.textContent='+0';});
        moveTimer=setTimeout(()=>{
            nodes.forEach(node=>{
                node.style.transition='transform 950ms cubic-bezier(.22,1,.36,1)'; node.style.transform='translateY(0)';
                const row=byId.get(node.dataset.standingId);if(row)node.querySelector('[data-rank-number]').textContent=row.rank;
            });
        },950);
        const start=performance.now(),gaining=rows.some(row=>row.earned_score>0);let lastTick=-1;
        function count(now) {
            if (finished)return;
            const progress=Math.max(0,Math.min(1,(now-start-550)/1350)),ease=1-Math.pow(1-progress,3);
            numberNodes.forEach(node=>{const row=byId.get(node.dataset.scoreNumber);if(row)node.textContent=formatter.format(Math.round(row.previous_score+(row.score-row.previous_score)*ease));});
            earnedNodes.forEach(node=>{node.textContent=`+${formatter.format(Math.round(integer(node.dataset.earnedNumber)*ease))}`;});
            const pulse=Math.floor((now-start-550)/145);
            if (gaining && progress>0 && progress<1 && pulse!==lastTick) { lastTick=pulse;sound('score_tick'); }
            if (progress<1)frame=requestAnimationFrame(count);
        }
        frame=requestAnimationFrame(count);
        endTimer=setTimeout(()=>{
            if(finished)return;
            finalize();const cue=cueFor(rows,playerId);if(cue)sound(cue);
            const announcement=container.querySelector('[data-standing-announcement]');
            if(announcement && rows[0])announcement.textContent=`Peringkat diperbarui. ${rows[0].nickname} memimpin dengan ${formatter.format(rows[0].score)} poin.`;
        },2050);
        return {finish:finalize};
    }
    window.ArenaStandings={plan,cueFor,animate};
})();
