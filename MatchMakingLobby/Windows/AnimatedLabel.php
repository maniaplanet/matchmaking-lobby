<?php

/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;
class AnimatedLabel extends Label
{
	function onConstruct()
	{
		$this->label->setId('animated-label');
	}
	function onDraw()
	{
		\ManiaLive\Gui\Manialinks::appendXML('<script>#RequireContext CGameManialinkScriptHandler
#Include "MathLib" as MathLib&#13;
main() {&#13;
	declare CMlLabel label &lt;=&gt; (Page.MainFrame.GetFirstChild("animated-label") as CMlLabel);&#13;
	while(True) { label.Scale = 2+MathLib::Cos(CurrentTime*.002); yield; }&#13;
}</script>');
	}
}

?>