<?php
namespace Tests\Feature;
use App\{User,Quiz,Participant};
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class CharacterSelectionTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed('QuizArenaSeeder'); }
    public function test_all_six_characters_can_be_selected_and_reach_state_and_leaderboard()
    {
        $service=app(GameService::class); $game=$service->create(Quiz::where('status','ready')->first());
        $this->assertCount(6,config('characters'));
        foreach(array_keys(config('characters')) as $key) {
            $p=$service->join($game->pin,'Player '.$key,'token-'.$key,$key);
            $this->assertSame($key,$p->fresh()->character);
        }
        $state=$service->state($game); $this->assertSame(array_keys(config('characters')),array_column($state['participants'],'character'));
        $rows=$service->leaderboard($game); $this->assertSame(array_keys(config('characters')),array_column($rows,'character'));
        $this->assertArrayNotHasKey('token_hash',$state['participants'][0]);
    }
    public function test_http_selection_survives_refresh_and_is_saved_in_result()
    {
        $service=app(GameService::class); $game=$service->create(Quiz::where('status','ready')->first());
        $this->post('/gabung',['pin'=>$game->pin,'nickname'=>'Alya','character'=>'luna'])->assertRedirect(route('games.play',$game));
        $this->get(route('games.play',$game))->assertOk()->assertSee('Karakter Luna');
        $this->getJson(route('games.state',$game))->assertJsonPath('participants.0.character','luna');
        $this->assertSame('luna',Participant::first()->character);
        $service->transition($game,'finish',0);
        $this->actingAs(User::first())->get(route('games.result',$game))->assertOk()->assertSee('Karakter Luna');
    }
    public function test_unknown_character_is_rejected_at_request_and_service_boundaries()
    {
        $service=app(GameService::class); $game=$service->create(Quiz::where('status','ready')->first());
        $this->post('/gabung',['pin'=>$game->pin,'nickname'=>'Alya','character'=>'../../outside'])->assertSessionHasErrors('character');
        $this->assertSame(0,$game->participants()->count());
        $this->expectException(ValidationException::class); $service->join($game->pin,'Alya','token','unknown');
    }
    public function test_existing_clients_get_default_and_validation_retains_selection()
    {
        $service=app(GameService::class); $game=$service->create(Quiz::where('status','ready')->first());
        $p=$service->join($game->pin,'Legacy','legacy'); $this->assertSame('nala',$p->fresh()->character);
        $this->post('/gabung',['pin'=>'000000','nickname'=>'Alya','character'=>'oki'])->assertSessionHasErrors('game');
        $this->get('/gabung')->assertOk()->assertSee('Oki siap menemanimu!');
        $this->assertSame('nala',\App\Support\Characters::key('removed-character'));
    }
}
