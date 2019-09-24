<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFootballGameResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('football_game_results', function (Blueprint $table) {
            $table->increments('id');
            $table->string("gameDate");
            $table->string("gameTime");
            $table->integer('dayId');
            $table->string("dayName");
            $table->string("seasonName");
            $table->integer('seasonId');
            $table->string("homeTeam");
            $table->string("results");
            $table->string("visitorTeam");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('football_game_results');
    }
}
