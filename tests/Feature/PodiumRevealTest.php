<?php
namespace Tests\Feature;
use App\{Quiz, User};
use App\Services\GameService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PodiumRevealTest extends TestCase
{
    use RefreshDatabase;
    protected function tearDown(): void {Carbon::setTestNow();parent::tearDown();}
    private function finalRound($count=3)
    {
        $this->seed('QuizArenaSeeder');Carbon::setTestNow(Carbon::parse('2026-11-04 00:00:00','UTC'));
        $service=app(GameService::class);$game=$service->create(Quiz::where('status','ready')->first());
        $game->questions()->where('position','>',1)->delete();$players=[];
        for($i=0;$i<$count;$i++)$players[]=$service->join($game->pin,'Peserta '.($i+1),'podium-token-'.$game->id.'-'.$i);
        $game=$service->transition($game,'start',0);$q=$game->currentQuestion();
        foreach($players as $i=>$player){Carbon::setTestNow($game->started_at->copy()->addSeconds($i+1));$service->answer($game,$player,$q->id,(int)$q->correct_index);}
        return [$service,$game,$players];
    }
    public function test_final_answer_hides_all_results_and_reveal_discloses_only_authorized_winners()
    {
        [$service,$game,$players]=$this->finalRound(4);
        foreach([null,...$players] as $player){
            $s=$service->state($game,$player);$this->assertTrue($s['final_pending']);
            $this->assertArrayNotHasKey('leaderboard',$s);$this->assertArrayNotHasKey('podium',$s);
            $this->assertArrayNotHasKey('correct_index',$s['question']);$this->assertArrayNotHasKey('distribution',$s);
            if($player){$this->assertArrayNotHasKey('score',$s['my_answer']);$this->assertArrayNotHasKey('is_correct',$s['my_answer']);}
        }
        $game=$service->transition($game,'next',$s['version']);
        for($step=1;$step<=3;$step++){
            $s=$service->state($game,$players[0]);$this->assertSame($step,$s['podium']['step']);
            $this->assertSame(5, $s['podium']['countdown_seconds']);
            $this->assertSame($step === 1 ? 8800 : 7200, (int) Carbon::parse($s['podium']['step_at'])->diffInMilliseconds(Carbon::parse($s['podium']['ready_at'])));
            $this->assertSame(array_slice([3,2,1],0,$step),array_column($s['podium']['winners'],'rank'));
            $this->assertSame(array_slice(array_reverse(array_slice(array_column($players,'id'),0,3)),0,$step),array_column($s['podium']['winners'],'id'));
            $this->assertSame($s['podium'],$service->state($game->fresh())['podium']);
            if($step<3){
                $this->assertSame('podium',$s['status']);$this->assertArrayNotHasKey('leaderboard',$s);
                $this->actingAs(User::first())->get(route('games.result',$game))->assertRedirect(route('games.host',$game));
                Carbon::setTestNow(Carbon::parse($s['podium']['ready_at']));$game=$service->transition($game,'reveal',$s['version']);
            }
        }
        $this->assertSame('finished',$s['status']);$this->assertTrue($s['podium']['complete']);$this->assertCount(4,$s['leaderboard']);
        $this->assertNull($game->fresh()->active_pin);$this->actingAs(User::first())->get(route('games.result',$game))->assertOk();
    }
    public function test_host_cannot_skip_or_double_reveal_and_participants_cannot_control_ceremony()
    {
        [$service,$game,$players]=$this->finalRound();$s=$service->state($game);$game=$service->transition($game,'next',$s['version']);
        $s=$service->state($game);
        $this->actingAs(User::first())->postJson(route('games.control',$game),['action'=>'reveal','version'=>$s['version']])->assertStatus(422);
        $this->postJson(route('games.control',$game),['action'=>'finish','version'=>$s['version']])->assertStatus(422);
        Carbon::setTestNow(Carbon::parse($s['podium']['ready_at']));$service->transition($game,'reveal',$s['version']);
        $this->postJson(route('games.control',$game),['action'=>'reveal','version'=>$s['version']])->assertStatus(422);
        $this->assertSame(2,$game->fresh()->podium_step);
        auth()->logout();$this->withSession(['participant_tokens.'.$game->id=>'podium-token-'.$game->id.'-0'])->postJson(route('games.control',$game),['action'=>'reveal','version'=>$game->fresh()->version])->assertUnauthorized();
    }
    public function test_timeout_on_last_question_keeps_results_hidden_and_small_podiums_have_no_empty_places()
    {
        foreach([1,2] as $count){
            [$service,$game,$players]=$this->finalRound($count);$game->currentQuestion()->answers()->delete();$game->update(['status'=>'question_active']);Carbon::setTestNow($game->deadline_at);
            $s=$service->state($game);$this->assertTrue($s['final_pending']);$this->assertArrayNotHasKey('leaderboard',$s);
            $game=$service->transition($game,'next',$s['version']);$s=$service->state($game);
            $this->assertSame($count,$s['podium']['total']);$this->assertSame($count,$s['podium']['winners'][0]['rank']);
            $this->assertSame($count===1,$s['podium']['complete']);
        }
    }
    public function test_legacy_finished_games_still_show_saved_results_without_starting_a_ceremony()
    {
        [$service,$game]=$this->finalRound();$game->update(['status'=>'finished','active_pin'=>null,'finished_at'=>now()]);
        $s=$service->state($game);$this->assertArrayNotHasKey('podium',$s);$this->assertCount(3,$s['leaderboard']);
    }
}
