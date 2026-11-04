<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddCharacterToParticipants extends Migration
{
    public function up()
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('character', 24)->default('nala');
        });
    }
    public function down()
    {
        Schema::table('participants', function (Blueprint $table) { $table->dropColumn('character'); });
    }
}
