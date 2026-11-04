<?php
namespace App\Http\Controllers;
use App\{Quiz, GameSession};
use App\Http\Requests\QuizRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class QuizController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::where('user_id', $request->user()->id)->withCount('questions');
        $search = mb_substr((string) $request->query('q', ''), 0, 120);
        if ($search !== '') $query->where('title', 'like', '%'.$search.'%');
        if (in_array($request->query('status'), ['draft', 'ready'])) $query->where('status', $request->query('status'));
        $quizzes = $query->latest()->paginate(9)->appends($request->only('q', 'status'));
        $stats = ['total' => Quiz::where('user_id', $request->user()->id)->count(), 'ready' => Quiz::where('user_id', $request->user()->id)->where('status', 'ready')->count(), 'finished' => GameSession::where('user_id', $request->user()->id)->where('status', 'finished')->count()];
        $activeGames = GameSession::where('user_id', $request->user()->id)->where('status', '!=', 'finished')->latest()->get();
        return view('dashboard', compact('quizzes', 'stats', 'activeGames'));
    }
    public function create() { return view('editor', ['quiz' => new Quiz(['theme' => 'islands', 'status' => 'draft'])]); }
    public function edit(Quiz $quiz) { $this->authorize('update', $quiz); $quiz->load('questions.choices'); return view('editor', compact('quiz')); }
    public function store(QuizRequest $request) { return $this->save($request, new Quiz(['user_id' => $request->user()->id])); }
    public function update(QuizRequest $request, Quiz $quiz) { $this->authorize('update', $quiz); return $this->save($request, $quiz); }
    private function save(QuizRequest $request, Quiz $quiz)
    {
        DB::transaction(function () use ($request, $quiz) {
            if ($quiz->exists) $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $quiz->fill($request->only('title', 'description', 'status', 'theme'));
            if ($request->hasFile('cover')) $quiz->cover = $request->file('cover')->store('covers', 'public');
            $quiz->save(); $existing = $quiz->questions()->get()->keyBy('id'); $quiz->questions()->delete();
            foreach ($request->input('questions') as $i => $input) {
                $image = isset($input['existing_id'], $existing[$input['existing_id']]) ? $existing[$input['existing_id']]->image : null;
                if ($request->hasFile('questions.'.$i.'.image')) $image = $request->file('questions.'.$i.'.image')->store('questions', 'public');
                $question = $quiz->questions()->create(['text' => $input['text'], 'duration' => $input['duration'], 'position' => $i + 1, 'image' => $image]);
                foreach (array_values($input['choices']) as $j => $text) $question->choices()->create(['text' => $text, 'position' => $j, 'is_correct' => $j === (int) $input['correct_index']]);
            }
        }, 3);
        return redirect()->route('quizzes.index')->with('status', 'Kuis berhasil disimpan.');
    }
    public function duplicate(Quiz $quiz)
    {
        $this->authorize('update', $quiz);
        DB::transaction(function () use ($quiz) {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $copy = $quiz->replicate(); $copy->title = mb_substr($quiz->title, 0, 108).' (salinan)'; $copy->status = 'draft'; $copy->save();
            foreach ($quiz->questions()->with('choices')->get() as $q) {
                $new = $q->replicate(); $new->quiz_id = $copy->id; $new->save();
                foreach ($q->choices as $choice) { $c = $choice->replicate(); $c->question_id = $new->id; $c->save(); }
            }
        }, 3);
        return back()->with('status', 'Kuis berhasil digandakan sebagai draft.');
    }
    public function destroy(Quiz $quiz) { $this->authorize('delete', $quiz); $quiz->delete(); return redirect()->route('quizzes.index')->with('status', 'Kuis dihapus. Hasil permainan tetap tersimpan.'); }
}
