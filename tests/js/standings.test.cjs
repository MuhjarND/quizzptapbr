const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const source=fs.readFileSync(path.join(__dirname,'../../public/js/standings.js'),'utf8');
const rows=[{id:2,nickname:'Bima',score:1750,previous_score:800,earned_score:950,previous_rank:2},{id:1,nickname:'Alya',score:900,previous_score:900,earned_score:0,previous_rank:1}];
function setup(reduced=false){
    let clock=0,id=0;const timers=new Map(),frames=new Map(),sounds=[];
    const window={matchMedia:()=>({matches:reduced})},document={hidden:false};
    const setTimeout=(fn,delay)=>{timers.set(++id,{fn,at:clock+delay});return id;};const clearTimeout=id=>timers.delete(id);
    const requestAnimationFrame=fn=>{frames.set(++id,fn);return id;};const cancelAnimationFrame=id=>frames.delete(id);
    vm.runInNewContext(source,{window,document,Intl,performance:{now:()=>clock},setTimeout,clearTimeout,requestAnimationFrame,cancelAnimationFrame});
    const numbers=rows.map(row=>({dataset:{scoreNumber:String(row.id)},textContent:String(row.score)}));
    const earned=[{dataset:{earnedNumber:'950'},textContent:'+950'}];
    const nodes=rows.map((row,i)=>{const rank={textContent:String(i+1)};return {dataset:{standingId:String(row.id)},style:{},offsetTop:i*106,querySelector:()=>rank,rank};});
    const announcement={textContent:''},container={dataset:{},querySelectorAll(selector){return selector==='[data-standing-id]'?nodes:selector==='[data-score-number]'?numbers:earned;},querySelector(){return announcement;}};
    function advance(ms){clock+=ms;const pending=[...frames.values()];frames.clear();pending.forEach(fn=>fn(clock));for(const [key,timer] of [...timers])if(timer.at<=clock&&timers.has(key)){timers.delete(key);timer.fn();}}
    return {api:window.ArenaStandings,container,nodes,numbers,earned,sounds,advance,timers,frames,document};
}
test('server order is retained and delta describes actual previous standings',()=>{
    const s=setup(),plan=s.api.plan(rows);assert.equal(plan[0].id,2);assert.equal(plan[0].rank_change,1);assert.equal(plan[1].rank_change,-1);
    assert.equal(s.api.cueFor(plan,2),'rank_up');assert.equal(s.api.cueFor(plan,1),'rank_down');assert.equal(s.api.cueFor(plan,null),'ranking');
});
test('rows start at old positions and totals count upward before landing on server values',()=>{
    const s=setup();s.api.animate(s.container,rows,{enabled:true,playerId:2,sound:cue=>s.sounds.push(cue)});
    assert.equal(s.numbers[0].textContent,'800');assert.equal(s.nodes[0].style.transform,'translateY(106px)');assert.equal(s.nodes[1].style.transform,'translateY(-106px)');
    s.advance(1000);const value=Number(s.numbers[0].textContent.replaceAll('.',''));assert.ok(value>800&&value<1750);assert.ok(s.sounds.includes('score_tick'));
    s.advance(1200);assert.equal(s.numbers[0].textContent,'1.750');assert.equal(s.nodes[0].rank.textContent,1);assert.equal(s.container.dataset.animating,'false');assert.equal(s.sounds.filter(x=>x==='rank_up').length,1);
});
test('refresh and reduced-motion show final values immediately without replaying sounds',()=>{
    for(const reduced of [false,true]){const s=setup(reduced);s.api.animate(s.container,rows,{enabled:reduced,sound:cue=>s.sounds.push(cue)});assert.equal(s.numbers[0].textContent,'1.750');assert.equal(s.frames.size,0);assert.equal(s.sounds.length,0);}
});
test('advancing or hiding the page cancels animation and leaves accurate final scores',()=>{
    const s=setup(),animation=s.api.animate(s.container,rows,{enabled:true,playerId:1,sound:cue=>s.sounds.push(cue)});s.advance(1000);animation.finish();
    const count=s.sounds.length;s.advance(5000);assert.equal(s.numbers[0].textContent,'1.750');assert.equal(s.timers.size,0);assert.equal(s.frames.size,0);assert.equal(s.sounds.length,count);
});
