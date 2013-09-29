<?php

/**
 * This interface, and all the classes in this package, are a part of a
 * strategy pattern implementation. The purpose of the tiebreaker implementations
 * are to determine, on class specific criteria, the score board order between
 * players.
 *
 * @author mats
 */
interface tiebreak_ITiebreak {
    
    /**
     * Compares two players according to the rules for a given tieBreaker.
     * 
     * @param type $playerOne
     * @param type $playerTwo
     * @param type $tournamentMatrix
     * @return int
     */
    public function compare($playerOne, $playerTwo, $tournamentMatrix);
}

?>
