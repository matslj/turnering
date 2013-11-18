<?php

// ===========================================================================================
//
// Class: CMatch
//
// 
// Author: Mats Ljungquist
//
class CMatch {

    private $id;
    private $round;
    private $playerOne;
    private $playerTwo;
    private $scorePlayerOne;
    private $scorePlayerTwo;
    private $proxyScorePlayerOne;
    private $proxyScorePlayerTwo;
    private $lastUpdated;

    function __construct($id, $round, $playerOne, $playerTwo, $scorePlayerOne, $scorePlayerTwo, $lastUpdated) {
        $this->id = $id;
        $this->round = $round;
        $this->playerOne = $playerOne;
        $this->playerTwo = $playerTwo;
        $this->scorePlayerOne = $scorePlayerOne;
        $this->scorePlayerTwo = $scorePlayerTwo;
        $this->lastUpdated = $lastUpdated;
        $this->proxyScorePlayerOne = null;
        $this->proxyScorePlayerTwo = null;
    }

    // ------------------------------------------------------------------------------------
	//
	// Destructor
	//
	public function __destruct() {
	}
    
    public function isPlayerInMatch($thePlayerId) {
        return ($this->playerOne->getId() == $thePlayerId || $this->playerTwo->getId() == $thePlayerId);
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getRound() {
        return $this->round;
    }

    public function setRound($round) {
        $this->round = $round;
    }

    public function getPlayerOne() {
        return $this->playerOne;
    }

    public function setPlayerOne($playerOne) {
        $this->playerOne = $playerOne;
    }

    public function getPlayerTwo() {
        return $this->playerTwo;
    }

    public function setPlayerTwo($playerTwo) {
        $this->playerTwo = $playerTwo;
    }

    public function getScorePlayerOne() {
        return $this->scorePlayerOne;
    }

    public function setScorePlayerOne($scorePlayerOne) {
        $this->scorePlayerOne = $scorePlayerOne;
        $this->proxyScorePlayerOne = null;
    }

    public function getScorePlayerTwo() {
        return $this->scorePlayerTwo;
    }

    public function setScorePlayerTwo($scorePlayerTwo) {
        $this->scorePlayerTwo = $scorePlayerTwo;
        $this->proxyScorePlayerTwo = null;
    }

    public function getLastUpdated() {
        return $this->lastUpdated;
    }

    public function setLastUpdated($lastUpdated) {
        $this->lastUpdated = $lastUpdated;
    }
    
    public function setProxyScorePlayerOne($proxyScorePlayerOne) {
        $this->proxyScorePlayerOne = $proxyScorePlayerOne;
    }

    public function setProxyScorePlayerTwo($proxyScorePlayerTwo) {
        $this->proxyScorePlayerTwo = $proxyScorePlayerTwo;
    }
        
    public function getProxyScorePlayerOne() {
        if ($this->proxyScorePlayerOne == null) {
            return 0;
        }
        return $this->proxyScorePlayerOne;
    }

    public function getProxyScorePlayerTwo() {
        if ($this->proxyScorePlayerTwo == null) {
            return 0;
        }
        return $this->proxyScorePlayerTwo;
    }

} // End of Of Class

?>