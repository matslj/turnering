<?php
// ===========================================================================================
//
// File: CHTMLHelpers.php
//
// Description: Class CHTMLHelpers
//
// Small code snippets to reduce coding in the pagecontrollers. The snippets are mainly for
// creating HTML code.
//
// Author: Mats Ljungquist
//


class CHTMLHelpers {

	// ------------------------------------------------------------------------------------
	//
	// Internal variables
	//
	

	// ------------------------------------------------------------------------------------
	//
	// Constructor
	//
	public function __construct() { ;	}
	

	// ------------------------------------------------------------------------------------
	//
	// Destructor
	//
	public function __destruct() { ; }

	
	// ------------------------------------------------------------------------------------
	//
	// Create a positive (Ok/Success) feedback message for the user.
	//
	public static function GetHTMLUserFeedbackPositive($aMessage) {
		return "<span class='userFeedbackPositive' style=\"background: url('".WS_IMAGES."/silk/accept.png') no-repeat; padding-left: 20px;\">{$aMessage}</span>";
	}
	
	
	// ------------------------------------------------------------------------------------
	//
	// Create a negative (Failed) feedback message for the user.
	//
	public static function GetHTMLUserFeedbackNegative($aMessage) {
		return "<span class='userFeedbackNegative' style=\"background: url('".WS_IMAGES."/silk/cancel.png') no-repeat; padding-left: 20px;\">{$aMessage}</span>";
	}
	
	
	// ------------------------------------------------------------------------------------
	//
	// Create feedback notices if functions was successful or not. The messages are stored
	// in the session. This is useful in submitting form and providing user feedback.
	// This method reviews arrays of messages and stores them all in an resulting array.
	//
	public static function GetHTMLForSessionMessages($aSuccessList, $aFailedList) {
	
		$messages = Array();
		foreach($aSuccessList as $val) {
			$m = CPageController::GetAndClearSessionMessage($val);
			$messages[$val] = empty($m) ? '' : self::GetHTMLUserFeedbackPositive($m);
		}
		foreach($aFailedList as $val) {
			$m = CPageController::GetAndClearSessionMessage($val);
			$messages[$val] = empty($m) ? '' : self::GetHTMLUserFeedbackNegative($m);
		}

		return $messages;
	}


	// ------------------------------------------------------------------------------------
	//
	// Static function, HTML helper
	// Create a horisontal sidebar menu
	//
	public static function GetSidebarMenu($aMenuitems, $aTarget="") {

		global $gPage;

		$target = empty($aTarget) ? $gPage : $aTarget;

		$menu = "<ul>";
		foreach($aMenuitems as $key => $value) {
			$selected = (strcmp($target, substr($value, 3)) == 0) ? " class='sel'" : "";
			$menu .= "<li{$selected}><a href='{$value}'>{$key}</a></li>";
		}
		$menu .= '</ul>';
		
		return $menu;
	}
    
    public static function getHtmlForSelectableTieBreakers($ddId, $ddName = "tiebreaker", $selectedTieBreaker = "") {

        $selectableTieBreakers = SELECTABLE_TIE_BREAKERS;
        $sList = unserialize($selectableTieBreakers);

        $html = "<select id='{$ddId}' class='{$ddName}' name='{$ddName}'>";
        $html .= "<option value=''>-</option>";
        foreach($sList as $key => $value) {
            $selected = !empty($selectedTieBreaker) && (strcmp($value, $selectedTieBreaker) == 0) ? " SELECTED" : "";
            $html .= "<option value='{$value}'{$selected}>{$key}</option>";
        }
        $html .= "</select>";

        return $html;
    }
    
    public static function isSelectableTieBreaker($name) {

        $selectableTieBreakers = SELECTABLE_TIE_BREAKERS;
        $sList = unserialize($selectableTieBreakers);
        $retVal = false;
        
        foreach($sList as $value) {
            if (strcmp($value, $name) == 0) {
                $retVal = true;
                break;
            }
        }

        return $retVal;
    }
    
    public static function getLabelForTieBreakerValue($theValue) {

        $selectableTieBreakers = SELECTABLE_TIE_BREAKERS;
        $sList = unserialize($selectableTieBreakers);
        $retVal = $theValue;
        
        foreach($sList as $key => $value) {
            if (strcmp($value, $theValue) == 0) {
                $retVal = $key;
                break;
            }
        }

        return $retVal;
    }
    
    /**
     * Returns all selectable armies as a html <select>.
     * 
     * @param type $ddId the id of the html <select>-component
     * @param type $ddName the name of the html <select>-component
     * @return string selectable armies as html
     */
    public static function getHtmlForSelectableArmies($ddId, $ddName = "army", $selectedArmy = "") {

        $selectableArmies = SELECTABLE_ARMIES;
        $sList = unserialize($selectableArmies);

        $html = "<select id='{$ddId}' class='{$ddName}' name='{$ddName}'>";
        $html .= "<option value=''>Välj armé</option>";
        foreach($sList as $key => $value) {
            $selected = !empty($selectedArmy) && (strcmp($key, $selectedArmy) == 0) ? " SELECTED" : "";
            $html .= "<option value='{$key}'{$selected}>{$key}</option>";
        }
        $html .= "</select>";

        return $html;
    }
    
    public static function isSelectableArmy($name) {

        $selectableArmies = SELECTABLE_ARMIES;
        $sList = unserialize($selectableArmies);
        $retVal = false;
        
        foreach($sList as $key => $value) {
            if (strcmp($key, $name) == 0) {
                $retVal = true;
                break;
            }
        }

        return $retVal;
    }
    
    public static function getArmyValueName($name) {

        $selectableArmies = SELECTABLE_ARMIES;
        $sList = unserialize($selectableArmies);
        $retVal = "";
        
        foreach($sList as $key => $value) {
            if (strcmp($key, $name) == 0) {
                $retVal = $value;
                break;
            }
        }

        return $retVal;
    }

        // ------------------------------------------------------------------------------------
	//
	// Create a negative (Failed) feedback message for the user.
	//
	public static function GetErrorMessageAsJSON($aMessage) {
    $json = <<<EOD
{
            "errorMessage": "{$aMessage}"
}
EOD;
            return $json;
        }


} // End of Of Class


?>