<?php
/**
 * COinS
 *
 * @copyright Copyright 2007-2019 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Coins\View\Helper
 */

// Include a name parser to parse human names.
// https://github.com/joshfraser/PHP-Name-Parser
include_once ('parser.php');


class Coins_View_Helper_Coins extends Zend_View_Helper_Abstract
{
	/**
	 * Return a COinS span tag for every passed item.
	 *
	 * @param array|Item An array of item records or one item record.
	 * @return string
	 */
	public function coins($items)
	{
		if (!is_array($items)) {
			return $this->_getCoins($items);
		}

		$coins = '';
		foreach ($items as $item) {
			$coins .= $this->_getCoins($item);
			release_object($item);
		}
		return $coins;
	}

	/**
	 * Build and return the COinS span tag for the specified item.
	 *
	 * @param Item $item
	 * @return string
	 */
	protected function _getCoins(Item $item)
	{
		$coins = array();

		$coins['ctx_ver'] = 'Z39.88-2004';
		$coins['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dc';
		$coins['rfr_id'] = 'info:sid/omeka.org:generator';

		// Populate rft_id key from Dublin Core:identifier.
		// E.g., https://en.wikipedia.org/wiki/Module_talk:Citation/CS1/COinS
		// May be used for identifiers such as doi, bibcode, pmid, etc.
		// E.g., DC Identifier metadata would be doi: followed by doi prefix and suffix.
		$identifier = $this->_getElementText($item, 'Identifier');
		if ((!empty($identifier)) or (!ctype_space($identifier))) {
			$protocol = strstr($identifier, ':', true);
			if ($protocol == 'http' or $protocol == 'https') {
				$coins['rft_id'] = $identifier;
			} else {
				$pos = strpos($identifier, ':');
				if ($pos !== false) {
					$identifier = substr_replace($identifier, '/', $pos, 1);
					$coins['rft_id'] = 'info:' . $identifier;
				}
			}
		}
		
		// Set the title key from Dublin Core:title.
		$title = $this->_getElementText($item, 'Title');
		if ((empty($title)) or (ctype_space($title))) {
			$title = '[unknown title]';
		}
		$coins['rft.title'] = $title;

		// Set the description key from Dublin Core:description.
		$description = $this->_getElementText($item, 'Description');
		if ((!empty($description)) or (!ctype_space($description))) {
			$coins['rft.description'] = $description;
		}

		// First try setting the type key from Omeka item type.
		// If the type key remains empty, set key from Dublin Core:type.
		// Map the type key to equivalent Zotero item type (i.e., COinS).
		// Item types not recognized by Zotero usually map as Web Page.
		$itemTypeName = metadata($item, 'item type name');
		if ((empty($itemTypeName)) or (ctype_space($itemTypeName))) {
			$itemTypeName = $this->_getElementText($item, 'Type');
		}
		if ((!empty($itemTypeName)) or (!ctype_space($itemTypeName))) {
			switch ($itemTypeName) {
				case 'Oral History':
				$type = 'interview';
				break;
			case 'Moving Image':
				$type = 'videoRecording';
				break;
			case 'Sound':
				$type = 'audioRecording';
				break;
			case 'Email':
				$type = 'email';
				break;
			case 'Website':
			case 'Webpage':
			case 'Web Page':
				$type = 'webpage';
				break;
			case 'Text':
			case 'Document':
				$type = 'document';
				break;
			case 'Journal Article':
				$type = 'journalArticle';
				break;
			case 'Magazine Article':
				$type = 'magazineArticle';
				break;
			case 'Newspaper Article':
				$type = 'newspaperArticle';
				break;
			case 'Book':
				$type = 'book';
				break;
			case 'Book Section':
				$type = 'bookSection';
				break;
			case 'Thesis':
				$type = 'thesis';
				break;
			case 'Report':
				$type = 'report';
				break;
			case 'Manuscript':
				$type = 'manuscript';
				break;
			case 'Map':
				$type = 'map';
				break;
			case 'Still Image':
			case 'Artwork':
				$type = 'artwork';
				break;
			case 'Software':
			case 'Computer Program':
				$type = 'computerProgram';
				break;
			default:
				$type = $itemTypeName;
			}
			$coins['rft.type'] = $type;
			$author = '';
		} else {
			$coins['rft.type'] = 'document';
		}

		// Set the creator key from Dublin Core:creator.
		// For certain item types, instead populate the author keys.
		// To populate the author keys, parse the creator(s) using a name parser.
		$creator = metadata($item, array('Dublin Core', 'Creator'), array('all' => true, 'no_escape' => true));
		if ((!empty($creator)) or (!ctype_space($creator))) {
			if (isset($author)) {
				$parser = new FullNameParser();
				$author = $parser->parse_name($creator[0]);
				$coins['rft.aufirst'] = $author['fname'];
				if (!empty($author['initials'])) {
					$coins['rft.aufirst'] .= ' ' . $author['initials'];
				}
				$coins['rft.aulast'] = $author['lname'];
				for ($i = 1; $i < count($creator); $i++) {
					$parser = new FullNameParser();
					$author = $parser->parse_name($creator[$i]);
					$coins['rft.au'][$i] = $author['fname'];
					if (!empty($author['initials'])) {
						$coins['rft.au'][$i] .= ' ' . $author['initials'];
					}
					$coins['rft.au'][$i] .= ' ' . $author['lname'];
				}
			} else {
				$coins['rft.creator'] = $creator;			
			}
		}

		// Set the Dublin Core elements that don't need special processing.
		$elementNames = array('Subject', 'Publisher', 'Contributor', 'Date', 'Format', 'Source', 'Language', 'Coverage', 'Rights', 'Relation');
		foreach ($elementNames as $elementName) {
			$elementText = $this->_getElementText($item, $elementName);
			if ((empty($elementText)) or (ctype_space($elementText))) {
				$elementText = '';
				continue;
			}
			$elementName = strtolower($elementName);
			$coins["rft.$elementName"] = $elementText;
		}

		// Set the identifier key as the absolute URL of the current page.
		$coins['rft.identifier'] = absolute_url();

		// Build and return the COinS span tag.
		$coinsSpan = '<span class="Z3988" title="';
		$coinsSpan .= html_escape(http_build_query($coins, null, '&', PHP_QUERY_RFC3986));
		$coinsSpan .= '"></span>';
		if (!empty($author)) {
			for ($i = 1; $i < count($creator); $i++) {
				$coinsSpan = str_replace("%5B" . $i . "%5D", '', $coinsSpan);
			}
		}
		return $coinsSpan;
	}

	/**
	 * Get the unfiltered element text for the specified item.
	 *
	 * @param Item $item
	 * @param string $elementName
	 * @return string|bool
	 */
	protected function _getElementText(Item $item, $elementName)
	{
		$elementText = metadata(
			$item,
			array('Dublin Core', $elementName),
			array('no_filter' => true, 'no_escape' => true, 'snippet' => 500)
		);
		return $elementText;
	}
}
