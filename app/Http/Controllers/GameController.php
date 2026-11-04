<?php
namespace App\Http\Controllers;
use App\{Quiz, GameSession, Participant};
use App\Http\Requests\{JoinRequest, AnswerRequest};
use App\Services\GameService;
use Illuminate\Http\Request;
class GameController extends Controller
{
    private $games;
    public function __construct(GameService $games) { $this->games = $games; }
    public function create(Quiz $quiz) { $this->authorize('view', $quiz); return redirect()->route('games.host', $this->games->create($quiz)); }
    public function host(GameSession $game) { $this->authorize('view', $game); return view('game', ['game' => $game, 'isHost' => true, 'participant' => null]); }
    public function hostState(GameSession $game) { $this->authorize('view', $game); return response()->json($this->games->state($game))->header('Cache-Control', 'no-store'); }
    public function control(Request $request, GameSession $game)
    {
        $this->authorize('update', $game); $data = $request->validate(['action' => 'required|in:start,next,finish,reveal', 'version' => 'required|integer|min:0']);
        $this->games->transition($game, $data['action'], (int) $data['version']); return response()->json($this->games->state($game));
    }
    public function joinForm(Request $request) { return view('join'); }
    public function join(JoinRequest $request)
    {
        $token = bin2hex(random_bytes(32)); $p = $this->games->join($request->pin, $request->nickname, $token, $request->input('character', 'nala'));
        $request->session()->put('participant_tokens.'.$p->game_session_id, $token); return redirect()->route('games.play', $p->game_session_id);
    }
    private function participant(Request $request, GameSession $game): Participant
    {
        $token = $request->session()->get('participant_tokens.'.$game->id);
        abort_unless(is_string($token), 403, 'Identitas peserta tidak ditemukan. Gunakan browser saat Anda bergabung.');
        return $game->participants()->where('token_hash', hash('sha256', $token))->firstOrFail();
    }
    public function play(Request $request, GameSession $game) { return view('game', ['game' => $game, 'isHost' => false, 'participant' => $this->participant($request, $game)]); }
    public function state(Request $request, GameSession $game) { return response()->json($this->games->state($game, $this->participant($request, $game)))->header('Cache-Control', 'no-store'); }
    public function answer(AnswerRequest $request, GameSession $game)
    {
        $this->games->answer($game, $this->participant($request, $game), (int) $request->question_id, (int) $request->choice_index);
        return response()->json(['message' => 'Jawaban terkunci. Tunggu hasilnya.']);
    }
    public function history(Request $request) { $games = GameSession::where('user_id', $request->user()->id)->withCount('participants')->latest()->paginate(15); return view('history', compact('games')); }
    public function result(GameSession $game)
    {
        $this->authorize('view', $game); $this->games->state($game); $game->refresh();
        if ($game->status !== 'finished') return redirect()->route('games.host', $game);
        $leaderboard = $this->games->leaderboard($game); $game->load('questions', 'participants.answers'); return view('result', compact('game', 'leaderboard'));
    }
}
