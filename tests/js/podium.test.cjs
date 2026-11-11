const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const source=fs.readFileSync(path.join(__dirname,'../../public/js/podium.js'),'utf8');
function setup({step=1,total=3,reduced=false,animate=true,elapsed=0,countdown=0}={}){
    let clock=elapsed,offset=0,busy=false,id=0;const timers=new Map(),sounds=[],actions=[];
    const button={disabled:true,addEventListener:(event,fn)=>button.click=fn},announcement={},results={hidden:true},ceremony={classList:{remove(){}}},opening={remove(){}};
    const countdownPanel = {}, countdownNumber = {}, currentWinner = {};
    const nodes = {'.podium-ceremony':ceremony,'.podium-opening':opening,'[data-podium-announcement]':announcement,'[data-podium-countdown]':countdownPanel,'[data-countdown-number]':countdownNumber,'.is-current .ceremony-winner':currentWinner};
    const root={innerHTML:'',querySelector:s=>s==='#reveal-winner'?(step<total?button:null):s==='[data-podium-results]'?(step===total?results:null):nodes[s]};
    const setTimeout=(fn,delay)=>{timers.set(++id,{fn,at:clock+delay});return id;};
    const window={matchMedia:()=>({matches:reduced})},document={hidden:false};
    vm.runInNewContext(source,{window,document,setTimeout,clearTimeout:id=>timers.delete(id),Date,Math});
    const state={leaderboard:[],podium:{step,total,countdown_seconds:countdown,step_at:new Date(0).toISOString(),ready_at:new Date((step===1?3800:2200)+countdown*1000).toISOString(),complete:step===total,winners:Array.from({length:step},(_,i)=>({id:total-i,rank:total-i,nickname:'Player '+(total-i),score:900}))}};
    const mount=()=>window.QuizPodium.mount(root,state,{host:true,avatar:()=>'',esc:s=>s,points:s=>s,sound:c=>sounds.push(c),control:c=>actions.push(c),now:()=>clock+offset,isBusy:()=>busy,animate,leaderboard:()=>'',links:''});
    function advance(ms){clock+=ms;for(const [id,t] of [...timers])if(t.at<=clock&&timers.has(id)){timers.delete(id);t.fn();}}
    return {root,button,announcement,results,timers,sounds,actions,mount,advance,document,countdownPanel,countdownNumber,currentWinner,offset:v=>offset=v,busy:v=>busy=v};
}
test('opening precedes third place and next winner stays locked until server ready time',()=>{
    const s=setup();s.mount();assert.deepEqual(s.sounds,['podium_open']);assert.equal(s.button.disabled,true);
    assert.match(s.root.innerHTML,/data-winner-id="3"/);assert.doesNotMatch(s.root.innerHTML,/data-winner-id="[12]"/);
    s.advance(1600);assert.deepEqual(s.sounds,['podium_open','podium_third']);s.advance(1400);assert.equal(s.button.disabled,true);
    s.advance(1100);assert.equal(s.button.disabled,false);s.button.click();assert.deepEqual(s.actions,['reveal']);
});
test('second and champion have distinct cues and full results wait for champion animation',()=>{
    const second=setup({step:2});second.mount();assert.deepEqual(second.sounds,['podium_second']);
    const first=setup({step:3});first.mount();assert.deepEqual(first.sounds,['champion']);assert.equal(first.results.hidden,true);
    assert.match(first.root.innerHTML,/champion-crown/);first.advance(3000);assert.equal(first.results.hidden,false);
});
test('refresh, reduced motion and late join do not replay a ceremony or its sound',()=>{
    for(const opts of [{animate:false},{reduced:true},{elapsed:5000}]){const s=setup(opts);s.mount();assert.equal(s.sounds.length,0);assert.match(s.announcement.textContent,/Juara 3/);}
});
test('hiding or leaving cancels pending winner cues and old UI updates',()=>{
    for(const dispose of [false,true]){const s=setup(),animation=s.mount();dispose?animation.dispose():animation.finish();s.advance(10000);assert.deepEqual(s.sounds,['podium_open']);if(dispose)assert.equal(s.timers.size,0);}
});
test('clock correction cannot strand the next button and in-flight controls remain locked',()=>{
    const s=setup(),animation=s.mount();s.advance(3000);s.offset(-500);s.advance(900);assert.equal(s.button.disabled,true);
    s.advance(600);assert.equal(s.button.disabled,false);s.busy(true);animation.update();assert.equal(s.button.disabled,true);
    s.busy(false);animation.update();assert.equal(s.button.disabled,false);animation.dispose();assert.equal(s.timers.size,0);
});
test('every winner waits five seconds, then reveals with its own sound',()=>{
    for (const step of [1,2,3]) {
        const s=setup({step,countdown:5});s.mount();
        if(step===1)s.advance(1600);
        assert.equal(s.countdownNumber.textContent,5);assert.equal(s.currentWinner.hidden,true);
        for(const number of [4,3,2,1]){s.advance(1000);assert.equal(s.countdownNumber.textContent,number);assert.equal(s.currentWinner.hidden,true);}
        s.advance(1000);assert.equal(s.countdownPanel.hidden,true);assert.equal(s.currentWinner.hidden,false);
        assert.equal(s.sounds.at(-1),step===1?'podium_third':step===2?'podium_second':'champion');
        s.advance(4000);if(step<3)assert.equal(s.button.disabled,false);else assert.equal(s.results.hidden,false);
    }
});
test('refresh and reduced motion resume server countdown without early disclosure or replay',()=>{
    for(const reduced of [false,true]){
        const s=setup({step:2,countdown:5,elapsed:2000,animate:false,reduced});const animation=s.mount();
        assert.equal(s.countdownNumber.textContent,3);assert.equal(s.currentWinner.hidden,true);
        animation.finish();assert.equal(s.currentWinner.hidden,true);s.advance(3000);
        assert.equal(s.currentWinner.hidden,false);assert.deepEqual(s.sounds,[]);animation.dispose();assert.equal(s.timers.size,0);
    }
});
