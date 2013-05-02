<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Utils;

class Dictionary
{

	static function build(array $messages)
	{
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dico = $dom->createElement('dico');
		$dom->appendChild($dico);
		foreach($messages as $language => $texts)
		{
			$lang = $dom->createElement('language');
			$lang->setAttribute('id', $language);
			$dico->appendChild($lang);

			foreach($texts as $key => $text)
			{
				$node = $dom->createElement($key);
				$node->appendChild($dom->createTextNode($text));
				$lang->appendChild($node);
			}
		}
		return $dom->saveXML();
	}
}

?>
