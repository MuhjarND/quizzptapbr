<?php
namespace App\Http\Controllers;
use App\User;
use App\Http\Requests\AuthRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function login() { return view('auth', ['register' => false]); }
    public function register() { return view('auth', ['register' => true]); }
    public function authenticate(AuthRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        $request->session()->regenerate(); return redirect()->intended(route('dashboard'));
    }
    public function store(AuthRequest $request)
    {
        $user = User::create(['name' => $request->name, 'email' => mb_strtolower($request->email), 'password' => Hash::make($request->password)]);
        Auth::login($user); $request->session()->regenerate(); return redirect()->route('dashboard')->with('status', 'Akun berhasil dibuat. Selamat datang di '.config('app.name').'!');
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/'); }
}
