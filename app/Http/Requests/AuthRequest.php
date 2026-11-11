<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AuthRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return ['email' => 'required|email|max:255', 'password' => 'required|string'];
    }
    public function messages() { return ['required' => ':attribute wajib diisi.', 'email.email' => 'Masukkan email yang valid.']; }
}
