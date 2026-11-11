<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/', 'landing')->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', 'AuthController@login')->name('login');
    Route::post('/login', 'AuthController@authenticate')->middleware('throttle:8,1')->name('login.store');
});
Route::post('/logout', 'AuthController@logout')->middleware('auth')->name('logout');
Route::get('/gabung', 'GameController@joinForm')->name('join');
Route::post('/gabung', 'GameController@join')->middleware('throttle:15,1')->name('join.store');
Route::get('/main/{game}', 'GameController@play')->name('games.play');
Route::get('/main/{game}/state', 'GameController@state')->middleware('arena.throttle:90')->name('games.state');
Route::post('/main/{game}/answer', 'GameController@answer')->middleware('arena.throttle:30')->name('games.answer');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', 'QuizController@index')->name('dashboard');
    Route::resource('quizzes', 'QuizController')->except('show');
    Route::post('/quizzes/{quiz}/duplicate', 'QuizController@duplicate')->name('quizzes.duplicate');
    Route::post('/quizzes/{quiz}/games', 'GameController@create')->middleware('throttle:10,1')->name('games.create');
    Route::get('/host/{game}', 'GameController@host')->name('games.host');
    Route::get('/host/{game}/state', 'GameController@hostState')->middleware('arena.throttle:90')->name('games.host-state');
    Route::post('/host/{game}/control', 'GameController@control')->middleware('arena.throttle:30')->name('games.control');
    Route::get('/riwayat', 'GameController@history')->name('history');
    Route::get('/hasil/{game}', 'GameController@result')->name('games.result');
});
