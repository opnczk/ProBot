<?php

namespace App\Repositories;

use App\FootballGameResult;

class FootballGameResultRepository
{
    public function all()
    {
        return FootballGameResult::all();
    }

    public function getTeamsForCurrentSeason(){
      $homeQuery = FootballGameResult::query();
      $visitorQuery = FootballGameResult::query();
      $homeQuery->select('homeTeam')->distinct();
      $visitorQuery->select('visitorTeam')->distinct();

      $homeTeams = $homeQuery->get()->map(function ($result) {
          return $result->homeTeam;
      });
      $visitorTeams = $visitorQuery->get()->map(function ($result) {
          return $result->visitorTeam;
      });

      $teams = array_unique(array_merge($homeTeams->toArray(), $visitorTeams->toArray()));

      return $teams;
    }

    public function getGameResultsForLastDayOfCurrentSeason($limit){
      $query = FootballGameResult::query();
      $query->where("seasonId", FootBallGameResult::max("seasonId"));
      $query->whereNotNull('results')->where('results', '!=', "");
      $query->where("dayId", FootBallGameResult::whereNotNull('results')->where('results', '!=', "")->where("seasonId", FootBallGameResult::max("seasonId"))->max('dayId'));
      $query->limit($limit);
      return $query->get();
    }

    public function getLastGameResultsForTeam($teamName, $limit){
      $query = FootballGameResult::query();
      $query->where("seasonId", FootBallGameResult::max("seasonId"));
      $query->whereNotNull('results')->where('results', '!=', "");
      $query->where(function($query) use ($teamName){
        return $query->where('homeTeam', $teamName)->orWhere('visitorTeam', $teamName);
      });
      $query->orderBy('seasonId', "DESC");
      $query->orderBy('dayId', "DESC");
      $query->limit($limit);
      return $query->get();
    }

    public function checkMessageForTeams($message){
      $wordsInMessage = explode(" ", $message);

      $homeQuery = FootballGameResult::query();
      $visitorQuery = FootballGameResult::query();
      $homeQuery->select('homeTeam')->distinct();
      $visitorQuery->select('visitorTeam')->distinct();
      foreach ($wordsInMessage as $word) {
        if(strlen($word) > 3){
          $homeQuery->orWhere('homeTeam', 'like', "%".$word."%");
          $visitorQuery->orWhere('visitorTeam', 'like', "%".$word."%");
        }
      }

      $homeTeams = $homeQuery->get()->map(function ($result) {
          return $result->homeTeam;
      });
      $visitorTeams = $visitorQuery->get()->map(function ($result) {
          return $result->visitorTeam;
      });

      $teams = array_unique(array_merge($homeTeams->toArray(), $visitorTeams->toArray()));

      return $teams;
    }
}
