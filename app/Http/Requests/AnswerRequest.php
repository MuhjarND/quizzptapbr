<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AnswerRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return ['question_id' => 'required|integer|min:1', 'choice_index' => 'required|integer|between:0,3']; }
}
