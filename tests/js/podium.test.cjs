const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const source=fs.readFileSync(path.join(__dirname,'../../public/js/podium.js'),'utf8');
function setup({step=1,total=3,reduced=false,animate=true,elapsed=0}={}){
    let clock=elapsed,offset=0,busy=false,id=0;const timers=new Map(),sounds=[],actions=[];
    const button={disabled:true,addEventListener:(event,fn)=>button.click=fn},announcement={},results={hidden:true},ceremony={classList:{remove(){}}},opening={remove(){}};
    const root={innerHTML:'',querySelector:s=>s==='#reveal-winner'?(step<total?button:null):s==='[data-podium-results]'?(step===total?results:null):s==='[data-podium-announcement]'?announcement:s==='.podium-ceremony'?ceremony:opening};
    const setTimeout=(fn,delay)=>{timers.set(++id,{fn,at:clock+delay});return id;};
    const window={matchMedia:()=>({matches:reduced})},document={hidden:false};
    vm.runInNewContext(source,{window,document,setTimeout,clearTimeout:id=>timers.delete(id),Date,Math});
    const state={leaderboard:[],podium:{step,total,step_at:new Date(0).toISOString(),ready_at:new Date(step===1?3800:2200).toISOString(),complete:step===total,winners:Array.from({length:step},(_,i)=>({id:total-i,rank:total-i,nickname:'Player '+(total-i),score:900}))}};
    const mount=()=>window.QuizPodium.mount(root,state,{host:true,avatar:()=>'',esc:s=>s,points:s=>s,sound:c=>sounds.push(c),control:c=>actions.push(c),now:()=>clock+offset,isBusy:()=>busy,animate,leaderboard:()=>'',links:''});
    function advance(ms){clock+=ms;for(const [id,t] of [...timers])if(t.at<=clock&&timers.has(id)){timers.delete(id);t.fn();}}
    return {root,button,announcement,results,timers,sounds,actions,mount,advance,document,offset:v=>offset=v,busy:v=>busy=v};
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
