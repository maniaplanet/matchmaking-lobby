MatchMaking Lobby
=================

ManiaLive plugins to manage match making across multiple servers.

Download : https://github.com/ManiaPlanet/MatchMakingLobby/tags

Feedbacks or questions : http://forum.maniaplanet.com/viewtopic.php?f=435&t=16768

Requirements
------------
* At least **two ManiaPlanet servers**
* Two instances of **ManiaLive 2.7.1** or higher
* **MySQL** database

Installation
------------
- Start at least two ManiaPlanet servers. Please [refer to the wiki](http://wiki.maniaplanet.com/en/Dedicated_servers) for further information.
Let's say the two dedicated login are *myLobbyServer* (running on port *5005*) and *myMatchServer01* (running on port *5010*).

- Download [latest manialive](https://code.google.com/p/manialive/downloads/list).

- Download [latest matchmaking plugin](https://github.com/ManiaPlanet/MatchMakingLobby/tags).

- Extract plugin zip in `/ManiaLiveInstallDir/ManiaLivePlugins/` (in order to have something like `/ManiaLiveInstallDir/MatchMakingLobby/Lobby/`).

- Create a config file for the lobby server. The minimal config file (*ManiaLive/config/config-lobby.ini*) is : 

```
server.host = 'localhost'
server.port = 5005
config.enableToggleGUI = Off
manialive.plugins[] = 'MatchMakingLobby\Lobby'
ManiaLivePlugins\MatchMakingLobby\Config.lobbyLogin = myLobbyServer
```
   
- Start manialive for this server : `php bootstrapper.php --manialive_cfg=config-lobby.ini`.
   
- Create a config file for match server with the Match plugin loaded. The minimal config file (*ManiaLive/config/config-match.ini*) is : 

```
server.host = 'localhost'
server.port = 5010
config.enableToggleGUI = Off
manialive.plugins[] = 'MatchMakingLobby\Match'
ManiaLivePlugins\MatchMakingLobby\Config.lobbyLogin = myLobbyServer
```

- Start manialive for this server : `php bootstrapper.php --manialive_cfg=config-match.ini`.

- Start more match servers with the Match plugin.

It does not work!
-----------------
Ask your question of the [dedicated post on the forum](http://forum.maniaplanet.com/viewtopic.php?f=463&t=16851).

How can I make matchmaking for a my team mode ? 
-----------------------------------------------
It's fairly easy! You may need to write a few lines of PHP.
Let's say you want to create for the script **MassiveFrenzy** which is a 12 vs 12 team mode.

- First customize the match maker for the needed number of players. Go to the folder `MatchMakingLobby\MatchMakingLobby\Lobby\MatchMakers`
- Create a file named `MassiveFrenzy.php` with the folowing content :

```
<?php
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

class MassiveFrenzy extends AbstractAllies
{
	function getNumberOfTeam()
	{
		return 2;
	}

	function getPlayersPerMatch()
	{
		return 24;
	}

	protected function getFallbackMatchMaker()
	{
		return DistanceElite::getInstance();
	}

}
?>
```

If you do not want to use the allies system, just replace `AbstractAllies`  with `AbstractLadderPointsDistance`.

- Create the matchmaking setting. Create a file named `MassiveFrenzy.php` in `MatchMakingLobby\MatchMakingLobby\MatchSettings\` with this content :

```
<?php
namespace ManiaLivePlugins\MatchMakingLobby\MatchSettings;

class MassiveFrenzy implements MatchSettings
{
	public function getLobbyScriptSettings()
	{
		$rules = array('S_UseLobby' => true);
		return $rules;
	}

	public function getMatchScriptSettings()
	{
		$rules = array('S_Mode' => false);
		$rules['S_Mode'] = 1;
		$rules['S_WarmUpDuration'] = 15;
		return $rules;
	}	
}

?>
```

How can I make my own matchmaker function ?
-------------------------------------------	
Long story short : you should make a class that implement the interface `ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers\MatchMakerInterface`

We have built some classes to help you doing this. 