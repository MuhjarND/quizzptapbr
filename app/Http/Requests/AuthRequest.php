<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AuthRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        $rules = ['email' => 'required|email|max:255', 'password' => 'required|string'];
        if ($this->routeIs('register.store')) { $rules['name'] = 'required|string|max:60'; $rules['email'] .= '|unique:users'; $rules['password'] .= '|min:8|confirmed'; }
        return $rules;
    }
    public function messages() { return ['email.unique' => 'Email ini sudah terdaftar.', 'password.confirmed' => 'Konfirmasi kata sandi tidak sama.', 'password.min' => 'Kata sandi minimal 8 karakter.', 'required' => ':attribute wajib diisi.', 'email.email' => 'Masukkan email yang valid.']; }
}
