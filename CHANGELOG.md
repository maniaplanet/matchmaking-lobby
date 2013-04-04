# 0.3
Please note that *this version require ManiaLive > r502 (or 2.7.2)

### General
* NEW: Countdown in label is now handle with ManiaScript
* NEW: Label class has now 3 boolean switch to handle various ManiaScript effects

### Lobby
* FIX: Optimization with a lot of players connected

### Match
* FIX: When an entire team has quit or gave-up, the match is now cancel

# 0.2

Please note that *this version require database change* AND *ManiaLive > r498* (or 2.7.1)

##### Lobby
* NEW: Matchmaker now support allies. Go to maniaplanet live chat in order to set one of your buddy as ally
* NEW: Player's rank is now displayed in the player list
* NEW: Text is now different for ready players if there is enough players ready or not
* NEW: Rename AlliesElite in Elite and former Elite MatchMaker is now known as DistanceElite
* FIX: Backups player time before jumping has been increased
* FIX: Many little bugs fixed

##### Match
* FIX: Waiting time timer is now updated every 5 seconds

# 0.1

##### Lobby
* FIX: Restart the map at map start to fix bug of player not spawning

##### Match
* NEW: Infinite waiting backups time if _Config.waitingForBackups_ is equal to 2
* NEW: Match is over if an entire team leave the match