<?php
namespace App\Services;

use KubAT\PhpSimple\HtmlDomParser;
use App\FootballGameResult;
use App;
use Illuminate\Support\Carbon;

class FootballResultsService {

    public $urlLeagueName = "ligue1";

    public function fetchResultsFromLFPwebsite( $lastSeasonOnly = null){
      ini_set("memory_limit", "1536M");
      ini_set('max_execution_time', 0);
      set_time_limit(0);

      $html = file_get_contents('https://www.lfp.fr/'.$this->urlLeagueName.'/calendrier_resultat');
      $seasons = array_column(HtmlDomParser::str_get_html($html)->find('#saison > option'), "value");

      if(isset($lastSeasonOnly) && $lastSeasonOnly == true){
        $seasons = [max($seasons)];
      }

      if(boolval(FootballGameResult::count()) && (new Carbon(FootballGameResult::max('updated_at')))->diffInHours(Carbon::now()) < 24 ){
        return;
      }

      $allTheGames = array();
      foreach($seasons as $seasonId){
        if(boolval(FootballGameResult::where('seasonId', $seasonId)->count()) && !boolval(FootballGameResult::where('seasonId', $seasonId)->where('results', "")->count())){
          //there nothing to fetch for this season
          continue;
        }
        $seasonGames = $this->fetchWholeSeasonFromSeasonId($this->urlLeagueName, $seasonId);
        $allTheGames = array_merge($allTheGames, $seasonGames);
      }


    }

    private function fetchWholeSeasonFromSeasonId($leagueId, $seasonId){
      $allTheGames = array();
      $html = file_get_contents('https://www.lfp.fr/'.$this->urlLeagueName.'/calendrier_resultat?sai='.$seasonId);
      $daysIds = array_column(HtmlDomParser::str_get_html($html)->find('#journee > option'), "value");
      $seasonName = HtmlDomParser::str_get_html($html)->find('#saison > option[selected]', 0)->plaintext;

      foreach ($daysIds as $dayId) {
        if(FootballGameResult::where('seasonId', $seasonId)->where('dayId', $dayId)->count() != 0 && FootballGameResult::where('seasonId', $seasonId)->where('dayId', $dayId)->where('results', "")->count() == 0){
          continue;
        }

        $html = file_get_contents('https://www.lfp.fr/'.$this->urlLeagueName.'/calendrier_resultat?sai='.$seasonId.'&jour='.$dayId);

        $dayName = HtmlDomParser::str_get_html($html)->find('#journee > option[selected]', 0)->plaintext;

        $gameResultsTable = HtmlDomParser::str_get_html($html)->find('#tableaux_rencontres > div', 0);

        $gameResultsTable = $gameResultsTable->children();

        $gameDate = null;

        $gameIsNotEmpty = false;
        foreach($gameResultsTable as $child){

          if($child->tag == "h4"){
            $gameDate = $child->plaintext;
          }

          if($child->tag == "table"){

            foreach($child->find("tr") as $game){

              $gameModel = [
                'gameDate' => $gameDate,
                'dayId' => $dayId,
                'dayName' => $dayName,
                'seasonName' => $seasonName,
                'seasonId' => $seasonId
              ];

              foreach ($game->children() as $gameValue) {
                if($gameValue->tag == "th")
                  continue;
                if(isset($gameValue->class)){
                  if($gameValue->class == "horaire"){
                    $gameIsNotEmpty = true;
                    $gameModel['gameTime'] = trim($gameValue->plaintext);
                  }
                  if($gameValue->class == "domicile"){
                    $gameIsNotEmpty = true;
                    $gameModel['homeTeam'] = trim($gameValue->plaintext);
                  }
                  if($gameValue->class == "stats"){
                    $gameIsNotEmpty = true;
                    $gameModel['results'] = trim($gameValue->plaintext);
                  }
                  if($gameValue->class == "exterieur"){
                    $gameIsNotEmpty = true;
                    $gameModel['visitorTeam'] = trim($gameValue->plaintext);
                  }

                }
              }
              if($gameIsNotEmpty){
                //We try to retrieve the empty game
                $model = FootballGameResult::where('seasonId', $seasonId)->where('dayId', $dayId)->where('homeTeam', $gameModel['homeTeam'])->where('visitorTeam', $gameModel['visitorTeam'])->first();

                //Here we save the game result
                if(!isset($model))
                  $model = new FootballGameResult;
                  if(!isset($gameModel['gameTime']))
                    $gameModel['gameTime'] = "";
                foreach ($gameModel as $key => $value) {
                  $model->{$key} = $value;
                }
                $model->save();

                $gameIsNotEmpty = false;
                $model = null;
              }
            }

          }//end of game table

        }

      } //end of days in season
      return $allTheGames;
    }
}
