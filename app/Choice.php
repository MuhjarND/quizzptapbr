<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Choice extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['is_correct' => 'boolean'];
    public function question() { return $this->belongsTo(Question::class); }
}
