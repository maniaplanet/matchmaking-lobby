<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\GUI;

use ManiaLivePlugins\MatchMakingLobby\Services\Match;
use ManiaLive\Data\Storage;

class ElitePractice extends Joust
{
	function showMatchSumUp(Match $match, $receiver, $time)
	{
		$storage = Storage::getInstance();
		$getPlayerInfosCallback = function ($login) use ($storage)
			{
				$p = $storage->getPlayerObject($login);
				$pathArray = explode('|', $p->ladderStats['PlayerRankings'][0]['Path']);
				$path = implode('|', array_slice($pathArray, 0, 3));
				return (object) array(
					'login' => $login,
					'nickname' => ($p ? $p->nickName : $login),
					'zone' => ($p ? array_pop($pathArray) : 'World'),
					'rank' => ($p ? $p->ladderStats['PlayerRankings'][0]['Ranking'] : -1),
					'zoneFlag' => sprintf('file://ZoneFlags/Login/%s/country', $login),
					'ladderPoints' => $p->ladderStats['PlayerRankings'][0]['Score'],
					'echelon' => floor($p->ladderStats['PlayerRankings'][0]['Score'] / 10000)
				);
			};
		$sortPlayerCallback = function ($player1, $player2)
		{
			if($player1->ladderPoints == $player2->ladderPoints)
			{
				return 0;
			}
			return $player1->ladderPoints < $player2->ladderPoints ? 1 : -1;
		};
		$players = array_map($getPlayerInfosCallback, $match->players);
		usort($players, $sortPlayerCallback);
		
		$window = \ManiaLivePlugins\MatchMakingLobby\Windows\StartSoloMatch::Create($receiver);
		$window->set($players, $time);
		$window->show();
	}
}
?>