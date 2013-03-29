MatchMaking Lobby
=================

ManiaLive plugins to manage match making across multiple servers.

Download : https://github.com/ManiaPlanet/MatchMakingLobby/tags

Requirements
------------
* At least **two ManiaPlanet servers**
* Two instances of **ManiaLive 2.7.1** or higher
* **MySQL** database

Installation
------------
- Start at least two ManiaPlanet servers. Please [refer to the wiki](http://wiki.maniaplanet.com/en/Dedicated_servers) for further information.
Let's say the two dedicated login are *myLobbyServer* running on port *5005* and *myMatchServer01* running on port *5010*

- Download [latest manialive](https://code.google.com/p/manialive/downloads/list) 

- Download[latest matchmaking plugin](https://github.com/ManiaPlanet/MatchMakingLobby/tags).

- Extract match making plugin zip in `/ManiaLiveInstallDir/ManiaLivePlugins/` (in order to have something like `/ManiaLiveInstallDir/MatchMakingLobby/Lobby/`)

- Create a config file for the lobby server. The minimal config file (*ManiaLive/config/config-lobby.ini*) is : 

```
server.host = 'localhost'
server.port = 5005
manialive.plugins[] = 'MatchMakingLobby\Lobby'
ManiaLivePlugins\MatchMakingLobby\Config.lobbyLogin = myLobbyServer
```
   
- Start manialive for this server : `php bootstrapper.php --manialive_cfg=config-lobby.ini`
   
- Create a config file for match server with the Match plugin loaded. The minimal config file (*ManiaLive/config/config-match.ini*) is : 

```
server.host = 'localhost'
server.port = 5010
manialive.plugins[] = 'MatchMakingLobby\Match'
ManiaLivePlugins\MatchMakingLobby\Config.lobbyLogin = myLobbyServer
```

- Start manialive for this server : `php bootstrapper.php --manialive_cfg=config-match.ini`

How can I make matchmaking for a my team mode ? 
-----------------------------------------------
It's fairly easy! You may need to write a few lines of PHP.
Let's say you want to create for the script **MassiveFrenzy** which is a 12 vs 12 team mode.

- First customize the match maker for the needed number of players. Go to the folder `MatchMakingLobby\MatchMakingLobby\Lobby\MatchMakers`
- Create a file `MassiveFrenzy.php` with the folowing content :

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

How can I make my own matchmaker function ?
-------------------------------------------	
Long story short : you should make a class that implement the interface `ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers\MatchMakerInterface`

We have built some classes to help you doing this. 