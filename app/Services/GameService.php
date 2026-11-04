<?php
namespace App\Services;

use App\{Quiz, GameSession, Participant, Answer};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameService
{
    private $scores;
    public function __construct(ScoreService $scores) { $this->scores = $scores; }
    private function reject($message) { throw ValidationException::withMessages(['game' => $message]); }
    private function openPodium(GameSession $game): void
    {
        $game->active_pin = null;
        $game->podium_step = $game->participants()->exists() ? 1 : 0;
        $game->podium_step_at = $game->podium_step ? now() : null;
        $game->status = $game->participants()->count() > 1 ? 'podium' : 'finished';
        if ($game->status === 'finished') $game->finished_at = now();
    }
    private function podiumReadyAt(GameSession $game)
    {
        return $game->podium_step_at->copy()->addMilliseconds($game->podium_step === 1 ? 3800 : 2200);
    }
    public function create(Quiz $quiz): GameSession
    {
        return DB::transaction(function () use ($quiz) {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $quiz->load('questions.choices');
            if (!$quiz->playable()) $this->reject('Lengkapi kuis dan tandai siap dimainkan terlebih dahulu.');
            // Serializes PIN allocation across hosts; active_pin also has a unique constraint.
            DB::table('users')->orderBy('id')->lockForUpdate()->get(['id']);
            do { $pin = (string) random_int(100000, 999999); } while (GameSession::where('active_pin', $pin)->exists());
            $game = GameSession::create(['user_id' => $quiz->user_id, 'quiz_id' => $quiz->id, 'title' => $quiz->title, 'pin' => $pin, 'active_pin' => $pin]);
            foreach ($quiz->questions as $q) {
                $game->questions()->create(['text' => $q->text, 'image' => $q->image, 'duration' => $q->duration, 'position' => $q->position,
                    'choices' => $q->choices->pluck('text')->values()->all(), 'correct_index' => $q->choices->values()->search(function ($c) { return $c->is_correct; })]);
            }
            return $game;
        }, 3);
    }
    public function join(string $pin, string $nickname, string $token, string $character = 'nala'): Participant
    {
        if (!array_key_exists($character, config('characters'))) $this->reject('Karakter tidak tersedia. Pilih karakter dari daftar.');
        return DB::transaction(function () use ($pin, $nickname, $token, $character) {
            $game = GameSession::where('active_pin', $pin)->lockForUpdate()->first();
            if (!$game) $this->reject('PIN tidak ditemukan atau sesi sudah berakhir. Periksa kembali PIN Anda.');
            if ($game->status !== 'lobby') $this->reject('Permainan sudah dimulai. Peserta baru tidak dapat bergabung.');
            $key = mb_strtolower(trim($nickname));
            if ($game->participants()->where('nickname_key', $key)->exists()) $this->reject('Nama panggilan sudah digunakan. Pilih nama lain.');
            return $game->participants()->create(['nickname' => trim($nickname), 'nickname_key' => $key, 'token_hash' => hash('sha256', $token), 'character'=>$character]);
        }, 3);
    }
    public function transition(GameSession $original, string $action, int $version): GameSession
    {
        return DB::transaction(function () use ($original, $action, $version) {
            $game = GameSession::whereKey($original->id)->lockForUpdate()->firstOrFail();
            if ((int) $game->version !== $version) $this->reject('Layar sudah berubah. Tunggu pembaruan lalu coba kembali.');
            if ($action === 'finish') {
                if ($game->status === 'finished') return $game;
                if ($game->status === 'podium') $this->reject('Lanjutkan pengumuman melalui tombol Juara selanjutnya.');
                $this->openPodium($game);
            } elseif ($action === 'reveal' && $game->status === 'podium') {
                if (now()->lessThan($this->podiumReadyAt($game))) $this->reject('Tunggu pengumuman juara ini selesai terlebih dahulu.');
                $game->podium_step++; $game->podium_step_at = now();
                if ($game->podium_step >= min(3, $game->participants()->count())) {
                    $game->status = 'finished'; $game->finished_at = now();
                }
            } elseif (($action === 'start' && $game->status === 'lobby') || ($action === 'next' && $game->status === 'question_result')) {
                if ($action === 'start' && !$game->participants()->exists()) $this->reject('Tunggu setidaknya satu peserta bergabung.');
                $position = $game->current_position + 1;
                $q = $game->questions()->where('position', $position)->first();
                if (!$q) $this->openPodium($game);
                else {
                    $game->current_position = $position; $game->status = 'question_active';
                    $game->started_at = now(); $game->deadline_at = $game->started_at->copy()->addSeconds($q->duration);
                }
            } else $this->reject('Aksi tidak tersedia pada tahap permainan ini.');
            $game->version++; $game->save(); return $game;
        }, 3);
    }
    private function settle(GameSession $game): void
    {
        if ($game->status !== 'question_active') return;
        $q = $game->currentQuestion();
        if (now()->greaterThanOrEqualTo($game->deadline_at) || $q->answers()->count() >= $game->participants()->count()) {
            $game->status = 'question_result'; $game->version++; $game->save();
        }
    }
    public function answer(GameSession $original, Participant $participant, int $questionId, int $choice): Answer
    {
        return DB::transaction(function () use ($original, $participant, $questionId, $choice) {
            $game = GameSession::whereKey($original->id)->lockForUpdate()->firstOrFail();
            if ($participant->game_session_id !== $game->id) abort(403);
            if ($game->status !== 'question_active' || now()->greaterThanOrEqualTo($game->deadline_at)) $this->reject('Waktu habis atau pertanyaan sudah tidak aktif.');
            $q = $game->currentQuestion();
            if ($q->id !== $questionId) $this->reject('Pertanyaan ini sudah tidak aktif.');
            if (!array_key_exists($choice, $q->choices)) $this->reject('Pilihan jawaban tidak valid.');
            if ($q->answers()->where('participant_id', $participant->id)->exists()) $this->reject('Jawaban Anda sudah terkunci.');
            $elapsed = max(0, (int) round(((float) now()->format('U.u') - (float) $game->started_at->format('U.u')) * 1000));
            $correct = $choice === (int) $q->correct_index;
            $answer = $q->answers()->create(['participant_id' => $participant->id, 'choice_index' => $choice, 'is_correct' => $correct,
                'elapsed_ms' => $elapsed, 'score' => $this->scores->calculate($correct, $elapsed, $q->duration)]);
            $this->settle($game); return $answer;
        }, 3);
    }
    public function leaderboard(GameSession $game): array
    {
        // Both standings are rebuilt from saved answers, so refresh and reconnect
        // do not depend on the browser remembering the preceding leaderboard.
        $positions = $game->questions()->pluck('position', 'id');
        $rows = collect(); $previous = collect();
        foreach ($game->participants()->with('answers')->get() as $p) {
            $before = $p->answers->filter(function ($answer) use ($positions, $game) {
                return (int) $positions->get($answer->session_question_id, PHP_INT_MAX) < $game->current_position;
            });
            $summarize = function ($answers) use ($p) {
                $correct = $answers->where('is_correct', true);
                return ['id' => $p->id, 'nickname' => $p->nickname, 'character' => \App\Support\Characters::key($p->character),
                    'score' => (int) $answers->sum('score'), 'correct' => $correct->count(), 'elapsed_ms' => (int) $correct->sum('elapsed_ms')];
            };
            $rows->push($summarize($p->answers)); $previous->push($summarize($before));
        }
        $compare = function ($a, $b) {
            return ($b['score'] <=> $a['score']) ?: (($b['correct'] <=> $a['correct']) ?: (($a['elapsed_ms'] <=> $b['elapsed_ms']) ?: ($a['id'] <=> $b['id'])));
        };
        $previous = $previous->sort($compare)->values()->map(function ($row, $i) { $row['rank'] = $i + 1; return $row; })->keyBy('id');
        return $rows->sort($compare)->values()->map(function ($row, $i) use ($previous) {
            $before = $previous->get($row['id']);
            $row['rank'] = $i + 1; $row['previous_rank'] = $before['rank'];
            $row['previous_score'] = $before['score']; $row['earned_score'] = $row['score'] - $before['score'];
            $row['rank_change'] = $row['previous_rank'] - $row['rank'];
            return $row;
        })->all();
    }
    public function state(GameSession $original, ?Participant $participant = null): array
    {
        return DB::transaction(function () use ($original, $participant) {
            $game = GameSession::whereKey($original->id)->lockForUpdate()->firstOrFail();
            $this->settle($game); $q = $game->currentQuestion();
            $result = ['id' => $game->id, 'title' => $game->title, 'pin' => $game->pin, 'status' => $game->status,
                'version' => $game->version, 'position' => $game->current_position, 'total' => $game->questions()->count(),
                'server_now' => now()->format('Y-m-d\TH:i:s.vP'), 'deadline' => $game->deadline_at ? $game->deadline_at->format('Y-m-d\TH:i:s.vP') : null,
                'participants' => $game->participants()->orderBy('id')->get(['id', 'nickname', 'character'])->toArray()];
            $finalPending = $game->status === 'question_result' && $game->current_position >= $result['total'];
            $result['final_pending'] = $finalPending;
            $showQuestionResult = !$finalPending && in_array($game->status, ['question_result', 'finished']);
            $ceremony = $game->podium_step > 0;
            if ($ceremony) {
                $rows = $this->leaderboard($game); $total = min(3, count($rows));
                $result['podium'] = ['step' => $game->podium_step, 'total' => $total,
                    'step_at' => $game->podium_step_at->format('Y-m-d\TH:i:s.vP'),
                    'ready_at' => $this->podiumReadyAt($game)->format('Y-m-d\TH:i:s.vP'),
                    'complete' => $game->status === 'finished',
                    'winners' => array_reverse(array_slice($rows, $total - $game->podium_step, $game->podium_step))];
            }
            if ($q && $game->status !== 'lobby') {
                $result['question'] = ['id' => $q->id, 'text' => $q->text, 'image' => $q->image, 'choices' => $q->choices, 'duration' => $q->duration];
                $result['answer_count'] = $q->answers()->count();
                if ($participant) {
                    $own = $q->answers()->where('participant_id', $participant->id)->first();
                    $result['my_answer'] = $own ? ['choice_index' => $own->choice_index] : null;
                }
                if ($showQuestionResult) {
                    $result['question']['correct_index'] = $q->correct_index;
                    $result['distribution'] = array_map(function ($i) use ($q) { return $q->answers()->where('choice_index', $i)->count(); }, array_keys($q->choices));
                    if ($participant && isset($own) && $own) $result['my_answer'] = ['choice_index' => $own->choice_index, 'score' => $own->score, 'is_correct' => $own->is_correct];
                }
            }
            // Before each host reveal, neither scores nor unrevealed ranks leave the server.
            if ($showQuestionResult) $result['leaderboard'] = $this->leaderboard($game);
            return $result;
        }, 3);
    }
}
