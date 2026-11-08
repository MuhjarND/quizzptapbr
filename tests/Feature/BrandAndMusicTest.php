<?php
namespace Tests\Feature;
use App\{User,Quiz};
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Artisan, Hash};
use Tests\TestCase;
class BrandAndMusicTest extends TestCase
{
    use RefreshDatabase;
    public function test_host_creation_generates_a_working_login_and_does_not_reset_existing_accounts()
    {
        $this->withoutMockingConsoleOutput();
        $email = 'admin@ptapapuabarat.test';
        $this->assertSame(0, Artisan::call('host:create', ['email' => $email]));
        preg_match('/Kata sandi: (\S+)/', Artisan::output(), $matches);
        $host = User::where('email', $email)->firstOrFail();
        $this->assertSame('Admin PTA Papua Barat', $host->name);
        $this->assertTrue(Hash::check($matches[1], $host->password));
        $this->post('/login', ['email' => $email, 'password' => $matches[1]])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($host);
        $this->assertSame(1, Artisan::call('host:create', ['email' => $email]));
        $this->assertSame($host->password, $host->fresh()->password);
        $this->assertSame(1, User::where('email', $email)->count());
    }
    public function test_public_brand_and_legacy_session_identity_are_preserved()
    {
        $this->assertSame('Cerdas Bersama PTA Papua Barat',config('app.name'));
        $this->assertSame('quizarena_session',config('session.cookie'));
        foreach(['/','/login','/gabung'] as $url) $this->get($url)->assertOk()->assertSee('Cerdas Bersama PTA Papua Barat')->assertSee('cerdas-bersama-pta-lockup-v2.svg')->assertDontSee('QuizArena');
    }
    public function test_host_game_has_independent_music_and_effect_controls()
    {
        $this->seed('QuizArenaSeeder');$host=User::first();$game=app(GameService::class)->create(Quiz::where('status','ready')->first());
        $this->actingAs($host)->get(route('games.host',$game))->assertOk()->assertSee('Aktifkan musik')->assertSee('Volume musik latar')->assertSee('Volume efek suara')->assertSee('pta-pabar-theme.wav')->assertSee('js/music.js')->assertSee('Cerdas Bersama PTA Papua Barat');
        $this->get('/dashboard')->assertOk()->assertSee('Cerdas Bersama PTA Papua Barat')->assertDontSee('QuizArena');
    }
}
