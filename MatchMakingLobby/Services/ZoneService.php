<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Services;

class ZoneService
{
	static protected $flags = array();
	
	static function constructDataStore()
	{
		$config = \ManiaLive\Features\WebServices\Config::getInstance();
		$zones = new \Maniaplanet\WebServices\Zones($config->username, $config->password);
		$continents = $zones->getChildrenByPath('World');
		foreach($continents as $continent)
		{
			$countries = $zones->getChildren($continent->id, 0, 100);
			foreach($countries as $country)
			{
				self::$flags[$country->path] = $country->iconURL;
			}
		}
	}
	
	function __construct()
	{
		if(!count(self::$flags))
		{
			self::constructDataStore();
		}
	}
	
	function getFlag($path)
	{
		if(!array_key_exists($path, self::$flags))
			return '';
		
		return self::$flags[$path];
	}
}

?>
