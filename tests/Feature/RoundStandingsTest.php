<?php
namespace Tests\Feature;
use App\Quiz;
use App\Services\GameService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class RoundStandingsTest extends TestCase
{
    use RefreshDatabase;
    protected function tearDown(): void { Carbon::setTestNow();parent::tearDown(); }
    private function setupGame()
    {
        $this->seed('QuizArenaSeeder');Carbon::setTestNow(Carbon::parse('2026-11-03 00:00:00','UTC'));
        $service=app(GameService::class);$game=$service->create(Quiz::where('status','ready')->first());
        $a=$service->join($game->pin,'Alya','token-a','luna');$b=$service->join($game->pin,'Bima','token-b','bimo');
        return [$service,$service->transition($game,'start',0),$a,$b];
    }
    public function test_each_round_returns_server_totals_delta_and_real_rank_movements()
    {
        [$service,$game,$a,$b]=$this->setupGame();$q=$game->currentQuestion();
        Carbon::setTestNow($game->started_at->copy()->addSeconds(4));$service->answer($game,$a,$q->id,(int)$q->correct_index);
        $active=$service->state($game,$a);$this->assertArrayNotHasKey('leaderboard',$active);$this->assertArrayNotHasKey('score',$active['my_answer']);
        Carbon::setTestNow($game->started_at->copy()->addSeconds(8));$service->answer($game,$b,$q->id,(int)$q->correct_index);
        $first=$service->state($game,$a);$this->assertSame('question_result',$first['status']);
        $this->assertSame(900,$first['leaderboard'][0]['earned_score']);$this->assertSame(0,$first['leaderboard'][0]['previous_score']);
        $game=$service->transition($game,'next',$first['version']);$q=$game->currentQuestion();
        Carbon::setTestNow($game->started_at->copy()->addSeconds(1));$service->answer($game,$a,$q->id,((int)$q->correct_index+1)%count($q->choices));
        Carbon::setTestNow($game->started_at->copy()->addSeconds(2));$service->answer($game,$b,$q->id,(int)$q->correct_index);
        $state=$service->state($game,$b);$winner=$state['leaderboard'][0];$other=$state['leaderboard'][1];
        $this->assertSame($b->id,$winner['id']);$this->assertSame(1750,$winner['score']);$this->assertSame(800,$winner['previous_score']);$this->assertSame(950,$winner['earned_score']);
        $this->assertSame(2,$winner['previous_rank']);$this->assertSame(1,$winner['rank']);$this->assertSame(1,$winner['rank_change']);
        $this->assertSame($a->id,$other['id']);$this->assertSame(900,$other['score']);$this->assertSame(0,$other['earned_score']);$this->assertSame(-1,$other['rank_change']);
        $this->assertSame($state['leaderboard'],$service->state($game->fresh(),$b)['leaderboard']);
    }
    public function test_unanswered_round_preserves_previous_ranks_and_adds_zero_points()
    {
        [$service,$game,$a,$b]=$this->setupGame();$q=$game->currentQuestion();
        foreach([$a,$b] as $p)$service->answer($game,$p,$q->id,(int)$q->correct_index);
        $first=$service->state($game);$game=$service->transition($game,'next',$first['version']);
        Carbon::setTestNow($game->deadline_at);$state=$service->state($game);
        foreach($state['leaderboard'] as $row){$this->assertSame(0,$row['earned_score']);$this->assertSame($row['score'],$row['previous_score']);$this->assertSame(0,$row['rank_change']);}
        $this->assertSame(array_column($first['leaderboard'],'id'),array_column($state['leaderboard'],'id'));
    }
}
