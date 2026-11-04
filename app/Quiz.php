<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Quiz extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['user_id' => 'integer'];
    public function user() { return $this->belongsTo(User::class); }
    public function questions() { return $this->hasMany(Question::class)->orderBy('position'); }
    public function games() { return $this->hasMany(GameSession::class); }
    public function playable()
    {
        if ($this->status !== 'ready' || !$this->questions->count()) return false;
        foreach ($this->questions as $q) {
            if (!trim($q->text) || $q->choices->count() < 2 || $q->choices->count() > 4 || $q->choices->where('is_correct', true)->count() !== 1) return false;
        }
        return true;
    }
}
