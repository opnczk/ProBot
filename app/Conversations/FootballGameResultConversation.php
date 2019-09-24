<?php
namespace App\Conversations;

use App;
use BotMan\BotMan\Messages\Conversations\Conversation;

use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ButtonTemplate;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;

use  BotMan\BotMan\Messages\Incoming\Answer;


class FootBallGameResultConversation extends Conversation {
  protected $typeOfResult;
  protected $selectedTeam;

  public function askTypeResult()
    {

      $buttonTemplate = ButtonTemplate::create('Quel type de résultats voulez-vous ?')
          ->addButton(ElementButton::create('Ceux d\'une équipe')
              ->type('postback')
              ->payload('TEAM')
          )
          ->addButton(ElementButton::create('Dernière journée')
              ->type('postback')
              ->payload('LAST_DAY')
          );

      $this->ask($buttonTemplate, function(Answer $answer) {
          $this->typeOfResult = $answer->getValue();

          if($this->typeOfResult == "Ceux d'une équipe" || $this->typeOfResult == "TEAM"){
            $this->ask("Quelle équipe vous intéresse", function(Answer $answer) {
              $teams = App::make('App\Repositories\FootballGameResultRepository')->checkMessageForTeams($answer->getText());
              $this->selectedTeam = $teams[0];
              $this->replyTeamLastResults($this->selectedTeam);
            });

          }else if($this->typeOfResult == "Dernière journée" || $this->typeOfResult == "LAST_DAY"){
            $this->replyLastDayResults();
          }
      });
    }

    public function replyLastDayResults(){
      $gameResults = App::make('App\Repositories\FootballGameResultRepository')->getGameResultsForLastDayOfCurrentSeason(100);

      $elements = array();
      $elements[] = 'Voici les résultats pour la '.$gameResults->first()->dayName.' de la saison'.$gameResults->first()->seasonName;

      foreach ($gameResults as $result) {
        $elements[] = $result->homeTeam.' '.$result->results.' '.$result->visitorTeam;
      }

      $this->sayElements($elements);
    }

    public function replyTeamLastResults($teamName){
      $gameResults = App::make('App\Repositories\FootballGameResultRepository')->getLastGameResultsForTeam($teamName, 10);

      $elements = array();
      $elements[] = 'Voici les derniers résultats de '.$teamName;

      foreach ($gameResults as $result) {
        $elements[] = $result->homeTeam.' '.$result->results.' '.$result->visitorTeam;
      }

      $this->sayElements($elements);
    }

    public function sayElements($elements){
      $template = ListTemplate::create()->useCompactView();
      foreach ($elements as $element) {
        $this->say($element);
        //$template->addElement(Element::create($element));
      }

      //$this->say("Voici les résultats : ");
      //$this->say($template);
    }

  public function run()
  {
      //$this->replyLastDayResults();
      $this->askTypeResult();
  }
}
