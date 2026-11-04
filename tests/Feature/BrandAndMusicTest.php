<?php
namespace Tests\Feature;
use App\{User,Quiz};
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BrandAndMusicTest extends TestCase
{
    use RefreshDatabase;
    public function test_public_brand_and_legacy_session_identity_are_preserved()
    {
        $this->assertSame('QUIZ by PTA Pabar',config('app.name'));
        $this->assertSame('quizarena_session',config('session.cookie'));
        foreach(['/','/login','/gabung'] as $url) $this->get($url)->assertOk()->assertSee('QUIZ by PTA Pabar')->assertSee('quiz-pta-pabar-logo.png')->assertDontSee('QuizArena');
    }
    public function test_host_game_has_independent_music_and_effect_controls()
    {
        $this->seed('QuizArenaSeeder');$host=User::first();$game=app(GameService::class)->create(Quiz::where('status','ready')->first());
        $this->actingAs($host)->get(route('games.host',$game))->assertOk()->assertSee('Aktifkan musik')->assertSee('Volume musik latar')->assertSee('Volume efek suara')->assertSee('pta-pabar-theme.wav')->assertSee('js/music.js')->assertSee('QUIZ by PTA Pabar');
        $this->get('/dashboard')->assertOk()->assertSee('QUIZ by PTA Pabar')->assertDontSee('QuizArena');
    }
}
