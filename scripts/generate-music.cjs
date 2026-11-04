/* Original instrumental: "Langkah Ceria", composed for QUIZ by PTA Pabar.
 * Rebuild: node scripts/generate-music.cjs (Node 20+, no packages).
 * 16 bars, 120 BPM, C major: plucked keys, warm pads, bass and light percussion.
 * No samples or third-party recordings are used.
 */
const fs=require('node:fs'),path=require('node:path');
const rate=24000,beat=.5,bars=16,duration=bars*4*beat,length=Math.round(rate*duration);
const left=new Float64Array(length),right=new Float64Array(length);
const tau=Math.PI*2;
let seed=20261103;
const noise=()=>{seed=(Math.imul(seed,1664525)+1013904223)>>>0;return seed/2147483648-1;};
const hz=midi=>440*2**((midi-69)/12);
function add(start,seconds,pan,gain,sample){
    const from=Math.round(start*rate),count=Math.round(seconds*rate),lg=Math.sqrt((1-pan)/2)*gain,rg=Math.sqrt((1+pan)/2)*gain;
    for(let i=0;i<count;i++){
        const at=(from+i)%length,t=i/rate,amplitude=sample(t,i);
        left[at]+=amplitude*lg;right[at]+=amplitude*rg;
    }
}
function key(note,start,seconds=.38,pan=.1,gain=.21){
    const f=hz(note);
    add(start,seconds,pan,gain,t=>{
        const attack=Math.min(1,t/.006),release=Math.min(1,(seconds-t)/.035);
        return attack*release*(Math.sin(tau*f*t)*Math.exp(-5.5*t/seconds)+.22*Math.sin(tau*f*2*t)*Math.exp(-11*t/seconds)+.09*Math.sin(tau*f*3*t)*Math.exp(-15*t/seconds));
    });
}
function pad(notes,start){
    for(const [i,n] of notes.entries())add(start,2.16,i%2?-.65:.65,.075,t=>{
        const envelope=Math.min(1,t/.17)*Math.min(1,(2.16-t)/.22);
        return envelope*(Math.sin(tau*hz(n)*t)+.14*Math.sin(tau*hz(n)*2.002*t))*.7;
    });
}
function bass(note,start,seconds=.35){
    const f=hz(note);add(start,seconds,-.12,.24,t=>Math.min(1,t/.012)*Math.min(1,(seconds-t)/.045)*Math.exp(-2*t/seconds)*(Math.sin(tau*f*t)+.12*Math.sin(tau*f*2*t)));
}
function kick(start){add(start,.14,0,.17,t=>Math.sin(tau*(45*t+34*(1-Math.exp(-t*35))/35))*Math.exp(-t*35));}
function hat(start,open=false){let last=0;add(start,open?.11:.045,.38,open?.023:.018,t=>{const n=noise(),high=n-last;last=n;return high*Math.exp(-t*(open?45:110));});}
function snap(start){add(start,.09,-.25,.031,t=>(noise()*.8+Math.sin(tau*190*t)*.2)*Math.exp(-t*60));}
const chords=[[60,64,67],[57,60,64],[53,57,60],[55,59,62],[52,55,59],[57,60,64],[53,57,60],[55,59,62]];
const roots=[36,33,29,31,28,33,29,31];
const melody=[
 [76,null,79,76,74,null,72,74],
 [76,null,72,69,72,null,76,null],
 [77,null,76,72,69,null,72,74],
 [74,null,71,67,71,74,79,null],
 [76,null,79,83,79,null,76,74],
 [72,null,76,81,79,null,76,null],
 [77,76,72,null,74,72,69,null],
 [71,null,74,79,74,null,72,74]
];
for(let bar=0;bar<bars;bar++){
    const start=bar*2,index=bar%8;pad(chords[index],start);
    for(let b=0;b<4;b++){
        bass(roots[index]+(b===2?7:0),start+b*beat,b%2?.28:.38);
        if(b===0||b===2)kick(start+b*beat);else snap(start+b*beat);
        hat(start+b*beat);hat(start+b*beat+.25,b===3);
    }
    melody[index].forEach((note,j)=>{if(note!==null)key(note,start+j*.25,j===7?.24:.34,j%2?.22:-.22,bar<8?.20:.17);});
    if(bar>=8)chords[index].forEach((note,j)=>key(note+12,start+.5+j*.5,.43,j%2?-.42:.42,.055));
}
// A short stereo echo wraps around the loop; soft limiting preserves headroom.
for(let i=0;i<length;i++){
    const fade=Math.min(1,i/(rate*.014),(length-i)/(rate*.014));
    const l=left[i]+right[(i-Math.round(rate*.1875)+length)%length]*.11;
    const r=right[i]+left[(i-Math.round(rate*.25)+length)%length]*.1;
    left[i]=Math.tanh(l*1.25)*fade;right[i]=Math.tanh(r*1.25)*fade;
}
const data=Buffer.alloc(44+length*4);
data.write('RIFF',0);data.writeUInt32LE(data.length-8,4);data.write('WAVEfmt ',8);data.writeUInt32LE(16,16);data.writeUInt16LE(1,20);data.writeUInt16LE(2,22);data.writeUInt32LE(rate,24);data.writeUInt32LE(rate*4,28);data.writeUInt16LE(4,32);data.writeUInt16LE(16,34);data.write('data',36);data.writeUInt32LE(length*4,40);
for(let i=0;i<length;i++){data.writeInt16LE(Math.round(Math.max(-1,Math.min(1,left[i]))*32767),44+i*4);data.writeInt16LE(Math.round(Math.max(-1,Math.min(1,right[i]))*32767),46+i*4);}
const output=path.join(__dirname,'../public/audio/pta-pabar-theme.wav');fs.mkdirSync(path.dirname(output),{recursive:true});fs.writeFileSync(output,data);
console.log(`Created original ${duration}s stereo loop: ${data.length} bytes`);
