<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class Question extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    public function quiz() { return $this->belongsTo(Quiz::class); }
    public function choices() { return $this->hasMany(Choice::class)->orderBy('position'); }
}
