<?php
// Standalone InnoDB concurrency check. Uses ONLY quiz_arena_race_test.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'mysql','database.connections.mysql.database'=>'quiz_arena_race_test']);
Illuminate\Support\Facades\DB::purge('mysql');
$service=app(App\Services\GameService::class);
if (($argv[1] ?? '') === 'worker') {
    try {
        $game=App\GameSession::findOrFail($argv[3]);
        if ($argv[2] === 'answer') $service->answer($game,App\Participant::findOrFail($argv[4]),(int)$argv[5],0);
        else $service->transition($game,$argv[2],(int)$argv[4]);
        echo 'accepted';
    } catch (Illuminate\Validation\ValidationException $e) { echo 'rejected'; }
    exit;
}
Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]);
$host=App\User::create(['name'=>'Race test','email'=>'race-'.bin2hex(random_bytes(6)).'@example.test','password'=>bcrypt(bin2hex(random_bytes(12)))]);
try {
    $quiz=App\Quiz::create(['user_id'=>$host->id,'title'=>'Concurrency test','theme'=>'islands','status'=>'ready']);
    foreach ([1,2] as $position) {
        $q=$quiz->questions()->create(['text'=>'Test '.$position,'duration'=>120,'position'=>$position]);
        foreach (['A','B'] as $i=>$text) $q->choices()->create(['text'=>$text,'is_correct'=>$i===0,'position'=>$i]);
    }
    $game=$service->create($quiz); $p=$service->join($game->pin,'Alya',bin2hex(random_bytes(32))); $p2=$service->join($game->pin,'Bima',bin2hex(random_bytes(32)));
    $game=$service->transition($game,'start',0); $qid=$game->currentQuestion()->id;
    $race=function(array $args) {
        $running=[];
        for($i=0;$i<2;$i++) {
            $pipes=[]; $process=proc_open(array_merge([PHP_BINARY,__FILE__,'worker'],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
            if(!is_resource($process)) throw new RuntimeException('Worker could not start');
            fclose($pipes[0]); $running[]=[$process,$pipes];
        }
        $outputs=[];
        foreach($running as [$process,$pipes]) {
            $output=trim(stream_get_contents($pipes[1])); $error=stream_get_contents($pipes[2]); fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);
            if($code!==0 || $error) throw new RuntimeException('Worker failed: '.$error.' '.$output);
            $outputs[]=$output;
        }
        sort($outputs); if($outputs!==['accepted','rejected']) throw new RuntimeException('Race invariant failed: '.json_encode($outputs));
    };
    $race(['answer',(string)$game->id,(string)$p->id,(string)$qid]);
    if(App\Answer::where('participant_id',$p->id)->count()!==1) throw new RuntimeException('Duplicate answer persisted');
    echo "PASS: concurrent duplicate answers produce one stored answer.\n";
    $service->answer($game,$p2,$qid,0); $state=$service->state($game);
    $race(['next',(string)$game->id,(string)$state['version']]);
    if($game->fresh()->current_position!==2) throw new RuntimeException('Question skipped');
    echo "PASS: concurrent next requests advance exactly once.\n";
    $game->refresh();$qid=$game->currentQuestion()->id;
    foreach([$p,$p2] as $player)$service->answer($game,$player,$qid,0);
    $state=$service->state($game);$game=$service->transition($game,'next',$state['version']);
    // Make this isolated fixture ready without sleeping through an animation.
    $game->update(['podium_step_at'=>now()->subSeconds(10)]);$state=$service->state($game);
    $race(['reveal',(string)$game->id,(string)$state['version']]);
    if($game->fresh()->podium_step!==2||$game->fresh()->status!=='finished')throw new RuntimeException('Podium reveal skipped or duplicated');
    echo "PASS: concurrent podium reveals announce exactly one next winner.\n";
} finally { $host->delete(); }
