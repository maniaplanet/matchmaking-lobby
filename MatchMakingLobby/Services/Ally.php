<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Services;

class Ally
{
	const TYPE_GENERAL = 1;
	const TYPE_LOCAL = 0;
	
	/** @var string */
	public $login;
	/** @var int */
	public $type;
	/** @var bool */
	public $isBilateral;
}

?>
