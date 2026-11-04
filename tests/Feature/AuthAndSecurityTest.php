<?php
namespace Tests\Feature;
use App\{User,Quiz,Answer};
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Tests\TestCase;
class AuthAndSecurityTest extends TestCase
{
    use RefreshDatabase;
    public function test_registration_login_logout_and_guest_redirect()
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->post('/register',['name'=>'Nadia','email'=>'nadia@example.test','password'=>'Secret123!','password_confirmation'=>'Secret123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticated(); $this->get('/login')->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect('/'); $this->assertGuest();
        $this->post('/login',['email'=>'nadia@example.test','password'=>'incorrect'])->assertSessionHasErrors('email');
        $this->post('/login',['email'=>'nadia@example.test','password'=>'Secret123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
    public function test_uploads_validate_type_size_and_preserve_snapshot_media()
    {
        Storage::fake('public'); $this->seed('QuizArenaSeeder'); $host=User::first();
        $data=['title'=>'Image quiz','theme'=>'islands','status'=>'ready','questions'=>[['text'=>'Gambar apa?','duration'=>20,'choices'=>['A','B'],'correct_index'=>0]]];
        $this->actingAs($host)->post('/quizzes',$data+['cover'=>UploadedFile::fake()->create('script.php',10,'application/x-php')])->assertSessionHasErrors('cover');
        $this->post('/quizzes',$data+['cover'=>UploadedFile::fake()->image('large.jpg')->size(2049)])->assertSessionHasErrors('cover');
        $data['questions'][0]['image']=UploadedFile::fake()->image('question.png');
        $this->post('/quizzes',$data+['cover'=>UploadedFile::fake()->image('cover.jpg')])->assertRedirect('/quizzes');
        $quiz=Quiz::where('title','Image quiz')->first(); Storage::disk('public')->assertExists($quiz->cover);
        $image=$quiz->questions->first()->image; Storage::disk('public')->assertExists($image);
        $game=app(GameService::class)->create($quiz); $quiz->delete();
        $this->assertSame($image,$game->questions->first()->image); Storage::disk('public')->assertExists($image);
    }
    public function test_answer_unique_constraint_blocks_direct_duplicates()
    {
        $this->seed('QuizArenaSeeder'); $service=app(GameService::class); $game=$service->create(Quiz::where('status','ready')->first());
        $p=$service->join($game->pin,'Alya','test-token'); $game=$service->transition($game,'start',0);
        $a=$service->answer($game,$p,$game->currentQuestion()->id,0);
        $this->expectException(QueryException::class); $copy=$a->replicate(); $copy->save();
    }
    public function test_inactive_question_id_is_rejected()
    {
        $this->seed('QuizArenaSeeder'); $service=app(GameService::class); $game=$service->create(Quiz::where('status','ready')->first());
        $p=$service->join($game->pin,'Alya','test-token'); $game=$service->transition($game,'start',0);
        $this->withSession(['participant_tokens'=>[$game->id=>'test-token']])->postJson(route('games.answer',$game),['question_id'=>$game->questions()->where('position',2)->first()->id,'choice_index'=>0])->assertStatus(422);
        $this->assertSame(0,Answer::count());
    }
    public function test_polling_rate_limit_is_enforced()
    {
        $request=\Illuminate\Http\Request::create('/poll');
        $session=app('session')->driver(); $session->setId(str_repeat('a',40)); $request->setLaravelSession($session);
        $route=new \Illuminate\Routing\Route('GET','/poll',function(){}); $route->name('games.state');
        $request->setRouteResolver(function() use($route){return $route;});
        $middleware=app(\App\Http\Middleware\ArenaThrottle::class); $next=function(){return response()->json(['ok'=>true]);};
        for($i=0;$i<90;$i++) $this->assertSame(200,$middleware->handle($request,$next,90)->status());
        $blocked=$middleware->handle($request,$next,90); $this->assertSame(429,$blocked->status());
        $this->assertTrue($blocked->headers->has('Retry-After'));
        $session->setId(str_repeat('b',40));
        $this->assertSame(200,$middleware->handle($request,$next,90)->status());
    }
}
