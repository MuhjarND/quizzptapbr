<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Participant extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['game_session_id' => 'integer'];
    protected $hidden = ['token_hash'];
    public function gameSession() { return $this->belongsTo(GameSession::class); }
    public function answers() { return $this->hasMany(Answer::class); }
}
