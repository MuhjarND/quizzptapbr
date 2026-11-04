<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPodiumRevealToGameSessions extends Migration
{
    public function up()
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('podium_step')->default(0);
            $table->dateTime('podium_step_at', 3)->nullable();
        });
    }
    public function down()
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['podium_step', 'podium_step_at']);
        });
    }
}
