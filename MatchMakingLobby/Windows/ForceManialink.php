<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

class ForceManialink extends \ManiaLive\Gui\Window
{

	private $xml;

	protected function onConstruct()
	{
		$this->xml = new \ManiaLive\Gui\Elements\Xml();
		$this->addComponent($this->xml);
	}

	function set($to)
	{
		$this->xml->setContent('<script><!--main() { OpenLink("'.$to.'", CMlScript::LinkType::ManialinkBrowser); }--></script>');
	}

}

?>
