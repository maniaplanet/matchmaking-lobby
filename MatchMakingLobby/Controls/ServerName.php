<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Controls;

use ManiaLib\Gui\Elements;

class ServerName extends \ManiaLive\Gui\Control
{
	static protected $serverName;
	
	/**
	 * @var Elements\Quad
	 */
	protected $background;
	
	/**
	 *
	 * @var Elements\Label
	 */
	protected $text;
		
	static function setServerName($serverName)
	{
		static::$serverName = $serverName;
	}
	
	function __construct()
	{
		$this->setSize(100, 15);
		
		$this->background = new Elements\Quad(100, 15);
		$this->background->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad-wide.png',true);
		$this->addComponent($this->background);
		
		$this->text = new Elements\Label(90, 15);
		$this->text->setStyle(Elements\Label::TextTitle1);
		$this->text->setTextEmboss();
		$this->text->setAlign('center', 'center');
		$this->addComponent($this->text);
	}
	
	function onDraw()
	{
		$this->background->setSize($this->sizeX, $this->sizeY);
		$this->text->setSize($this->sizeX - 5);
		$this->text->setPosition($this->sizeX / 2, - $this->sizeY / 2);
		$this->text->setText(static::$serverName);
	}
}

?>
