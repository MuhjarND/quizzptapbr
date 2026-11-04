<?php
namespace Tests\Feature;
use App\{User, Quiz, GameSession, Answer};
use App\Services\GameService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class GameFlowTest extends TestCase
{
    use RefreshDatabase;
    protected $host, $quiz, $service;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed('QuizArenaSeeder');
        $this->host = User::first(); $this->quiz = Quiz::where('status', 'ready')->first();
        $this->service = app(GameService::class);
        Carbon::setTestNow(Carbon::parse('2026-11-02 00:00:00.000', 'UTC'));
    }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function started()
    {
        $g = $this->service->create($this->quiz); $p = $this->service->join($g->pin, 'Alya', str_repeat('a',64));
        $g = $this->service->transition($g, 'start', 0); return [$g,$p];
    }
    public function test_full_game_and_no_answer_key_until_result()
    {
        [$g,$p] = $this->started();
        $active = $this->service->state($g,$p);
        $this->assertArrayNotHasKey('correct_index', $active['question']);
        $this->assertArrayNotHasKey('token_hash', $active['participants'][0]);
        $this->assertArrayNotHasKey('leaderboard', $active);
        for ($i=1; $i<=5; $i++) {
            $g->refresh(); $q = $g->currentQuestion(); Carbon::setTestNow(now()->addSeconds(10));
            $this->service->answer($g,$p,$q->id,(int)$q->correct_index);
            $s = $this->service->state($g,$p);
            $this->assertSame('question_result', $s['status']);
            if($i<5)$this->assertSame(750,$s['my_answer']['score']);
            else {$this->assertTrue($s['final_pending']);$this->assertArrayNotHasKey('score',$s['my_answer']);}
            $g = $this->service->transition($g,'next',$s['version']);
        }
        $this->assertSame('finished',$g->status); $this->assertNull($g->active_pin);
        $this->assertSame(3750,$this->service->leaderboard($g)[0]['score']);
        $this->actingAs($this->host)->get(route('games.result',$g))->assertOk()->assertSee('Alya');
    }
    public function test_duplicate_answer_rejected_and_unique_constraint_exists()
    {
        [$g,$p] = $this->started();
        // Keep question active with another participant, inserted before start in real use.
        $g->participants()->create(['nickname'=>'Bima','nickname_key'=>'bima','token_hash'=>hash('sha256','b')]);
        $q=$g->currentQuestion(); $this->service->answer($g,$p,$q->id,0);
        try { $this->service->answer($g,$p,$q->id,1); $this->fail('Duplicate accepted'); }
        catch (ValidationException $e) { $this->assertSame(1,Answer::count()); }
    }
    public function test_late_answer_rejected_and_poll_settles_timeout()
    {
        [$g,$p]=$this->started(); $q=$g->currentQuestion(); Carbon::setTestNow($g->deadline_at);
        try { $this->service->answer($g,$p,$q->id,0); $this->fail('Late answer accepted'); }
        catch (ValidationException $e) { $this->assertSame(0,Answer::count()); }
        $this->assertSame('question_result',$this->service->state($g,$p)['status']);
    }
    public function test_stale_control_version_does_not_advance_twice()
    {
        [$g,$p]=$this->started(); $this->service->answer($g,$p,$g->currentQuestion()->id,0);
        $state=$this->service->state($g); $next=$this->service->transition($g,'next',$state['version']);
        try { $this->service->transition($g,'next',$state['version']); $this->fail('Double next accepted'); }
        catch (ValidationException $e) { $this->assertEquals(2,$next->fresh()->current_position); }
    }
    public function test_join_after_start_and_duplicate_nickname_are_rejected()
    {
        $g=$this->service->create($this->quiz); $this->service->join($g->pin,'Alya','first');
        try { $this->service->join($g->pin,'ALYA','second'); $this->fail('Duplicate name'); } catch (ValidationException $e) { $this->assertSame(1,$g->participants()->count()); }
        $this->service->transition($g,'start',0);
        $this->expectException(ValidationException::class); $this->service->join($g->pin,'Bima','third');
    }
    public function test_other_hosts_and_unauthenticated_players_cannot_access_game()
    {
        $g=$this->service->create($this->quiz);
        $other=User::create(['name'=>'Other','email'=>'other@example.test','password'=>bcrypt('password')]);
        $this->actingAs($other)->get(route('quizzes.edit',$this->quiz))->assertForbidden();
        $this->delete(route('quizzes.destroy',$this->quiz))->assertForbidden();
        $this->post(route('quizzes.duplicate',$this->quiz))->assertForbidden();
        $this->post(route('games.create',$this->quiz))->assertForbidden();
        $this->getJson(route('games.host-state',$g))->assertForbidden();
        $this->postJson(route('games.control',$g),['action'=>'start','version'=>0])->assertForbidden();
        $this->get(route('games.result',$g))->assertForbidden();
        $this->getJson(route('games.state',$g))->assertForbidden();
        $this->postJson(route('games.answer',$g),['question_id'=>1,'choice_index'=>0])->assertForbidden();
    }
    public function test_snapshots_survive_quiz_edits_and_deletion()
    {
        $g=$this->service->create($this->quiz); $original=$g->questions->first()->text;
        $this->quiz->questions()->first()->update(['text'=>'Changed']);
        $this->assertSame($original,$g->questions()->first()->text);
        $this->quiz->delete(); $this->assertNull($g->fresh()->quiz_id); $this->assertSame(5,$g->questions()->count());
    }
    public function test_refresh_keeps_participant_identity_and_host_control()
    {
        $g=$this->service->create($this->quiz);
        $this->post(route('join.store'),['pin'=>$g->pin,'nickname'=>'Alya'])->assertRedirect(route('games.play',$g));
        $this->get(route('games.play',$g))->assertOk()->assertSee('Alya');
        $this->getJson(route('games.state',$g))->assertOk()->assertJsonPath('participants.0.nickname','Alya');
        $this->actingAs($this->host)->get(route('games.host',$g))->assertOk();
    }
    public function test_draft_and_invalid_pin_cannot_start_game()
    {
        $this->post(route('join.store'),['pin'=>'000000','nickname'=>'Alya'])->assertSessionHasErrors('game');
        $this->expectException(ValidationException::class); $this->service->create(Quiz::where('status','draft')->first());
    }
    public function test_tiebreak_uses_correct_count_then_correct_time_then_join_order()
    {
        $g=$this->service->create($this->quiz); $a=$this->service->join($g->pin,'Alya','a'); $b=$this->service->join($g->pin,'Bima','b');
        $g=$this->service->transition($g,'start',0); $q=$g->currentQuestion();
        foreach ([$a,$b] as $p) $this->service->answer($g,$p,$q->id,(int)$q->correct_index);
        $rows=$this->service->leaderboard($g); $this->assertSame($a->id,$rows[0]['id']);
        $b->answers()->update(['elapsed_ms'=>0,'score'=>1000]); $a->answers()->update(['elapsed_ms'=>1,'score'=>1000]);
        $this->assertSame($b->id,$this->service->leaderboard($g)[0]['id']);
    }
    public function test_quiz_editor_validates_correct_choice_and_owns_nested_question_ids()
    {
        $this->actingAs($this->host)->post(route('quizzes.store'),['title'=>'Test','theme'=>'islands','status'=>'ready','questions'=>[['text'=>'Test?','duration'=>20,'choices'=>['A','B'],'correct_index'=>3]]])->assertSessionHasErrors('questions.0.correct_index');
        $this->post(route('quizzes.store'),['title'=>'Test','theme'=>'islands','status'=>'ready','questions'=>[['text'=>'Test?','duration'=>20,'choices'=>['A','B'],'correct_index'=>1]]])->assertRedirect(route('quizzes.index'));
        $q=Quiz::where('title','Test')->first(); $this->assertTrue($q->playable());
        $this->post(route('quizzes.duplicate',$q))->assertRedirect(); $this->assertSame('draft',Quiz::where('title','Test (salinan)')->first()->status);
    }
}
