<?php

/**
 * This tiebreaker determines ranking based on original score.
 * This only has meaning if a point filter is chosen.
 *
 * @author mats
 */
class tiebreak_COrgScore implements tiebreak_ITiebreak {
    
    public static $LOG = null;
    
    public function __construct() {
        self::$LOG = logging_CLogger::getInstance(__FILE__);
    }
    
    /**
     * Overridden from ITiebreak.
     * 
     * @param type $playerOne
     * @param type $playerTwo
     * @param type $tournamentMatrix
     * @return int
     */
    public function compare($playerOne, $playerTwo, $tournamentMatrix) {
        // self::$LOG -> debug("&&&&&& comparing: " . $playerOne->getName() . " with " . $playerTwo->getName());
        $playerOneCounter = $this->getTotalScore($playerOne, $tournamentMatrix);
        $playerTwoCounter = $this->getTotalScore($playerTwo, $tournamentMatrix);
        
        if ($playerOneCounter == $playerTwoCounter) {
            return 0;
        }
        
        return ($playerOneCounter > $playerTwoCounter) ? -1 : 1;
    }
    
    private function getTotalScore($player, $tournamentMatrix) {
        $retVal = 0;
        
        self::$LOG -> debug("I gettotalscore");

        foreach ($tournamentMatrix as $value) {
            // self::$LOG -> debug("key: " . $key . " : value: " . $value . " thenewround: " . $theNewRound);
            // self::$LOG -> debug(print_r($value, true));
                foreach ($value as $valueInner) {
                    //self::$LOG -> debug(print_r($valueInner, true));
                     //self::$LOG -> debug("valueInner: " . $valueInner);
                    if ($player->getId() == $valueInner->getPlayerOne()->getId()) {
                        $retVal += $valueInner->getScorePlayerOne();
                    } else if($player->getId() == $valueInner->getPlayerTwo()->getId()) {
                        $retVal += $valueInner->getScorePlayerTwo();
                    }
                }
        }
        self::$LOG -> debug("klar med total score");
        return $retVal;
    }
}

?>
