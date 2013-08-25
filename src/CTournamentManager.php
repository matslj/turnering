<?php

// ===========================================================================================
//
// Class: CTournamentManager
// 
// Author: Mats Ljungquist
//
class CTournamentManager {

    private $tournamentMatrix;
    private $totalNumberOfRounds;
    
    // Holds the value of the current round
    private $currentRound;
    private $currentRoundEdited;
    private $currentRoundComplete;
    
    public static $LOG = null;
    
    public function __destruct() {
        ;
    }
    
    public function __construct($theDatabase, $theMaxNrOfRounds, $theRoundToCreate) {
        
        self::$LOG = logging_CLogger::getInstance(__FILE__);
        
        // DB connection is required
        if (empty($theDatabase)) {
            return null;
        }
        
        self::$LOG -> debug("ny: " .  $theRoundToCreate);

        // Get matchups from DB
        $this->totalNumberOfRounds = $theMaxNrOfRounds;
        $this->currentRound = 1;
        $this->currentRoundEdited = false;
        $this->currentRoundComplete = true;
        $this->tournamentMatrix = self::populateFromDB($theDatabase, $this->currentRound, $this->currentRoundEdited, $this->currentRoundComplete);
        
        self::$LOG -> debug("efter populering från db: " . $this->tournamentMatrix);
        
        // If no matchups where found in db -> create the first round and store it in DB
        if ($this->tournamentMatrix == null || ($theRoundToCreate == 1 && $this->currentRound == 1 && !$this->currentRoundEdited)) {
            self::$LOG -> debug("inne häär");
            $this->tournamentMatrix = self::createFirstRound($theDatabase);
        } else if ($theRoundToCreate > 1) {
            self::$LOG -> debug("more rounds");
            $okToCreate = false;
            if ($theRoundToCreate == $this->currentRound && !$this->currentRoundEdited) {
                $okToCreate = true;
            }
            $nextRound = $this->currentRound + 1;
            if ($theRoundToCreate == $nextRound &&
                    $nextRound <= $theMaxNrOfRounds &&
                    $this->currentRoundComplete) {
                $okToCreate = true;
            }
            if ($okToCreate) {
                $this->tournamentMatrix[$theRoundToCreate] = self::createRound($theDatabase, $theRoundToCreate);
                $this->currentRound = $theRoundToCreate;
                $this->currentRoundEdited = false;
                $this->currentRoundComplete = false;
            }
        }
    }
    
    private static function createFirstRound($theDatabase) {
        $userRepository = user_CUserRepository::getInstance($theDatabase);
        $tempOldUserList = $userRepository->getUsers();
        
        $tempUserList = array();
        foreach ($tempOldUserList as $value) {
            $tempUserList[] = $value->getCopy();
            self::$LOG -> debug($value->getName());
        }
        self::$LOG -> debug("inne yhuyyuiyiy");
        $tempMatrix = null;
        $round = 1;
        
        if (!empty($tempUserList)) {
            
            // First round results
            $result = array();
            $playerOne = null;
            
            while($playerOne = array_pop($tempUserList)) {
                
                self::$LOG -> debug("one: " . $playerOne->getName());
                
                $playerTwo = null;
                shuffle($tempUserList);
                
                if (sizeof($tempUserList) > 0) {
                    // Even number of entries -> get player two
                    $playerTwo = array_pop($tempUserList);
                    self::$LOG -> debug("two: " . $playerTwo->getName());
                } else {
                    $playerTwo = user_CUserData::getEmptyInstance();
                }
                $result[] = new CMatch(-1, $round, $playerOne, $playerTwo, 0, 0, null);
            }
            
            foreach ($result as $value) {
                self::$LOG -> debug($value->getPlayerOne()->getName() . " " . $value->getPlayerTwo()->getName());
            }
            
            self::storeCreatedMatchupsOnRound($theDatabase, $round, $result);
            $tempMatrix = array();
            $tempMatrix[$round] = $result;
            
        }
        return $tempMatrix;
    }
    
    private function createRound($theDatabase, $theRound) {
        // Fetch all users active in this tournament.
        $userRepository = user_CUserRepository::getInstance($theDatabase);
        $tempOldUserList = $userRepository->getUsers();
        $result = array();
        
        // Not thet the users are fetched, create a copy of them and calulate
        // every players score up to (but not including) the round that we
        // are about to create.
        $tempUserList = array();
        foreach ($tempOldUserList as $value) {
            $tempPlayer = $value->getCopy();
            $tempPlayer->setTotalScore($this->getTotalScore($tempPlayer, $theRound));
            $tempUserList[] = $tempPlayer;
            self::$LOG -> debug($tempPlayer->getName() . " score: " . $tempPlayer->getTotalScore());
        }
        
        // Sort by score.
        $this->sortByScore($tempUserList);
        
        // If there are an uneven number of players, the one with lowest score
        // has to be byed (if not byed before).
        $nrOfEntries = count($tempUserList);
        $even = $nrOfEntries%2 == 0;
        
        if (!$even) {
            $i = $nrOfEntries - 1;
            if ($this->hasBeenByed($tempUserList[$i], $theRound)) {
                // The last entry has already been byed -> must find a switch
                $found = false;
                while (!$found || $i > 0) {
                    $i--;
                    if (!$this->hasBeenByed($tempUserList[$i], $theRound)) {
                        $found = true;
                    }
                }
                if ($found) {
                    // A switch has been found -> make the swap, placing the switch last  
                    $tp = $tempUserList[$i];
                    $tempUserList[$i] = $tempUserList[$nrOfEntries - 1];
                    $tempUserList[$nrOfEntries - 1] = $tp;
                    self::$LOG -> debug("Byed: " . $tp->getName());
                }
                // In the unlikely event that no unbyed player is found, the last
                // one has to be unbyed again. This is done automatically.
            }
            $playerOne = array_pop($tempUserList);
            $playerTwo = user_CUserData::getEmptyInstance();
            $result[] = new CMatch(-1, $theRound, $playerOne, $playerTwo, 0, 0, null);
            
            // After this we have an even number of players.
            // Re sort the array.
            $this->sortByScore($tempUserList);
        }
        
        self::$LOG -> debug("bye done");
        
        foreach ($tempUserList as $value) {
            self::$LOG -> debug($value->getName());
        }
        
        // Sort the list so that 1 - 2, 3 - 4, 5 - 6, etc is paired
        $this->sortOnUniqueOponent($tempUserList, $theRound);
        
        self::$LOG -> debug("inne yhuyyuiyiy");
           
        // First round results
        
        $playerOne = null;
            
        while($playerOne = array_pop($tempUserList)) {

            self::$LOG -> debug("one: " . $playerOne->getName());
            $playerTwo = array_pop($tempUserList);
            self::$LOG -> debug("two: " . $playerTwo->getName());

            $result[] = new CMatch(-1, $theRound, $playerOne, $playerTwo, 0, 0, null);
        }
            
        self::storeCreatedMatchupsOnRound($theDatabase, $theRound, $result);
            
        return $result;
    }
    
    /**
     * 
     * @param type $players even number of players, for the round, sorted, highest score first
     * @param type $theNewRound the round which is about to be created
     */
    private function sortOnUniqueOponent(&$players, $theNewRound) {
        
        $goBackwardAlso = false;
        $numberOfPlayers = count($players);
        
        // Forward
        $sign = 1;
        $startPos = 0;
		$stopPos = $numberOfPlayers;
        
        // Arrange so that 1 meets 2, 3 meets 4, etc.
        // Jump by 2; if for example player 1 and player 2 hasnt played before
        // all is fine and we jump two steps forward and try 3 and 4. If 3 has
        // played 4, we look for the next player, after 4, that 3 has not played.
        for ($index = $startPos; $index != $stopPos; $index = $index + $sign * 2) {
            self::$LOG -> debug("index: " . $index . " sign: " . $sign . " start/stop: " . $startPos . ", " . $stopPos);
            if ($this->hasPlayedBefore($players[$index], $players[$index + $sign], $theNewRound)) {
                // The two teams next to each other on the scoreboard has played before, look for first best
                self::$LOG -> debug("** hasplayedbefore **");
                $playerIndex = $this->findFirstEligibleMatch($stopPos, $players, $index, $index + 2 * $sign, $theNewRound);
                if ($playerIndex > 0) {
                    $this->movePlayerOnScoreTable($players, $index + $sign, $playerIndex);
                } else {
                    $goBackwardAlso = true;
                    break;
                }
            }
        }
        
        self::$LOG -> debug("forward completed");
        
        if ($goBackwardAlso) {
            
            // Backward
            $sign = -1;
			$startPos = $numberOfPlayers - 1;
			$stopPos = -1; 
            
            for ($index = $startPos; $index < $stopPos; $index = $index + $sign * 2) {
                if ($this->hasPlayedBefore($players[$index]->getPlayerOne(), $players[$index + $sign]->getPlayerTwo(), $theNewRound)) {
                    // The two teams next to each other on the scoreboard has played before, look for first best
                    $playerIndex = $this->findFirstEligibleMatch($stopPos, $players, $index, $index + 2 * $sign, $theNewRound, false);
                    if ($playerIndex > 0) {
                        $this->movePlayerOnScoreTable($players, $index + $sign, $playerIndex);
                    }
                    // If no match was found, we go with the suggested order.
                }
            }
        }
    }
    
    private function findFirstEligibleMatch($numberOfPlayers, $players, $indexToMatch, $startPos, $theNewRound, $forward = true) {
        $retVal = -1;
        
        if ($forward) {
			$sign = 1;
			$stopPos = $numberOfPlayers;
			if ($startPos > $stopPos) {
                return -1;
            }
		} else {
			$sign = -1;
			$stopPos = -1;
			if ( $startPos < $stopPos ) {
                return -1;
            }
		}
        
        for ($index = $startPos; $index != $stopPos; $index = $index + $sign) {
            self::$LOG -> debug("-----index: " . $index . " sign: " . $sign . " start/stop: " . $startPos . ", " . $stopPos);
            if (!$this->hasPlayedBefore($players[$indexToMatch], $players[$index], $theNewRound)) {
                $retVal = $index;
                break;
            }
        }
        return $retVal;
    }
    
    private function movePlayerOnScoreTable(&$players, $shiftTo, $shiftFrom) {
        
        if ($shiftFrom > $shiftTo) {
			$sign = -1;
		} else{
			$sign = 1;
		}
	
		$tempPlayer = $players[$shiftFrom]; 
		
		for ($i=$shiftFrom; $i!=$shiftTo; $i=$i+$sign) {
			$players[$i]=$players[$i+$sign];
		}
		$players[$shiftTo]=$tempPlayer;
    }
    
    private function hasPlayedBefore($playerOne, $playerTwo, $theNewRound) {
        $retVal = false;
        
         self::$LOG -> debug($playerOne->getName() . " vs " . $playerTwo->getName());
        
        foreach ($this->tournamentMatrix as $key => $value) {
            if ($key != $theNewRound) {
                foreach ($value as $valueInner) {
                    if ($playerOne->getId() == $valueInner->getPlayerOne()->getId() && 
                            $playerTwo->getId() == $valueInner->getPlayerTwo()->getId()) {
                        $retVal = true;
                    } else if ($playerOne->getId() == $valueInner->getPlayerTwo()->getId() && 
                            $playerTwo->getId() == $valueInner->getPlayerOne()->getId()) {
                        $retVal = true;
                    }
                }
            }
        }
        return $retVal;
    }
    
    private function hasBeenByed($player, $theNewRound) {
        $retVal = false;
        self::$LOG -> debug("thePlayer: " . $player->getName() . " and the round: " . $theNewRound);
        
        foreach ($this->tournamentMatrix as $key => $value) {
            if ($key != $theNewRound) {
                foreach ($value as $valueInner) {
                    if ($player->getId() == $valueInner->getPlayerOne()->getId() && 
                            $valueInner->getPlayerTwo()->isEmptyInstance()) {
                        $retVal = true;
                    }
                }
            }
        }
        return $retVal;
    }
    
    private function getTotalScore($player, $theNewRound) {
        $retVal = 0;
        
        
        
        foreach ($this->tournamentMatrix as $key => $value) {
            if ($key != $theNewRound) {
                foreach ($value as $valueInner) {
                    if ($player->getId() == $valueInner->getPlayerOne()->getId()) {
                        $retVal += $valueInner->getScorePlayerOne();
                    } else if($player->getId() == $valueInner->getPlayerTwo()->getId()) {
                        $retVal += $valueInner->getScorePlayerTwo();
                    }
                }
            }
        }
        return $retVal;
    }
    
    private function sortByScore(&$players) {
        usort($players, array("user_CUserData", "cmp"));
    }
    
    private static function storeCreatedMatchupsOnRound($theDatabase, $theRound, $theMatchups) {
        // Delete all matchups for this round
        $spDeleteAllMatchesOnRound = DBSP_DeleteAllMatchesOnRound;
        $queryDeleteAll = "CALL {$spDeleteAllMatchesOnRound}({$theRound});";
        $resDel = $theDatabase->MultiQuery($queryDeleteAll);
        $nrOfStatements = $theDatabase->RetrieveAndIgnoreResultsFromMultiQuery();
        
        if($nrOfStatements != 1) {
            // Delete not OK
            self::$LOG -> debug("ERROR: Kunde inte radera runda.");
            
        } else {
            
            // Delete OK -> update db with new data
            $spCreateMatch = DBSP_CreateMatch;
            foreach ($theMatchups as $value) {
                $query = "CALL {$spCreateMatch}({$value->getPlayerOne()->getId()}, {$value->getPlayerTwo()->getId()}, {$value->getRound()}, @matchId);";
                $query .= "SELECT @matchId AS matchid";

                // Perform the query
                $res = $theDatabase->MultiQuery($query);

                // Use results
                $results = Array();
                $theDatabase->RetrieveAndStoreResultsFromMultiQuery($results);

                // Retrieve and update the id of the Match-object
                $row = $results[1]->fetch_object();
                $value -> setId($row->matchid);

                // Close the result set
                $results[1]->close();
            }
        }
    }
    
    private static function populateFromDB($theDatabase, &$theCurrentRound, &$theCurrentRoundEdited, &$theCurrentRoundComplete) {
        $userRepository = user_CUserRepository::getInstance($theDatabase);
        
        // Get the tablenames
        $tMatch       = DBT_Match;

        $query = <<< EOD
            SELECT
                idMatch AS id,
                playerOneMatch_idUser AS idPlayerOne, 
                playerTwoMatch_idUser AS idPlayerTwo, 
                playerOneScoreMatch   AS scorePlayerOne, 
                playerTwoScoreMatch   AS scorePlayerTwo, 
                roundMatch            AS round, 
                lastUpdateMatch       AS lastUpdate
            FROM {$tMatch}
            ORDER BY round ASC;
EOD;

         $res = $theDatabase->Query($query);
         $tempMatrix = array();
         $updated = false;
         $theCurrentRoundEdited = false;
         $theCurrentRoundComplete = true;
         while($row = $res->fetch_object()) {
             if (!array_key_exists($row->round, $tempMatrix)) {
                 $tempMatrix[$row->round] = array();
             }
             
             // The last round is the current round and we only want to record values for the current (last) round
             if ($row->round != $theCurrentRound) {
                 $theCurrentRoundEdited = false;
                 $theCurrentRoundComplete = true;
             }

             $tempPlayerTwo = empty($row->idPlayerTwo) ? user_CUserData::getEmptyInstance() : $userRepository->getUser($row->idPlayerTwo);
             $tempMatrix[$row->round][] = new CMatch($row->id, $row->round, $userRepository->getUser($row->idPlayerOne), $tempPlayerTwo, $row->scorePlayerOne, $row->scorePlayerTwo, $row->lastUpdate);
             $updated = true;
             $theCurrentRound = $row->round;
             $theCurrentRoundEdited = $row->scorePlayerOne != 0 || $row->scorePlayerTwo != 0 ? true : $theCurrentRoundEdited;
             $theCurrentRoundComplete = $theCurrentRoundComplete && ($row->scorePlayerOne != 0 || $row->scorePlayerTwo != 0) ? true : false;
         }
         $res->close();
         if ($updated !== true) {
             $tempMatrix = null;
         }
         return $tempMatrix;
    }
    
    function getNextRound() {
        return $this->currentRound + 1;
    }
    
    function getRoundAsHtml($theRound, $theEditable = false) {
        $tempRound = $this->tournamentMatrix[$theRound];
        
        $loggedOnUser = CUserData::getInstance();
        
        $byedPlayer = null;
        
        $html = "<div id='round{$theRound}' class='round'>";
        $html .= "<h2>Omgång {$theRound}</h2>";
        
        foreach ($tempRound as $value) {
            $html .= "<div class='matchup'>";
            $html .= "<table>";
            if ($value->getPlayerOne()->isEmptyInstance() || $value->getPlayerTwo()->isEmptyInstance()) {
                $byedPlayer = $value->getPlayerOne()->isEmptyInstance() ? $value->getPlayerTwo() : $value->getPlayerOne();
            } else {
                $html .= "<tr><td>{$value->getPlayerOne()->getAccount()}</td><td>-</td><td>{$value->getPlayerTwo()->getAccount()}</td></tr>";
                $html .= "<tr><td>";
                if ($theEditable && ($value->isPlayerInMatch($loggedOnUser->getId()) || $loggedOnUser->isAdmin())) {
                    $html .= "<input id='playerOneScore#{$value->getId()}' class='scoreInput' type='text' name='playerOneScore#{$value->getId()}' value='{$value->getScorePlayerOne()}' />";
                } else {
                    $html .= $value->getScorePlayerOne();
                }
                $html .= "</td><td>&nbsp;</td><td>";
                if ($theEditable && ($value->isPlayerInMatch($loggedOnUser->getId()) || $loggedOnUser->isAdmin())) {
                    $html .= "<input id='playerTwoScore#{$value->getId()}' class='scoreInput' type='text' name='playerTwoScore#{$value->getId()}' value='{$value->getScorePlayerTwo()}' />";
                } else {
                    $html .= $value->getScorePlayerTwo();
                }
                $html .= "</td></tr>";
            }
            $html .= "</table>";
            $html .= "</div> <!-- End div with matchup class -->";
        }
        if ($byedPlayer != null) {
            $html .= "<p>Spelare som får stå över den här rundan: <span class='byedPlayer'>" . $byedPlayer->getAccount() . "</span></p>";
        }
        $html .= "</div> <!-- End div with round id -->";
        
        return $html;
    }
    
    function getAllRoundsAsHtml() {
        $html = "<div id='allRounds'>";
        foreach ($this->tournamentMatrix as $key => $value) {
            $editable = $key == $this->currentRound;
            $html .= $this->getRoundAsHtml($key, $editable);
        }
        $html .= "</div> <!-- End of allRounds div -->";
        return $html;
    }
        
} // End of Of Class

?>