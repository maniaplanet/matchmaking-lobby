<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

abstract class AbstractAllies extends \ManiaLib\Utils\Singleton implements MatchMakerInterface
{
	protected $matchedPlayers = array();

	function run(array $players = array())
	{
		$teams = $this->getTeams($players);

		return $this->getMatches($teams);
	}

	function getTeams(array $players = array())
	{
		$teams = array();
		$matchableTeams = array();
		$matchableTeamsScore = array();
		$matches = array();

		$teamSize = $this->getPlayersPerMatch() / 2;

		$playersObject = array_map('\ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo::Get', $players);

		$playersObject = array_filter($playersObject, function ($player) use($teamSize) { return (count($player->allies) != 0 && count($player->allies) <= $teamSize-1); });

		usort($playersObject, function ($a, $b)
		{
			return count($b->allies) - count($a->allies);
		});

		foreach ($playersObject as $player)
		{
			$this->matchedPlayers[] = $player->login;
			$team = array($player->login);
			foreach ($player->allies as $ally)
			{
				$team[] = $ally;
				$this->matchedPlayers[] = $ally;
			}
			sort($team);
			$teams[] = $team;
		}

		$teams = array_map(function ($team){ return serialize($team); }, $teams);

		$teams = array_count_values($teams);

		krsort($teams);

		foreach ($teams as $team => $size)
		{
			$team = unserialize($team);
			if ($size == $teamSize)
			{
				$matchableTeams[] = $team;
			}
			else if ($size < $teamSize)
			{
				$missingCount = $teamSize - count($team);
				$closePlayers = $this->findClosePlayer($team, array_diff($players, $this->matchedPlayers), $missingCount);

				if ($closePlayers)
				{
					$this->matchedPlayers = array_merge($this->matchedPlayers, $closePlayers);
					$team = array_merge($team, $closePlayers);

					$matchableTeams[] = $team;
				}
			}
		}

		//There are a few players not in teams
		return array_merge($matchableTeams, $this->getFallbackMatchMaker()->getTeams(array_diff($players, $this->matchedPlayers)));
	}

	public function getMatches(array $teams = array())
	{
		return $this->getFallbackMatchMaker()->getMatches($teams);
	}

	/**
	 * Return the exact number of players
	 * @param array $closeTo
	 * @param array $availablePlayers
	 * @param type $number
	 * @returns array
	 */
	protected function findClosePlayer($closeTo, $availablePlayers, $number)
	{
		if (count($availablePlayers) < $number)
		{
			return array();
		}
		return array_map(function ($index) use ($availablePlayers) { return $availablePlayers[$index]; },
				(array) array_rand($availablePlayers, $number));
	}

	/**
	 * @returns MatchMakerInterface
	 */
	protected abstract function getFallbackMatchMaker();
}

?>