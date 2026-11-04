const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const source=fs.readFileSync(path.join(__dirname,'../../public/js/sound.js'),'utf8');
function setup(saved=null,unsupported=false){
    const nodes=[],events={},savedValues=[];
    const param=()=>({value:0,setValueAtTime(){},linearRampToValueAtTime(){},exponentialRampToValueAtTime(){},setTargetAtTime(value){this.value=value;}});
    class AudioContext {
        constructor(){this.state='suspended';this.currentTime=0;this.destination={};}
        createGain(){return {gain:param(),connect(){},disconnect(){}};}
        createOscillator(){const n={frequency:param(),connect(){},disconnect(){},start(time){n.started=time;},stop(time){n.stopped=time;}};nodes.push(n);return n;}
        async resume(){this.state='running';}
        async close(){this.state='closed';}
    }
    const document={hidden:false,querySelectorAll(){return [];},addEventListener(type,fn){events[type]=fn;}};
    const window={AudioContext:unsupported?undefined:AudioContext,addEventListener(type,fn){events[type]=fn;}};
    const localStorage={getItem(){return saved;},setItem(key,value){savedValues.push(JSON.parse(value));}};
    vm.runInNewContext(source,{window,document,localStorage,console});
    return {audio:window.ArenaSound,nodes,document,events,savedValues};
}
test('no autoplay before gesture, effects emit scheduled audio after unlock',async()=>{
    const {audio,nodes}=setup();assert.equal(audio.play('question'),false);assert.equal(nodes.length,0);
    assert.equal(await audio.unlock(),true);assert.equal(audio.play('question'),true);assert.ok(nodes.length>0);
    for(const node of nodes){assert.ok(node.started>=0);assert.ok(node.stopped>node.started);}
});
test('mute, zero volume and hidden tab suppress new sound',async()=>{
    const {audio,nodes,document}=setup();await audio.unlock();audio.setEnabled(false);
    assert.equal(audio.play('finish'),false);audio.setEnabled(true);audio.setVolume(0);
    assert.equal(audio.play('correct'),false);audio.setVolume(.5);document.hidden=true;
    assert.equal(audio.play('tick'),false);assert.equal(nodes.length,0);
});
test('volume is bounded, persists and restores muted preference',async()=>{
    const {audio,savedValues}=setup();audio.setVolume(3);assert.equal(audio.status().volume,1);
    audio.setVolume(-1);assert.equal(audio.status().volume,0);audio.setVolume(.35);audio.setEnabled(false);
    const restored=setup(JSON.stringify(savedValues.at(-1))).audio;
    assert.equal(restored.status().enabled,false);assert.equal(restored.status().volume,.35);
    assert.equal(await restored.unlock(),false);
});
test('all game cues produce audio and unsupported browsers fail gracefully',async()=>{
    const {audio}=setup();await audio.unlock();
    for(const cue of ['pick','join','question','answer','tick','correct','incorrect','reveal','finish','score_tick','score_done','rank_up','rank_down','ranking','podium_open','podium_third','podium_second','champion'])assert.equal(audio.play(cue),true);
    assert.equal(audio.play('unknown'),false);
    const unavailable=setup(null,true).audio;assert.equal(await unavailable.unlock(),false);assert.equal(unavailable.status().available,false);
});
