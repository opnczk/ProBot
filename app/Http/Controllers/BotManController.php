<?php

namespace App\Http\Controllers;

use App;
use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;
use App\Conversations\FootballGameResultConversation;

class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');
        // Appel a un service pour mettre à jour les resultats
        // J'ai crée ce service car je n'ai pas trouvé d'API ou de dataset avec des résultats récents.
        App::make('App\Services\FootballResultsService')->fetchResultsFromLFPwebsite(true);

        $botman->hears('{message}', function($botman, $message) {
          //j'ai préféré utiliser un déclencheur de conversation très libre, j'aurai aussi pu créer un menu persistent avec addPersistentMenu
          // par ailleurs, encapsuler toute la logique du bot dans une classe dérivée de conversation me semblait pertinent, même pour une logique si simple
          $botman->startConversation(new FootballGameResultConversation);
        });

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new ExampleConversation());
    }
}
