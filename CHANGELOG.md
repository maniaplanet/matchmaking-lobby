# 3.0.2

* Adding support for masters
* Minor fixes

# 3.0.1

Hotfixes

# 3.0.0

* New UI
* Many bug fix
* Support "waves" for MatchMaker
* Many more :-)

# 2.2.0

### Lobby

 * Player list is now sorted on Player's LP

### Match

 * Current server map is registered in database

# 2.1.0

### Lobby

* Player who cancel more than 3 matches start are now banned

# 2.0.0

* MatchMaking function improvment 
* Better performances
* New penalties system (banned player have to stay online during their ban period)
* Every texts are translated in English and French

### Lobby

* Added a nicer window when going to match
* Added average waiting time before match
* Players can cancel when choosen to replace someone else
* Adding command /setAllReady to set all players ready on lobby (admin only)
* Adding command /kickNotReady to kick all not ready players (admin only)
* Adding command /setPenalty <login> to reset the penalty of an user (admin only)
* Adding command /resetAllPenalties to reset the penalties of all players (admin only)

### Match

* Quit dialog is cutomized to show a warning
* Removing "Give up" button
* Allowing kick (ratio 0.6)

# 1.2.2

* Fix bug which can prevent games from being cancelled

# 1.2.1

* Adding a manialive version check to avoid compatibility issues

# 1.2

* Removed feedback button

### Lobby

* FIX: cancelling a match may result in a ban of the whole team
* NEW: message when a player cancel a match start in the lobby
* NEW: message when a player have more ally than he should

### Match

* Teams are now forced on dedicated side

# 1.1

Fixes many loop redirection issues

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
