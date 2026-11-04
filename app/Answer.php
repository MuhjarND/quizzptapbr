<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Answer extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_correct' => 'boolean', 'participant_id' => 'integer', 'session_question_id' => 'integer', 'choice_index' => 'integer', 'score' => 'integer', 'elapsed_ms' => 'integer'];
    public function participant() { return $this->belongsTo(Participant::class); }
    public function sessionQuestion() { return $this->belongsTo(SessionQuestion::class); }
}
