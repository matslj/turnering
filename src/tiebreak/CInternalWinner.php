<?php

/**
 * This tiebreaker determines ranking based on who won the game when (and if) the
 * two players met.
 *
 * @author mats
 */
class tiebreak_CInternalWinner implements tiebreak_ITiebreak {
    
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
        $match = $this->findMatch($playerOne, $playerTwo, $tournamentMatrix);
        
        // self::$LOG -> debug("&&&&&& comparing: " . print_r($match, true));
        
        if ($match == null || $match->getScorePlayerOne() == $match->getScorePlayerTwo()) {
            return 0;
        }
        
        // It can happen that in the $match returned, player one is player two and vice versa.
        // This is taken care of below.
        $playerOneScore = 0;
        $playerTwoScore = 0;
        if ($match->getPlayerOne()->equals($playerOne)) {
            $playerOneScore = $match->getScorePlayerOne();
            $playerTwoScore = $match->getScorePlayerTwo();
        } else {
            $playerOneScore = $match->getScorePlayerTwo();
            $playerTwoScore = $match->getScorePlayerOne();
        }
        
        return ($playerOneScore > $playerTwoScore) ? -1 : 1;
    }
    
    private function findMatch($playerOne, $playerTwo, $tournamentMatrix) {
        $retVal = null;
        
        foreach ($tournamentMatrix as $value) {
            foreach ($value as $valueInner) {
                if ($playerOne->getId() == $valueInner->getPlayerOne()->getId() && 
                        $playerTwo->getId() == $valueInner->getPlayerTwo()->getId()) {
                    $retVal = $valueInner;
                } else if ($playerOne->getId() == $valueInner->getPlayerTwo()->getId() && 
                        $playerTwo->getId() == $valueInner->getPlayerOne()->getId()) {
                    $retVal = $valueInner;
                }
            }
        }
        return $retVal;
    }
}

?>
