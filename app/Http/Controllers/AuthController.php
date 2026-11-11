<?php
namespace App\Http\Controllers;
use App\Http\Requests\AuthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function login() { return view('auth'); }
    public function authenticate(AuthRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        $request->session()->regenerate(); return redirect()->intended(route('dashboard'));
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/'); }
}
