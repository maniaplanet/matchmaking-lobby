<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Services;

class AllyEvent extends \ManiaLive\Event\Event
{
	const ON_ALLIES_CHANGED = 1;
	protected $login;
		
	function __construct($login)
	{
		parent::__construct(static::ON_ALLIES_CHANGED);
		
		$this->login = $login;
	}
	
	public function fireDo($listener)
	{
		$listener->onAlliesChanged($this->login);
	}
}

?>
