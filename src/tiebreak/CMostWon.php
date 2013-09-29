<?php

/**
 * This tiebreaker determines ranking based on most won games.
 *
 * @author mats
 */
class tiebreak_CMostWon implements tiebreak_ITiebreak {
    
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
        $playerOneCounter = 0;
        $playerTwoCounter = 0;
        
        foreach ($tournamentMatrix as $value) {
            foreach ($value as $valueInner) {
                $winningPlayer = null;
                // self::$LOG -> debug("&&&&&& inner comparing: " . $valueInner->getPlayerOne()->getName() . " with " . $valueInner->getPlayerTwo()->getName());
                $scoreOne = $valueInner->getScorePlayerOne();
                $scoreTwo = $valueInner->getScorePlayerTwo();
                // self::$LOG -> debug("&&&&&& scoreone: " . $scoreOne . " with scoretwo: " . $scoreTwo);
                if ($scoreOne > $scoreTwo) {
                    $winningPlayer = $valueInner->getPlayerOne();
                } else if ($scoreOne < $scoreTwo) {
                    $winningPlayer = $valueInner->getPlayerTwo();
                }
                if ($winningPlayer != null) {
                    if ($playerOne->getId() == $winningPlayer->getId()) {
                        $playerOneCounter++;
                    } else if ($playerTwo->getId() == $winningPlayer->getId()) {
                        $playerTwoCounter++;
                    }
                }
            }
        }
        
        if ($playerOneCounter == $playerTwoCounter) {
            return 0;
        }
        
        return ($playerOneCounter > $playerTwoCounter) ? -1 : 1;
    }
}

?>
