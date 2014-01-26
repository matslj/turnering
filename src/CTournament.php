<?php

// ===========================================================================================
//
// Class: CTournament
//
// 
// Author: Mats Ljungquist
//
class CTournament {
    
    public static $LOG = null;

    private $id;
    private $creator;
    private $place;
    private $nrOfRounds;
    private $type;
    private $active;
    private $byeScore;
    private $tournamentDateFrom; // class CDate
    private $tournamentDateTom;  // class CDate
    private $creationDate;       // class CDate
    private $playedNrOfRounds;
    
    private $useProxy;
    
    // Rounds - Matchups/Matches
    private $tournamentMatrix;
    
    // Holds the value of the current round
    private $currentRound;
    private $currentRoundEdited;
    private $currentRoundComplete;
 
    // Tiebreakers
    private $tieBreakers; // array of ITiebreak interface implementing objects
    
    private $scoreProxyManager;

    private function __construct($id, $creator, $place, $nrOfRounds, $type, $theActive, $byeScore, $tournamentDateFrom, $tournamentDateTom, $creationDate, $theTieBreakers, $theUseProxy, $theProxyFilter) {
        self::$LOG -> debug("### Start CTournament constructor");
        
        $this->playedNrOfRounds = null;
        $this->id = $id;
        $this->creator = $creator;
        $this->place = $place;
        $this->nrOfRounds = $nrOfRounds;
        $this->type = $type;
        $this->active = $theActive != 0 ? true : false;
        $this->byeScore = $byeScore;
        $this->tournamentDateFrom = CDate::getInstanceFromMysqlDatetime($tournamentDateFrom);
        $this->tournamentDateTom = CDate::getInstanceFromMysqlDatetime($tournamentDateTom);
        $this->creationDate = CDate::getInstanceFromMysqlDatetime($creationDate);
        $this->tieBreakers = array(); // Must always be initialized so leave it be.
        // Below: add tie breakers. Explode the string of ','-delimited tiebreakers
        // and use them in a strategy pattern to create tiebreaker-objects
        $strArray = explode(",", $theTieBreakers);
        foreach ($strArray as $value) {
            switch ($value) {
                case "internalwinner":
                    $this->tieBreakers[] = new tiebreak_CInternalWinner();
                    break;
                case "mostwon":
                    $this->tieBreakers[] = new tiebreak_CMostWon();
                    break;
                case "orgscore":
                    $this->tieBreakers[] = new tiebreak_COrgScore();
                    break;
            }
        }
        $this->useProxy = $theUseProxy != 0 ? true : false;
        $this->scoreProxyManager = new CScoreProxyManager($theProxyFilter);

        self::$LOG -> debug("### End CTournament constructor");
    }
    
    public static function getEmptyInstance() {
        
        self::$LOG = logging_CLogger::getInstance(__FILE__);
        
        $user = CUserData::getInstance();
        return new self(0, $user, "", 0, "swiss", 0, 0, null, null, null, 0, null);
    }
    
    public static function getInstanceByParameters($id, $creator, $place, $nrOfRounds, $type, $theActive, $byeScore, $tournamentDateFrom, $tournamentDateTom, $creationDate, $theTieBreakers, $theUseProxy, $theProxyFilter) {
        self::$LOG = logging_CLogger::getInstance(__FILE__);
        return new self($id, $creator, $place, $nrOfRounds, $type, $theActive, $byeScore, $tournamentDateFrom, $tournamentDateTom, $creationDate, $theTieBreakers, $theUseProxy, $theProxyFilter);
    }
    
    public static function getInstanceById($theDatabase, $theTournamentId) {
        
        self::$LOG = logging_CLogger::getInstance(__FILE__);
        
        self::$LOG -> debug("---- Start of getInstanceById(db, {$theTournamentId}) ----");
        
        // Get the tablenames
        $tTournament       = DBT_Tournament;

        $query = <<< EOD
            SELECT
                idTournament AS id,
                creatorTournament_idUser AS creator,
                placeTournament AS place, 
                roundsTournament AS rounds, 
                typeTournament AS type, 
                activeTournament AS active,
                byeScoreTournament AS byeScore,
                createdTournament AS creationDate,
                dateFromTournament AS dateFrom,
                dateTomTournament AS dateTom,
                tieBreakersTournament AS tieBreakers,
                useProxyTournament AS useProxy,
                jsonScoreProxyTournament as scoreFilter
            FROM {$tTournament}
            WHERE
                idTournament = {$theTournamentId};
EOD;

         $res = $theDatabase->Query($query);
         $row = $res->fetch_object();
         
         $tempTournament = null;
         
         if (!empty($row)) {
             self::$LOG -> debug("In getInstanceById(db, {$theTournamentId}) - found tournament with id {$theTournamentId} in the database.");
             $users = user_CUserRepository::getInstance($theDatabase);
             $user = $users->getUser($row->creator);
             $tempTournament = new self($row->id, $user, $row->place, $row->rounds, $row->type, $row->active, $row->byeScore, $row->dateFrom, $row->dateTom, $row->creationDate, $row->tieBreakers, $row->useProxy, $row->scoreFilter);
         }
         $res->close();
         
         self::$LOG -> debug("In getInstanceById(db, {$theTournamentId}) - created a temporary instance of CTournament");
         
         // Get matchups, on tournament, from DB
         if (!empty($tempTournament)) {
             $tempTournament->currentRound = 1;
             $tempTournament->currentRoundEdited = false;
             $tempTournament->currentRoundComplete = true;
             self::$LOG -> debug("In getInstanceById(db, {$theTournamentId}) - before match load. current round: " . $tempTournament->currentRound . " edited: " . $tempTournament->currentRoundEdited . " complete: " . $tempTournament->currentRoundComplete);
             $tempTournament->tournamentMatrix = self::populateMatrixFromDB($theDatabase, $tempTournament->id, $tempTournament->scoreProxyManager, $tempTournament->currentRound, $tempTournament->currentRoundEdited, $tempTournament->currentRoundComplete);
             self::$LOG -> debug("In getInstanceById(db, {$theTournamentId}) - after match load. current round: " . $tempTournament->currentRound . " edited: " . $tempTournament->currentRoundEdited . " complete: " . $tempTournament->currentRoundComplete);
         } else {
             return self::getEmptyInstance();
         }
         return $tempTournament;
    }
    
    public function createOrRecreateRound($theDatabase, $theRoundToCreate = 0) { // Was 1
        self::$LOG -> debug(" **** I createOrRecreateRound(db, {$theRoundToCreate}) ****");
        // If no matchups where found in db -> create the first round and store it in DB
        if ($this->tournamentMatrix == null || ($theRoundToCreate == 1 && $this->currentRound == 1)) { //  && !$this->currentRoundEdited
            $logMessage = ($theRoundToCreate == 1 && $this->currentRound == 1) ? "Kommer att återskapa första rundan." : "Skapar första rundan.";
            self::$LOG -> debug($logMessage);
            $this->tournamentMatrix = $this->createFirstRound($theDatabase);
            $this->currentRoundComplete = (count($this ->getActiveUsers($theDatabase)) == 1) ? true : false;
            $this->currentRoundEdited = false;
        } else if ($theRoundToCreate < $this->currentRound && $theRoundToCreate > 0) {
            self::$LOG -> debug("Kommer att radera runda '{$this->currentRound}'");
            $this->deleteCurrentRound($theDatabase);
        } else if ($theRoundToCreate > 1) {
            self::$LOG -> debug("more rounds. current round: " . $this->currentRound . " the round to create: " . $theRoundToCreate . " edited: " . $this->currentRoundEdited . " complete: " . $this->currentRoundComplete);
            $okToCreate = false;
            if ($theRoundToCreate == $this->currentRound) { // && !$this->currentRoundEdited
                $okToCreate = true;
                self::$LOG -> debug("more rounds 1");
            }
            $nextRound = $this->currentRound + 1;
            if ($theRoundToCreate == $nextRound &&
                $nextRound <= $this->nrOfRounds &&
                $this->currentRoundComplete) {
                $okToCreate = true;
                self::$LOG -> debug("more rounds 2");
            }
            if ($okToCreate) {
                self::$LOG -> debug("more rounds 3");
                $this->tournamentMatrix[$theRoundToCreate] = self::createRound($theDatabase, $theRoundToCreate);
                $this->currentRound = $theRoundToCreate;
                $this->currentRoundEdited = false;
                $this->currentRoundComplete = false;
            }
        }
    }
    
    // ***************************************************************************************
    // **      Below are accessor methods
    // **

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getCreator() {
        return $this->creator;
    }

    public function setCreator($creator) {
        $this->creator = $creator;
    }

    public function getPlace() {
        return $this->place;
    }

    public function setPlace($place) {
        $this->place = $place;
    }

    public function getNrOfRounds() {
        return $this->nrOfRounds;
    }

    public function setNrOfRounds($nrOfRounds) {
        $this->nrOfRounds = $nrOfRounds;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public function getCreationDate() {
        return $this->creationDate;
    }

    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }
    
    public function getByeScore() {
        return $this->byeScore;
    }

    public function setByeScore($byeScore) {
        $this->byeScore = $byeScore;
    }

    public function getTournamentDateFrom() {
        return $this->tournamentDateFrom;
    }

    public function setTournamentDateFrom($tournamentDateFrom) {
        $this->tournamentDateFrom = $tournamentDateFrom;
    }

    public function getTournamentDateTom() {
        return $this->tournamentDateTom;
    }

    public function setTournamentDateTom($tournamentDateTom) {
        $this->tournamentDateTom = $tournamentDateTom;
    }
    
    public function isCurrentRoundComplete() {
        return $this->currentRoundComplete;
    }
    
    public function getTieBreakers() {
        return $this->tieBreakers;
    }
    
    public function getCurrentRound() {
        return $this->currentRound;
    }
    
    public function getUseProxy() {
        return $this->useProxy;
    }
    
    public function getScoreProxyManager() {
        return $this->scoreProxyManager;
    }
    
    public function isUpcoming() {
        return ($this->tournamentDateFrom -> compareWithoutTime(time()) >= 0);
    }
    public function isDeletable() {
        return $this->getPlayedNrOfRounds() <= 1;
    }
    
    public function getPlayedNrOfRounds() {
        if ($this->playedNrOfRounds == null) {
            $this->playedNrOfRounds = $this->getNrOfRoundsInMatrix();
        }
        return $this->playedNrOfRounds;
    }

    public function setPlayedNrOfRounds($playedNrOfRounds) {
        $this->playedNrOfRounds = $playedNrOfRounds;
    }
    
    public function getNrOfRoundsInMatrix() {
        return count($this->tournamentMatrix);
    }
              
    // ***************************************************************************************
    // **      Below are business methods
    // **
    
    private function createFirstRound($theDatabase) {
        
        $tempOldUserList = $this -> getActiveUsers($theDatabase);
        
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
                $byeScore = 0;
                if (sizeof($tempUserList) > 0) {
                    // Even number of entries -> get player two
                    $playerTwo = array_pop($tempUserList);
                    self::$LOG -> debug("two: " . $playerTwo->getName());
                } else {
                    $playerTwo = user_CUserData::getEmptyInstance();
                    $byeScore = $this->byeScore;
                }
                $result[] = new CMatch(-1, $round, $playerOne, $playerTwo, $byeScore, 0, null);
            }
            
            foreach ($result as $value) {
                self::$LOG -> debug("First round: " . $value->getPlayerOne()->getName() . " score1: " . $value->getScorePlayerOne() . " " . $value->getPlayerTwo()->getName() . " score2: " . $value->getScorePlayerTwo());
            }
            
            self::storeCreatedMatchupsOnRound($theDatabase, $round, $result, $this->getId());
            $tempMatrix = array();
            $tempMatrix[$round] = $result;
            
        } else {
            $this ->deleteCurrentRound($theDatabase);
        }
        return $tempMatrix;
    }
    
    /**
     * Get all active users in the tournament. An active user is a user who is a
     * part of the UserTournament-table for the tournament.
     * 
     * @param type $theDatabase
     * @return \user_CUserData
     */
    private function getActiveUsers($theDatabase) {
        // Fetch all users active in this tournament.
        $tUserTournament = DBT_UserTournament;
        $tableUser       = DBT_User;
        $tableGroup      = DBT_Group;
        $tableGroupMember  = DBT_GroupMember;

        $query = <<< EOD
            SELECT
                    idUser,
                    accountUser,
                    nameUser,
                    lastLoginUser,
                    emailUser,
                    armyUser,
                    activeUser,
                    avatarUser,
                    idGroup,
                    nameGroup
            FROM {$tableUser} AS U
                    INNER JOIN {$tUserTournament} AS UT 
                        ON UserTournament_idUser = idUser
                    INNER JOIN {$tableGroupMember} AS GM
                        ON U.idUser = GM.GroupMember_idUser
                    INNER JOIN {$tableGroup} AS G
                        ON G.idGroup = GM.GroupMember_idGroup
            WHERE deletedUser = FALSE AND 
                  U.activeUser = TRUE AND
                  UT.UserTournament_idTournament = {$this->id};
EOD;

        $res = $theDatabase->Query($query);
        $tempUsers = array();
        while($row = $res->fetch_object()) {
            $tempActive = $row->activeUser == 1 ? true : false;
            $tempUsers[$row->idUser] = new user_CUserData($row->idUser, $row->accountUser, $row->nameUser, $row->emailUser, $row->avatarUser, $row->idGroup, $row->armyUser, $tempActive);
        }
        $res -> close();
        
        return $tempUsers;
    }
    
    /**
     * Get participants of a tournament. The participants are either a part
     * of the tournament or has a match result in the tournament. This means
     * that the participant can have left the tournament and still be included
     * in this list.
     * 
     * @param type $theDatabase
     * @return \user_CUserData
     */
    private function getTournamentUsers($theDatabase) {
        $tempUsers = $this ->getActiveUsers($theDatabase);
        
        // Fetch all users active in this tournament.
        $tMatch = DBT_Match;
        $tableUser       = DBT_User;
        $tableGroup      = DBT_Group;
        $tableGroupMember  = DBT_GroupMember;

        $query = <<< EOD
            SELECT
                    idUser,
                    accountUser,
                    nameUser,
                    lastLoginUser,
                    emailUser,
                    armyUser,
                    activeUser,
                    avatarUser,
                    idGroup,
                    nameGroup
            FROM {$tableUser} AS U
                    INNER JOIN {$tMatch} AS UT 
                        ON playerOneMatch_idUser = idUser OR playerTwoMatch_idUser = idUser
                    INNER JOIN {$tableGroupMember} AS GM
                        ON U.idUser = GM.GroupMember_idUser
                    INNER JOIN {$tableGroup} AS G
                        ON G.idGroup = GM.GroupMember_idGroup
            WHERE deletedUser = FALSE AND 
                  U.activeUser = TRUE AND
                  UT.tRefMatch_idTournament = {$this->id};
EOD;

        $res = $theDatabase->Query($query);
        while($row = $res->fetch_object()) {
            $tempActive = $row->activeUser == 1 ? true : false;
            $tempUsers[$row->idUser] = new user_CUserData($row->idUser, $row->accountUser, $row->nameUser, $row->emailUser, $row->avatarUser, $row->idGroup, $row->armyUser, $tempActive);
        }
        $res -> close();
        
        return $tempUsers;
    }
    
    public function getParticipantsSortedByScore($theDatabase, $theRound = 0, $forScoreBoard = false) {
        
        $tempOldUserList = null;
        if ($forScoreBoard) {
            $tempOldUserList = $this ->getTournamentUsers($theDatabase);
        } else {
            $tempOldUserList = $this ->getActiveUsers($theDatabase);
        }
        
        // Now that the users are fetched, create a copy of them and calulate
        // every players score up to (but not including - if not 0, then sum all up) the round that we
        // are about to create.
        $tempUserList = array();
        foreach ($tempOldUserList as $value) {
            
            $tempPlayer = $value->getCopy();
            self::$LOG -> debug("this is the tempplayer " . $tempPlayer->getName());
            $tempPlayer->setTotalScore($this->getTotalScore($tempPlayer, $theRound));
            $tempUserList[] = $tempPlayer;
            self::$LOG -> debug($tempPlayer->getName() . " score: " . $tempPlayer->getTotalScore());
        }
        
        // Sort by score.
        $this->sortByScore($tempUserList);
        
        self::$LOG -> debug("sorted list:");
        foreach ($tempUserList as $value) {
            self::$LOG -> debug($value->getName());
        }
        
        return $tempUserList;
    }
    
    private function createRound($theDatabase, $theRound) {

        $result = array();
        $tempUserList = $this->getParticipantsSortedByScore($theDatabase, $theRound);
        
        // If there are an uneven number of players, the one with lowest score
        // has to be byed (if not byed before).
        $nrOfEntries = count($tempUserList);
        $even = $nrOfEntries%2 == 0;
        // self::$LOG -> debug("number of entries: " . $nrOfEntries);
        if (!$even) {
            $i = $nrOfEntries - 1;
            if ($this->hasBeenByed($tempUserList[$i], $theRound)) {
                // The last entry has already been byed -> must find a switch
                $found = false;
                while (!$found && $i > 0) {
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
            $result[] = new CMatch(-1, $theRound, $playerOne, $playerTwo, $this->byeScore, 0, null);
            
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
            
        self::storeCreatedMatchupsOnRound($theDatabase, $theRound, $result, $this->getId());
            
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
        
        self::$LOG -> debug("forward completed" . $goBackwardAlso);
        
        if ($goBackwardAlso) {
            
            self::$LOG -> debug("going back!");
            // Backward
            $sign = -1;
			$startPos = $numberOfPlayers - 1;
			$stopPos = -1; 
            self::$LOG -> debug("<backwards before> index: " . $index . " sign: " . $sign . " start/stop: " . $startPos . ", " . $stopPos);
            for ($index = $startPos; $index != $stopPos; $index = $index + $sign * 2) {
                self::$LOG -> debug("<backwards> index: " . $index . " sign: " . $sign . " start/stop: " . $startPos . ", " . $stopPos);
                if ($this->hasPlayedBefore($players[$index], $players[$index + $sign], $theNewRound)) {
                    self::$LOG -> debug("** <backwards> hasplayedbefore **");
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
        // self::$LOG -> debug("thePlayer: " . $player->getName() . " and the round: " . $theNewRound);
        
        foreach ($this->tournamentMatrix as $key => $value) {
            if ($key != $theNewRound) {
                foreach ($value as $valueInner) {
                    if ($player->getId() == $valueInner->getPlayerOne()->getId() && 
                            $valueInner->getPlayerTwo()->isEmptyInstance()) {
                        // self::$LOG -> debug("TREEEEEEEUuuuuuuuuuuuuuuuuuuuuuuuuuuuuu");
                        $retVal = true;
                    }
                }
            }
        }
        return $retVal;
    }
    
    private function getTotalScore($player, $theNewRound) {
        $retVal = 0;
        
        self::$LOG -> debug("I gettotalscore");

        // If get total score for each player up to, but not including, theNewRound.
        // If theNewRound is empty, then get total score for each player for all rounds.
        foreach ($this->tournamentMatrix as $key => $value) {
            // self::$LOG -> debug("key: " . $key . " : value: " . $value . " thenewround: " . $theNewRound);
            // self::$LOG -> debug(print_r($value, true));
            if ($key != $theNewRound || empty($theNewRound)) {
                foreach ($value as $valueInner) {
                    //self::$LOG -> debug(print_r($valueInner, true));
                    //self::$LOG -> debug("valueInner: " . $valueInner);

                    if ($player->getId() == $valueInner->getPlayerOne()->getId()) {
                        $retVal += $this->useProxy ? $valueInner->getProxyScorePlayerOne() : $valueInner->getScorePlayerOne();
                    } else if($player->getId() == $valueInner->getPlayerTwo()->getId()) {
                        $retVal += $this->useProxy ? $valueInner->getProxyScorePlayerTwo() : $valueInner->getScorePlayerTwo();
                    }
                }
            }
        }
        self::$LOG -> debug("klar med total score");
        return $retVal;
    }
    
    public function numberOfRoundsPlayed($thePlayer) {
        $retVal = 0;

        // A round counts as played if $players is either player one or two and
        // if at least one of the players has points registered.
        foreach ($this->tournamentMatrix as $value) {
            foreach ($value as $valueInner) {
                if (($valueInner->getScorePlayerOne() > 0) || ($valueInner->getScorePlayerTwo() > 0)) {
                    if (($thePlayer->getId() == $valueInner->getPlayerOne()->getId()) || ($thePlayer->getId() == $valueInner->getPlayerTwo()->getId()) ) {
                        $retVal++;
                    }
                }
            }
        }
        return $retVal;
    }
    
    private function sortByScore(&$players) {
        // Old sort, where the sorting method 'cmp' whas i user_CUserData
        // usort($players, array("user_CUserData", "cmp"));
        // New sort
        usort($players, array($this, "cmpUsers"));
    }
    
    public function cmpUsers($playerA, $playerB) {
        if ($playerA->getTotalScore() == $playerB->getTotalScore()) {
            // Equal score - apply tie breakers. In order, until a tie is broken
            // or all the tie breakers are iterated. Uses strategy pattern.
            foreach ($this->tieBreakers as $value) {
                $result = $value->compare($playerA, $playerB, $this->tournamentMatrix);
                if ($result != 0) {
                    return $result;
                }
            }
            // No breaking of the tie between the players was found. Return 0.
            return 0;
        }
        return ($playerA->getTotalScore() > $playerB->getTotalScore()) ? -1 : 1;
    }
    
    private function deleteCurrentRound($theDatabase) {
        self::$LOG -> debug("deleting round: " . $this->currentRound . " on turnament: " . $this->id);
        // Delete all matchups for this round
        $spDeleteAllMatchesOnRound = DBSP_DeleteAllMatchesOnRound;
        $queryDeleteAll = "CALL {$spDeleteAllMatchesOnRound}({$this->currentRound}, {$this->id});";
        $resDel = $theDatabase->MultiQuery($queryDeleteAll);
        $nrOfStatements = $theDatabase->RetrieveAndIgnoreResultsFromMultiQuery();
        
        if($nrOfStatements != 1) {
            // Delete not OK
            self::$LOG -> debug("ERROR: Kunde inte radera runda.");
        } else {
            // Delete from matrix also
            unset($this->tournamentMatrix[$this->currentRound]);
            $this->currentRound--;
            $this->currentRoundComplete = true;
            $this->currentRoundEdited = false;
        }
    }
    
    private static function storeCreatedMatchupsOnRound($theDatabase, $theRound, $theMatchups, $theTournamentId) {
        // Delete all matchups for this round
        $spDeleteAllMatchesOnRound = DBSP_DeleteAllMatchesOnRound;
        $queryDeleteAll = "CALL {$spDeleteAllMatchesOnRound}({$theRound}, {$theTournamentId});";
        $resDel = $theDatabase->MultiQuery($queryDeleteAll);
        $nrOfStatements = $theDatabase->RetrieveAndIgnoreResultsFromMultiQuery();
        
        if($nrOfStatements != 1) {
            // Delete not OK
            self::$LOG -> debug("ERROR: Kunde inte radera runda.");
            
        } else {
            
            // Delete OK -> update db with new data
            $spCreateMatch = DBSP_CreateMatch;
            foreach ($theMatchups as $value) {
                $query = "CALL {$spCreateMatch}({$value->getPlayerOne()->getId()}, {$value->getPlayerTwo()->getId()}, {$value->getRound()}, {$theTournamentId}, {$value->getScorePlayerOne()}, {$value->getScorePlayerTwo()}, @matchId);";
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
    
    private static function populateMatrixFromDB($theDatabase, $theId, $theScoreProxyManager, &$theCurrentRound, &$theCurrentRoundEdited, &$theCurrentRoundComplete) {
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
            WHERE tRefMatch_idTournament = {$theId}
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
             
             // Create a Match object
             $aMatch = new CMatch($row->id, $row->round, $userRepository->getUser($row->idPlayerOne), $tempPlayerTwo, $row->scorePlayerOne, $row->scorePlayerTwo, $row->lastUpdate);
             
             // Create proxy scores (if no proxy filter exists in db a default proxy filter will be used).
             $proxyFilter = $theScoreProxyManager->getScoreProxyForDiffBetweenPlayers($row->scorePlayerOne, $row->scorePlayerTwo);
             $aMatch->setProxyScorePlayerOne($proxyFilter->getScorePlayerOne());
             $aMatch->setProxyScorePlayerTwo($proxyFilter->getScorePlayerTwo());
             
             $tempMatrix[$row->round][] = $aMatch;     
             
             $updated = true;
             $theCurrentRound = $row->round;
             // if player two is empty (player one byed) then 'edited' should not be considered for this row
             if (!empty($row->idPlayerTwo)) {
                $theCurrentRoundEdited = $row->scorePlayerOne != 0 || $row->scorePlayerTwo != 0 ? true : $theCurrentRoundEdited;
             }
             $theCurrentRoundComplete = $theCurrentRoundComplete && ($row->scorePlayerOne != 0 || $row->scorePlayerTwo != 0) ? true : false;
         }
         $res->close();
         if ($updated !== true) {
             $tempMatrix = null;
         }
         return $tempMatrix;
    }
    
    public function getNextRound() {
        return $this->currentRound + 1;
    }
    
    public function getRoundAsHtml($theRound, $theEditable = false, $admin = false, $showTitle = true) {
        // self::$LOG -> debug("###################### NY RUNDA ########################");
        // Link to images
        $imageLink = WS_IMAGES;
        
        $siteLink = WS_SITELINK;
        
        $tempRound = $this->tournamentMatrix[$theRound];
        
        $loggedOnUser = CUserData::getInstance();
        
        $byedPlayer = null;
        
        $html = "<div id='round{$theRound}' class='round'>";
        
        $thePanel = "";
        $deleteSign = "";
        $refreshSign = "";
        if ($admin && $theRound == $this->currentRound) {
            if ($theRound > 1) {
                $dr = $theRound - 1;
                $deleteSign = "<a href='{$siteLink}?p=matchupap&cr={$dr}&t={$this -> id}' onclick='return confirm(\"Vill du verkligen radera den här rundan?\")'><img src='{$imageLink}/close_24.png'></a>";
            }
            $refreshSign =  <<< EOD
                <a href='{$siteLink}?p=matchupap&cr={$theRound}&t={$this -> id}' onclick='return confirm("Vill du verkligen göra om den här rundan?")'>
                    <img src='{$imageLink}/recycle_24.png' alt='Omskapa runda'>
                </a>
EOD;
        }
 
        $thePanel =  <<< EOD
                <span class='panelMatchup'>
                    <a href='{$siteLink}?p=pdfmatchup&round={$theRound}&st={$this -> id}'>
                        <img src='{$imageLink}/PDF-icon.png'>
                    </a>
                    {$refreshSign}
                    {$deleteSign}
                </span>
EOD;
        
        
        if ($showTitle) {
            $html .= "<h2>Omgång {$theRound}{$thePanel}</h2>";
        }
        
        foreach ($tempRound as $value) {
            // self::$LOG -> debug("--- At start of loop.");
            $html .= "<div class='matchup'>";
            $html .= "<table>";
            if ($value->getPlayerOne()->isEmptyInstance() || $value->getPlayerTwo()->isEmptyInstance()) {
                // self::$LOG -> debug("------- Found byed player");
                $byedPlayer = $value->getPlayerOne()->isEmptyInstance() ? $value->getPlayerTwo() : $value->getPlayerOne();
            } else {
                // self::$LOG -> debug("------- Drawing match.");
                // self::$LOG -> debug(print_r($value, true));
                $html .= "<tr><td>{$value->getPlayerOne()->getAccount()}</td><td class='marker'>-</td><td>{$value->getPlayerTwo()->getAccount()}</td></tr>";
                $html .= "<tr class='inputRow'><td class='pLeft'>";
                if ($this->useProxy) {
                    $html .= "<span class='proxyLeft'>({$value->getProxyScorePlayerOne($this->id)})</span>";
                }
                if ($theEditable && ($value->isPlayerInMatch($loggedOnUser->getId()) || $admin)) {
                    $html .= "<input id='playerOneScore#{$value->getId()}' class='scoreInput' type='text' name='playerOneScore#{$value->getId()}' value='{$value->getScorePlayerOne()}' />";
                } else {
                    $html .= "<div style='display: inline-block; width: 30px;'>" . $value->getScorePlayerOne() . "</div>";
                }
                $html .= "</td><td>&nbsp;</td><td class='pRight'>";
                if ($theEditable && ($value->isPlayerInMatch($loggedOnUser->getId()) || $admin)) {
                    $html .= "<input id='playerTwoScore#{$value->getId()}' class='scoreInput' type='text' name='playerTwoScore#{$value->getId()}' value='{$value->getScorePlayerTwo()}' />";
                } else {
                    $html .= "<div style='display: inline-block; width: 30px;'>" . $value->getScorePlayerTwo() . "</div>";
                }
                if ($this->useProxy) {
                    $html .= "<span class='proxyRight'>({$value->getProxyScorePlayerTwo($this->id)})</span>";
                }
                $html .= "</td></tr>";
            }
            $html .= "</table>";
            $html .= "</div> <!-- End div with matchup class -->";
        }
        if ($byedPlayer != null) {
            $html .= "<p>Spelare som får stå över den här rundan: <span class='byedPlayer'>" . $byedPlayer->getAccount() . "</span></p>";
        }
        if ($theEditable && $theRound == $this->currentRound) {
            $html .= "<div>";
            $html .= "<span><input id='saveScoreButton' type='button' name='postvalues' value='Spara resultat' /></span><span id='info'></span>";
            $html .= "</div>";
        }
        $html .= "</div> <!-- End div with round id -->";
        
        // self::$LOG -> debug("#### Klaaaaaaar");
        
        return $html;
    }
    
    public function getAllRoundsAsHtml($admin = false) {
        $html = "<div id='allRounds'>";
        foreach ($this->tournamentMatrix as $key => $value) {
            $editable = $key == $this->currentRound;
            $html .= $this->getRoundAsHtml($key, $editable, $admin);
        }
        $html .= "</div> <!-- End of allRounds div -->";
        return $html;
    }
    
    public function getAllRoundsAsHtmlNoEdit() {
        $html = "<div id='allRounds'>";
        foreach ($this->tournamentMatrix as $key => $value) {
            $html .= $this->getRoundAsHtml($key, false, false);
        }
        $html .= "</div> <!-- End of allRounds div -->";
        return $html;
    }
    
    public function getRoundAsHtmlForPDF($theRound) {
        
        $tempRound = $this->tournamentMatrix[$theRound];
        
        $byedPlayer = null;

        $html .= "<h2>Omgång {$theRound}</h2><table cellpadding=\"5\">";
        $rowColor = false;
        foreach ($tempRound as $value) {
            if ($value->getPlayerOne()->isEmptyInstance() || $value->getPlayerTwo()->isEmptyInstance()) {
                $byedPlayer = $value->getPlayerOne()->isEmptyInstance() ? $value->getPlayerTwo() : $value->getPlayerOne();
            } else {
                $rowColor = !$rowColor;
                $rowColorClass = $rowColor ? " colored" : "";
                $scoreOne = $this->useProxy ? $value->getProxyScorePlayerOne($this->id) : $value->getScorePlayerOne();
                $scoreTwo = $this->useProxy ? $value->getProxyScorePlayerTwo($this->id) : $value->getScorePlayerTwo();
                $html .= "<tr><td class=\"first{$rowColorClass}\">{$value->getPlayerOne()->getAccount()}</td><td class=\"marker{$rowColorClass}\">&nbsp;-</td><td class=\"{$rowColorClass}\">{$value->getPlayerTwo()->getAccount()}</td></tr>";
                $html .= "<tr><td class=\"first{$rowColorClass}\">{$scoreOne}</td><td class=\"marker{$rowColorClass}\">&nbsp;-</td><td class=\"{$rowColorClass}\">{$scoreTwo}</td></tr>";
            }
        }
        
        $html .= "</table>";
        
        if ($byedPlayer != null) {
            $html .= "<p>Spelare som får stå över den här rundan: <span>" . $byedPlayer->getAccount() . "</span></p>";
        }
        
        return $html;
    }

} // End of Of Class

?>