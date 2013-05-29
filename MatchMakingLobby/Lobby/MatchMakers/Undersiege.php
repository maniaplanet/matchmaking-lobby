<?php
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

class Undersiege extends AbstractAllies
{
    function getNumberOfTeam()
    {
        return 2;
    }

    function getPlayersPerMatch()
    {
        return 10;
    }

    protected function getFallbackMatchMaker()
    {
        return DistanceHeroes::getInstance();
    }

}
?>
