const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const code=fs.readFileSync(path.join(__dirname,'../../public/js/music.js'),'utf8');
function setup(saved=null,failFirst=false){
    const sources=[],gains=[],timers=new Map(),handlers={},savedValues=[];
    let clock=1700000000000,context=null,fetches=0,id=0,controller;
    const parameter=()=>({value:0,setTargetAtTime(v){this.value=v;},setValueAtTime(v){this.value=v;},cancelScheduledValues(){}});
    const audio={state:'suspended',currentTime:0,destination:{},createGain(){const gain={gain:parameter(),connect(){}};gains.push(gain);return gain;},createBufferSource(){const s={connect(){},disconnect(){},start(t,offset){s.offset=offset;s.started=true;},stop(){s.stopped=true;}};sources.push(s);return s;},async decodeAudioData(){return {duration:32};}};
    const label={textContent:''},button={dataset:{},setAttribute(){},querySelector(){return label;},addEventListener(type,fn){handlers.toggle=fn;}};
    const input={value:25,setAttribute(){},addEventListener(type,fn){handlers.volume=fn;}};
    const document={hidden:false,querySelector(){return {dataset:{musicSrc:'/theme.wav'}};},querySelectorAll(selector){return selector==='[data-music-toggle]'?[button]:selector==='[data-music-volume]'?[input]:[];},addEventListener(type,fn){handlers[type]=fn;}};
    const window={addEventListener(type,fn){handlers[type]=fn;}};
    const localStorage={getItem(){return saved;},setItem(key,value){savedValues.push(JSON.parse(value));}};
    const fetch=async()=>{fetches++;return {ok:!(failFirst&&fetches===1),async arrayBuffer(){return new ArrayBuffer(4);}};};
    const setTimeout=(fn,delay)=>{timers.set(++id,{fn,delay});return id;};const clearTimeout=key=>timers.delete(key);
    vm.runInNewContext(code,{window,document,localStorage,fetch,setTimeout,clearTimeout,AbortController,Date:{now:()=>clock},console});
    const unlock=async()=>{context=audio;audio.state='running';controller.sync();return true;};
    controller=window.createQuizMusic({getContext:()=>context,ensureContext:unlock,isAvailable:()=>true});
    return {music:controller,audio,document,handlers,sources,gains,label,input,savedValues,timers,unlock,fetchCount:()=>fetches,now:()=>clock,advance(ms){clock+=ms;audio.currentTime+=ms/1000;}};
}
const flush=()=>new Promise(setImmediate);
test('music requires a gesture and an active question; repeated polling never stacks loops',async()=>{
    const s=setup();s.music.setState('question_active',s.now()+10000);await flush();assert.equal(s.fetchCount(),0);assert.equal(s.sources.length,0);
    await s.unlock();await flush();assert.equal(s.music.status().playing,true);assert.equal(s.sources.length,1);assert.equal(s.sources[0].loop,true);
    for(let i=0;i<5;i++)s.music.setState('question_active',s.now()+10000);
    assert.equal(s.sources.length,1);assert.equal(s.fetchCount(),1);
});
test('results pause, following question resumes position, finished resets playback',async()=>{
    const s=setup();await s.unlock();await flush();assert.equal(s.music.status().playing,false);
    s.music.setState('question_active',s.now()+20000);s.advance(6000);s.music.setState('question_result');assert.equal(s.music.status().playing,false);assert.equal(s.sources[0].stopped,true);
    s.music.setState('question_active',s.now()+20000);assert.equal(s.sources[1].offset,6);
    s.music.setState('finished');assert.equal(s.music.status().playing,false);s.music.setState('question_active',s.now()+20000);assert.equal(s.sources[2].offset,0);
});
test('music volume and mute persist independently and silence does not close the context',async()=>{
    const s=setup();await s.unlock();await flush();s.music.setState('question_active',s.now()+20000);
    s.music.setVolume(0);assert.equal(s.music.status().playing,false);assert.equal(s.audio.state,'running');
    s.music.setVolume(.15);assert.equal(s.music.status().playing,true);s.music.setEnabled(false);assert.equal(s.music.status().playing,false);
    const restored=setup(JSON.stringify(s.savedValues.at(-1)));await restored.unlock();await flush();assert.equal(restored.music.status().enabled,false);assert.equal(restored.music.status().volume,.15);assert.equal(restored.fetchCount(),0);
});
test('hidden tabs, local deadline and navigation stop music',async()=>{
    const s=setup();await s.unlock();await flush();s.music.setState('question_active',s.now()+5000);
    s.document.hidden=true;s.handlers.visibilitychange();assert.equal(s.music.status().playing,false);
    s.document.hidden=false;s.handlers.visibilitychange();assert.equal(s.music.status().playing,true);
    s.advance(5100);for(const timer of [...s.timers.values()])timer.fn();assert.equal(s.music.status().playing,false);
    s.music.setState('question_active',s.now()+5000);s.handlers.pagehide();assert.equal(s.music.status().playing,false);s.music.sync();assert.equal(s.music.status().playing,false);
});
test('failed load is visible and can be retried without repeated background requests',async()=>{
    const s=setup(null,true);await s.handlers.toggle();await flush();assert.equal(s.music.status().error,true);assert.equal(s.label.textContent,'Coba musik lagi');
    s.music.sync();await flush();assert.equal(s.fetchCount(),1);
    await s.handlers.toggle();await flush();assert.equal(s.music.status().loaded,true);assert.equal(s.music.status().error,false);assert.equal(s.fetchCount(),2);
});
test('effects can duck music without stopping its continuous source',async()=>{
    const s=setup();await s.unlock();await flush();s.music.setState('question_active',s.now()+5000);s.music.duck(.3);
    assert.equal(s.music.status().playing,true);assert.equal(s.sources.length,1);assert.equal(s.gains[1].gain.value,1);
});
