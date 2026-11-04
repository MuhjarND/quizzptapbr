<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class GameSession extends Model
{
    protected $guarded = ['id'];
    protected $dateFormat = 'Y-m-d H:i:s.v';
    protected $casts = ['user_id' => 'integer', 'quiz_id' => 'integer', 'current_position' => 'integer', 'version' => 'integer', 'podium_step' => 'integer', 'podium_step_at' => 'datetime', 'started_at' => 'datetime', 'deadline_at' => 'datetime', 'finished_at' => 'datetime'];
    public function quiz() { return $this->belongsTo(Quiz::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function questions() { return $this->hasMany(SessionQuestion::class)->orderBy('position'); }
    public function participants() { return $this->hasMany(Participant::class); }
    public function currentQuestion() { return $this->questions()->where('position', $this->current_position)->first(); }
}
