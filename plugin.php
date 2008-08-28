<?php
add_plugin_hook('append_to_item_show', 'COinS');
add_plugin_hook('append_to_items_browse', 'COinSMultiple');

function COinS($item)
{
    $coins = new COinS($item);
    echo $coins->getCoinsSpan();
}

function COinSMultiple($items)
{
    foreach ($items as $item) {
        COinS($item);
    }
}

class COinS
{
    const COINS_SPAN_CLASS = 'Z3988';
    
    const CTX_VER = 'Z39.88-2004';
    
    const RFT_VAL_FMT = 'info:ofi/fmt:kev:mtx:dc';
    
    const RFR_ID = 'info:sid/omeka.org:generator';
    
    const ELEMENT_SET_DUBLIN_CORE = 'Dublin Core';
    
    const ELEMENT_TEXT_INDEX = 0;
    
    private $_item;
    
    private $_coins = array();
    
    private $_coinsSpan;
    
    public function getCoinsSpan()
    {
        return $this->_coinsSpan;
    }
    
    public function __construct($item)
    {
        $this->_item = $item;
        
        $this->_coins['ctx_ver']     = self::CTX_VER;
        $this->_coins['rft_val_fmt'] = self::RFT_VAL_FMT;
        $this->_coins['rfr_id']      = self::RFR_ID;
        
        $this->_setTitle();
        $this->_setCreator();
        $this->_setSubject();
        $this->_setDescription();
        $this->_setPublisher();
        $this->_setContributor();
        $this->_setDate();
        $this->_setType();
        $this->_setFormat();
        $this->_setIdentifier();
        $this->_setSource();
        $this->_setLanguage();
        $this->_setCoverage();
        $this->_setRights();
        $this->_setRelation();
        
        $this->_buildCoinsSpan();
    }
    private function _setTitle()
    {
        $this->_coins['rft.title'] = $this->_getElementText('Title');
    }
    private function _setCreator()
    {
        $this->_coins['rft.creator'] = $this->_getElementText('Creator');
    }
    private function _setSubject()
    {
        $this->_coins['rft.subject'] = $this->_getElementText('Subject');
    }
    private function _setDescription()
    {
        // Truncate long descriptions.
        $this->_coins['rft.description'] = substr($this->_getElementText('Description'), 0, 500);
    }
    private function _setPublisher()
    {
        $this->_coins['rft.publisher'] = $this->_getElementText('Publisher');
    }
    private function _setContributor()
    {
        $this->_coins['rft.contributor'] = $this->_getElementText('Contributor');
    }
    private function _setDate()
    {
        $this->_coins['rft.date'] = $this->_getElementText('Date');
    }
    /**
     * Use the type from the Item Type name, not the Dublin Core type name.
     * @todo: devise a better mapping scheme between Omeka and COinS/Zotero
     */
    private function _setType()
    {
        switch ($this->_item->Type->name) {
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
            case 'Hyperlink':
                $type = 'webPage';
                break;
            case 'Document':
            case 'Event':
            case 'Lesson Plan':
            case 'Person':
            case 'Interactive Resource':
            case 'Still Image':
            default:
                $type = 'document';
                break;
        }
        $this->_coins['rft.type'] = $type;
    }
    private function _setFormat()
    {
        $this->_coins['rft.format'] = $this->_getElementText('Format');
    }
    /**
     * Use the current script URI instead of the Dublin Core identifier.
     * @todo when running on localhost, $_SERVER['SCRIPT_URI'] does not return 
     * the full URI (http://localhost/...). current_uri(), url_for(), etc. 
     * don't return the full path either. Need to find some way to always get 
     * the full path of the current URL regardless of host.
     */
    private function _setIdentifier()
    {
        $this->_coins['rft.identifier'] = $_SERVER['SCRIPT_URI'];
    }
    private function _setSource()
    {
        $this->_coins['rft.source'] = $this->_getElementText('Source');
    }
    private function _setLanguage()
    {
        $this->_coins['rft.language'] = $this->_getElementText('Language');
    }
    private function _setCoverage()
    {
        $this->_coins['rft.coverage'] = $this->_getElementText('Coverage');
    }
    private function _setRights()
    {
        $this->_coins['rft.rights'] = $this->_getElementText('Rights');
    }
    private function _setRelation()
    {
        $this->_coins['rft.relation'] = $this->_getElementText('Relation');
    }
    
    private function _getElementText($elementName)
    {
        $elementTexts = item($elementName, 
                             array('element_set' => self::ELEMENT_SET_DUBLIN_CORE));
        return $elementTexts[count($elementTexts) - 1];
    }
    
    private function _buildCoinsSpan()
    {
        $this->_coinsSpan = '<span class="' . self::COINS_SPAN_CLASS . '" title="' . http_build_query($this->_coins, '', '&amp;') . '"></span>';
    }
}