<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class SessionQuestion extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['choices' => 'array', 'correct_index' => 'integer', 'position' => 'integer', 'duration' => 'integer'];
    protected $hidden = ['correct_index'];
    public function gameSession() { return $this->belongsTo(GameSession::class); }
    public function answers() { return $this->hasMany(Answer::class); }
}
