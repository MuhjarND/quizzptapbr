<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizArenaTables extends Migration
{
    public function up()
    {
        Schema::create('quizzes', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->string('title', 120); $t->text('description')->nullable();
            $t->string('cover')->nullable(); $t->string('theme', 20)->default('islands');
            $t->string('status', 10)->default('draft'); $t->timestamps();
            $t->index(['user_id', 'status']);
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $t->text('text'); $t->string('image')->nullable(); $t->unsignedSmallInteger('duration')->default(20);
            $t->unsignedInteger('position'); $t->unique(['quiz_id', 'position']);
        });
        Schema::create('choices', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignId('question_id')->constrained()->onDelete('cascade');
            $t->string('text', 300); $t->boolean('is_correct'); $t->unsignedTinyInteger('position');
            $t->unique(['question_id', 'position']);
        });
        Schema::create('game_sessions', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->unsignedBigInteger('quiz_id')->nullable();
            $t->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('set null');
            $t->string('title', 120); $t->string('pin', 6); $t->string('active_pin', 6)->nullable()->unique();
            $t->string('status', 20)->default('lobby'); $t->unsignedInteger('current_position')->default(0);
            $t->unsignedInteger('version')->default(0);
            $t->dateTime('started_at', 3)->nullable(); $t->dateTime('deadline_at', 3)->nullable();
            $t->dateTime('finished_at')->nullable(); $t->timestamps(); $t->index(['user_id', 'status']);
        });
        Schema::create('session_questions', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignId('game_session_id')->constrained()->onDelete('cascade');
            $t->text('text'); $t->string('image')->nullable(); $t->unsignedSmallInteger('duration');
            $t->json('choices'); $t->unsignedTinyInteger('correct_index'); $t->unsignedInteger('position');
            $t->unique(['game_session_id', 'position']);
        });
        Schema::create('participants', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignId('game_session_id')->constrained()->onDelete('cascade');
            $t->string('nickname', 30); $t->string('nickname_key', 30); $t->string('token_hash', 64)->unique();
            $t->timestamps(); $t->unique(['game_session_id', 'nickname_key']);
        });
        Schema::create('answers', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->foreignId('participant_id')->constrained()->onDelete('cascade');
            $t->foreignId('session_question_id')->constrained()->onDelete('cascade');
            $t->unsignedTinyInteger('choice_index'); $t->boolean('is_correct');
            $t->unsignedInteger('elapsed_ms'); $t->unsignedSmallInteger('score'); $t->timestamps();
            $t->unique(['participant_id', 'session_question_id']);
        });
    }

    public function down()
    {
        foreach (['answers', 'participants', 'session_questions', 'game_sessions', 'choices', 'questions', 'quizzes'] as $table) Schema::dropIfExists($table);
    }
}
