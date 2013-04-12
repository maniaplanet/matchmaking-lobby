# 1.1

### Lobby

* Showing message "No server available" for all ready players (if no server are available)
* Not moving players to spectator after being not ready for a while
* Canceling match before it starts does not give any penalties

### Match

* Disabling all votes on match server (kick, ban, balance, setScriptSettings)

# 1.0

* Adding combo & heroes matchmaker
* Stability fixes


# 0.3
Please note that *this version requires database change* AND *ManiaLive > r505* (or 2.7.1)

This version improves performance and stability

### General
* NEW: Countdown in label is now handled with ManiaScript
* NEW: Label class has now 3 boolean switches to handle various ManiaScript effects

### Lobby
* Improved match making function to do fairer teams
* FIX: Optimization
* NEW: When Hitting F7 players can have help
* NEW: Players not ready during a certain time are switched to spec and help is displayed

### Match
* FIX: When an entire team is empty, the match is now cancelled

# 0.2

Please note that *this version requires database change* AND *ManiaLive > r498* (or 2.7.1)

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