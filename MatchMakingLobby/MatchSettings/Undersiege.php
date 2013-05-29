<?php
namespace ManiaLivePlugins\MatchMakingLobby\MatchSettings;

class Undersiege implements MatchSettings
{
    public function getLobbyScriptSettings()
    {
        $rules = array('S_UseLobby' => true);
        return $rules;
    }

    public function getMatchScriptSettings()
    {
        $rules = array('S_UseLobby' => true);
        $rules['S_WarmUpDuration'] = 30;
  	$rules['S_NbRoundMax'] = 5;
        return $rules;
    }   
}

?>
