<?php
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

class Undersiege extends AbstractAllies
{
    function getNumberOfTeam()
    {
        return 5;
    }

    function getPlayersPerMatch()
    {
        return 10;
    }

    protected function getFallbackMatchMaker()
    {
        return DistanceElite::getInstance();
    }

}
?>
