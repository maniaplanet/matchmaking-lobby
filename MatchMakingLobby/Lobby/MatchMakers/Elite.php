<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\Services\Match;
use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;

class Elite extends AbstractMatchMaker
{

	protected $isTeamMode = true;
	public $playerPerMatch = 6;

	const DISTANCE_THRESHOLD = 2000;

	public function getPlayerScore($login)
	{
		return \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login)->ladderStats['PlayerRankings'][0]['Score'];
	}

	/**
	 * @param PlayerInfo $p1
	 * @param PlayerInfo $p2
	 */
	protected function distance($p1, $p2)
	{
		// If players are allies there is no distance between them
		if(in_array($p2->login, $p1->allies))
		{
			return -1;
		}
		return parent::distance($p1, $p2);
	}

	protected function distributePlayers(Match $m)
	{
		$players = $m->players;

		usort($players,
			function ($a, $b)
			{
				$pa = PlayerInfo::Get($a);
				$pb = PlayerInfo::Get($b);
				if($pa->ladderPoints == $pb->ladderPoints)
				{
					return 0;
				}
				return ($pa->ladderPoints > $pb->ladderPoints) ? -1 : 1;
			}
		);
		$playersInfo = array();

		foreach($players as $player)
		{
			$playersInfo[$player] = PlayerInfo::Get($player);
		}

		$alliesCountPlayers = array();
		foreach($playersInfo as $player)
		{
			if(count($player->allies) == 2)
			{
				$alliesCountPlayers[2][] = $player->login;
			}
			elseif(count($player->allies) == 1)
			{
				$alliesCountPlayers[1][] = $player->login;
			}
		}
		$teamNumber = false;
		foreach($players as $key => $player)
		{
			/* @var $player \DedicatedApi\Structures\Player */
			$m->addPlayerInTeam($player, $teamNumber);
			if($key % 2 == 0) $teamNumber = !$teamNumber;
		}


		if(isset($alliesCountPlayers[2]) && count($alliesCountPlayers[2])) $ally = array_shift($alliesCountPlayers[2]);
		elseif(isset($alliesCountPlayers[1]) && count($alliesCountPlayers[1])) $ally = array_shift($alliesCountPlayers[1]);
		else $ally = null;

		if($ally)
		{
			$allies = $playersInfo[$ally]->allies;
			$allies[] = $ally;

			if(array_search($ally, $m->team1)!== false)
			{
				list($m->team2, $m->team1) = $this->teamSwitch($m->team2, $m->team1, $allies);
			}
			elseif(array_search($ally, $m->team2)!== false)
			{
				list($m->team1, $m->team2) = $this->teamSwitch($m->team1, $m->team2, $allies);
			}
		}

		return $m;
	}

	protected function teamSwitch(array $aTeam, array $bTeam, array $fixPlayers)
	{
		$movingAllies = array_diff($fixPlayers, $bTeam);
		foreach($bTeam as $key => $player)
		{
			if(in_array($player, $fixPlayers))
			{
				continue;
			}
			$allyKey = array_search(current($movingAllies), $aTeam);
			$tmp = $aTeam[$allyKey];
			$aTeam[$allyKey] = $player;
			$bTeam[$key] = $tmp;
			if(!next($movingAllies))
			{
				break;
			}
		}

		return array($aTeam, $bTeam);
	}

	public function getBackups(array $quitters)
	{
		$readyPlayers = PlayerInfo::GetReady();
		//Remove players in match from this
		$matchMakingService = new \ManiaLivePlugins\MatchMakingLobby\Services\MatchMakingService();
		$readyPlayers = array_filter($readyPlayers,
			function ($readyPlayer) use($matchMakingService)
			{
				return !$matchMakingService->isInMatch($readyPlayer->login);
			});
		$backups = array();
		if(count($quitters) <= count($readyPlayers))
		{
			$backups = array();
			foreach($quitters as $quitter)
			{
				$quitterInfo = PlayerInfo::Get($quitter);
				// Sort ready players to have the one with the same level
				usort($readyPlayers,
					function (PlayerInfo $p1, PlayerInfo $p2) use ($quitterInfo)
					{
						$dist1 = abs($quitterInfo->ladderPoints - $p1->ladderPoints);
						$dist2 = abs($quitterInfo->ladderPoints - $p2->ladderPoints);
						echo "{$quitterInfo->ladderPoints}|{$p1->ladderPoints}|{$p2->ladderPoints}\n";
						if($dist1 == $dist2)
						{
							return 0;
						}
						return ($dist1 < $dist2) ? -1 : 1;
					}
				);
				$player = array_shift($readyPlayers);
				$backups[] = $player->login;
			}
		}
		return $backups;
	}

}

?>