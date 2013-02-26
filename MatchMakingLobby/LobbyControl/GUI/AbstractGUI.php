<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\LobbyControl\GUI;

use ManiaLive\Gui\Windows\Shortkey;
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match;

abstract class AbstractGUI extends \ManiaLib\Utils\Singleton
{

	public $actionKey = Shortkey::F6;
	public $lobbyBoxPosY = 0;
	public $displayAllies = false;

	abstract function getNotReadyText();

	abstract function getReadyText();

	abstract function getPlayerBackLabelPrefix();

	abstract function getLaunchMatchText(Match $m, $player);

	abstract function getMatchInProgressText();
	
	abstract function getBadKarmaText($time);
}

?>