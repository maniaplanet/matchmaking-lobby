<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

class TooManyAllies extends \ManiaLive\Gui\Window
{
	/**
	 * @var Label
	 */
	public $message;
	
	protected $dico;

	protected function onConstruct()
	{
		$this->message = new \ManiaLib\Gui\Elements\Label(400);
		$this->message->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->message->setTextSize(5);
		$this->message->setHalign('center');
		$this->message->enableAutonewline();
		$this->message->setTextid('text');
		$this->addComponent($this->message);
	}

	public function setText($text)
	{
		$this->dico = $text;
	}
	
	protected function onDraw()
	{
		\ManiaLive\Gui\Manialinks::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::build($this->dico));
	}

}
?>