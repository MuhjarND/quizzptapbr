<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class JoinRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() { return ['pin' => 'required|digits:6', 'nickname' => 'required|string|min:2|max:30|regex:/^[\pL\pN _.-]+$/u', 'character'=>['sometimes','required',\Illuminate\Validation\Rule::in(array_keys(config('characters')))]]; }
    public function attributes() { return ['character'=>'Karakter']; }
    public function messages() { return ['pin.digits' => 'PIN permainan harus terdiri dari 6 angka.', 'nickname.regex' => 'Gunakan huruf, angka, spasi, titik, tanda hubung, atau garis bawah.', 'nickname.required' => 'Masukkan nama panggilan Anda.']; }
}
