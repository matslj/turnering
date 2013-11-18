<?php

// ===========================================================================================
//
// Class: CMatch
//
// 
// Author: Mats Ljungquist
//
class CScoreProxyManager {
    
    public static $LOG = null;
    
    private $scoreFilter = null;

    function __construct($theProxyFilterAsJsonString = "") {
        self::$LOG = logging_CLogger::getInstance(__FILE__);
        self::$LOG->debug(" **** Creating CScoreProxyManager **** ");
        if (empty($theProxyFilterAsJsonString)) {
            $this->loadScoreFilterTest();
        } else {
            $this->loadScoreFilter($theProxyFilterAsJsonString);
        }
    }
    
    /**
     * Creates a scoreFilter with some hard coded values. This should be used
     * during testing.
     */
    private function loadScoreFilterTest() {
        self::$LOG->debug(" **** In CScoreProxyManager - loadScoreFilterTest() **** ");
        $scoreFilter = array();
        $scoreFilter[] = new CScoreProxy(1, 0, 120, 10, 10);
        $scoreFilter[] = new CScoreProxy(2, 121, 240, 11, 9);
        $scoreFilter[] = new CScoreProxy(2, 241, 360, 12, 8);
        $scoreFilter[] = new CScoreProxy(3, 361, 480, 13, 7);
        $scoreFilter[] = new CScoreProxy(4, 481, 600, 14, 6);
        $scoreFilter[] = new CScoreProxy(5, 601, 720, 15, 5);
        $scoreFilter[] = new CScoreProxy(6, 721, 840, 16, 4);
        $scoreFilter[] = new CScoreProxy(7, 841, 960, 17, 3);
        $scoreFilter[] = new CScoreProxy(8, 961, 1080, 18, 2);
        $scoreFilter[] = new CScoreProxy(9, 1081, 1200, 19, 1);
        $scoreFilter[] = new CScoreProxy(10, 1201, PHP_INT_MAX, 20, 0);
        $this -> scoreFilter = $scoreFilter;
    }
    
    /**
     * Creates a scoreFilter from a json string score filter.
     * 
     * @param string $theProxyFilterAsJsonString
     */
    private function loadScoreFilter($theProxyFilterAsJsonString) {
        self::$LOG->debug(" **** In CScoreProxyManager - loadScoreFilter() **** ");
        self::$LOG->debug(" *    theProxyFilterAsJsonString: \r\n" . $theProxyFilterAsJsonString);
        $scoreFilter = array();
        $id = 1; // This is a dummyId and has nothing to do with the id in the database.
        
        $scoresDecoded = json_decode($theProxyFilterAsJsonString);

        if ($scoresDecoded != null && is_array($scoresDecoded)) {

            foreach ($scoresDecoded as $value) {
                $scoreFilter[] = new CScoreProxy($id, $value -> orgFrom, $value -> orgTom, $value -> newFrom, $value -> newTom);
                $id = $id + 1;
            }
        }
        $this -> scoreFilter = $scoreFilter;
    }
    
    public function getScoreFilter() {
        return $this->scoreFilter;
    }
    
    public function getScoreFilterForDiff($diffValue) {
        self::$LOG->debug(" **** In CScoreProxyManager - getScoreFilterForDiff(" . $diffValue . ")");
        
        foreach ($this -> scoreFilter as $value) {
            if ($value -> getDiffLow() < $diffValue && $value -> getDiffHigh() > $diffValue) {
                return $value;
            }
        }
        return null;
    }
    
    /**
     * Finds and returns the appropriate scoreFilterEntry for the parameterized scores.
     * <p>
     * In the returned object, the new score for playerOne is in CScoreProxy->scorePlayerOne
     * and the new score for playerTwo is in CScoreProxy->scorePlayerTwo.
     * <p>
     * The rest of the values in the return object are not of interest (and may even be wrong).
     * 
     * @param integer $playerOneScore original score for the first player
     * @param integer $playerTwoScore original score for the second player
     * @return CScoreProxy
     */
    public function getScoreProxyForDiffBetweenPlayers($playerOneScore, $playerTwoScore) {
        self::$LOG->debug(" **** In CScoreProxyManager - getScoreProxyForDiffBetweenPlayers(" . $playerOneScore . ", " . $playerTwoScore . ")");
        $playerOneScore = $playerOneScore == null || empty($playerOneScore) ? 0 : $playerOneScore;
        $playerTwoScore = $playerTwoScore == null || empty($playerTwoScore) ? 0 : $playerTwoScore;
        self::$LOG->debug("in proxydiff 2: " . $playerOneScore . " - " . $playerTwoScore);
        $orgDiff = $playerOneScore - $playerTwoScore;
        $diffValue = abs($orgDiff);
        self::$LOG->debug("---- diff: " . $diffValue);
        $foundPointProxy = null;
        foreach ($this -> scoreFilter as $value) {
            self::$LOG->debug("proxy: " . print_r($value, true));
            if ($value -> getDiffLow() <= $diffValue && $value -> getDiffHigh() >= $diffValue) {
                self::$LOG->debug("---- FOUND PROXY!!! -----");
                $foundPointProxy = $value;
                break;
            }
        }
        if ($orgDiff < 0 && $foundPointProxy != null) {
            $tempVal = $foundPointProxy->getScorePlayerOne();
            $foundPointProxy->setScorePlayerOne($foundPointProxy->getScorePlayerTwo());
            $foundPointProxy->setScorePlayerTwo($tempVal);
        }
        //self::$LOG->debug("end proxydiff: " . print_r($foundPointProxy, true));
        return $foundPointProxy;
    }
    
    public function getScoreFilterAsJavascriptObjectArray() {
        self::$LOG->debug(" **** In CScoreProxyManager - getScoreFilterAsJavascriptObjectArray()");
        
        $jsObjectArray = "[";
        $lengthSF = count($this->scoreFilter);
        for ($index = 0; $index < $lengthSF; $index++) {
            $jsObjectArray .= "{ ";
            $value = $this->scoreFilter[$index];
            $jsObjectArray .= "diffLow: {$value->getDiffLow()}, ";
            $jsObjectArray .= "diffHigh: {$value->getDiffHigh()}, ";
            $jsObjectArray .= "scorePlayerOne: {$value->getScorePlayerOne()}, ";
            $jsObjectArray .= "scorePlayerTwo: {$value->getScorePlayerTwo()} ";
            $jsObjectArray .= "} ";
            if ($index != $lengthSF - 1) {
                $jsObjectArray .= ", ";
            }
        }
        $jsObjectArray .= "]";
        return $jsObjectArray;
    }
    
    public function getScoreFilterAsHtmlTable($editable = true) {
        self::$LOG->debug(" **** In CScoreProxyManager - getScoreFilterAsHtmlTable(" . $editable . ")");
        
        $disabled = $editable ? "" : " disabled";
        
        $htmlMain = <<< EOD
        <table class="scoreFilterTable">
                <tr>
                    <th style='border-top-left-radius: 10px;' class="dbfHandle">&nbsp;</th>
                    <th colspan="3">Intervall</th>
                    <th class='dbfEmptyCell'>&nbsp;</th>
                    <th style='border-top-right-radius: 10px;' colspan="3">Poäng</th>
                </tr>
EOD;
        $lengthSF = count($this->scoreFilter);
        for ($index = 0; $index < $lengthSF; $index++) {
            $value = $this->scoreFilter[$index];
            $htmlMain .= "<tr>";
            $htmlMain .= "    <td class='sfCell dbfHandle'><div id='dbfId#{$index}' class='dbfHTarget'>&nbsp;</div></td>";
            $htmlMain .= "    <td class='sfCell dpfCell'><input id='dpfOriginalFrom#{$index}'{$disabled} class='orgFrom' type='text' name='dpfOriginalFrom#{$index}' value='{$value->getDiffLow()}' /></td>";
            $htmlMain .= "    <td class='sfCell minus'>-</td>";
            $htmlMain .= "    <td class='sfCell dpfCell'><input id='dpfOriginalTom#{$index}'{$disabled} class='orgTom' type='text' name='dpfOriginalTom#{$index}' value='{$value->getDiffHigh()}' /></td>";
            $htmlMain .= "    <td class='sfCell dbfEmptyCell'>&nbsp;</td>";
            $htmlMain .= "    <td class='sfCell dpfCell'><input id='dpfNewFrom#{$index}'{$disabled} class='newFrom' type='text' name='dpfNewFrom#{$index}' value='{$value->getScorePlayerOne()}' /></td>";
            $htmlMain .= "    <td class='sfCell slash'>/</td>";
            $htmlMain .= "    <td class='sfCell dpfCell'><input id='dpfNewTom#{$index}'{$disabled} class='newTom' type='text' name='dpfNewTom#{$index}' value='{$value->getScorePlayerTwo()}' /></td>";
            $htmlMain .= "<tr>";
        }

        $htmlMain .= "</table>";
        
        return $htmlMain;
    }

} // End of Of Class

?>