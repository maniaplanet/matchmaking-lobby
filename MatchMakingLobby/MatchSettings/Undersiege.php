<?php
namespace ManiaLivePlugins\MatchMakingLobby\MatchSettings;

class Undersiege implements MatchSettings
{
    public function getLobbyScriptSettings()
    {
        $rules = array(
    		'S_UseLobby' => true,
			'S_LobbyTimePerMap' => 1800
		);
        return $rules;
    }

    public function getMatchScriptSettings()
    {
        $rules = array('S_UseLobby' => true);
        $rules['S_WarmUpDuration'] = 20;
  	    $rules['S_NbRoundMax'] = 5;
        return $rules;
    }   
}

?>
