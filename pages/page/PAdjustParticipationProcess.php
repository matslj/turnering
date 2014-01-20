<?php
// ===========================================================================================
//
// File: PAdjustParticipationProcess.php
//
// Description: This process page perform join/leave tournament database actions
//              on registered users. The process takes as input tournamentId and
//              an array of userIds. If a userId is present in the array it means
//              that it is (or want to be) a part of the tournament.
//              
//              So this process iterates over all the users in the system and adjust
//              their participation in the specified tournament with the help of
//              the userId array.
//
// Author: Mats Ljungquist
//

$log = logging_CLogger::getInstance(__FILE__);
// -------------------------------------------------------------------------------------------
//
// Get pagecontroller helpers. Useful methods to use in most pagecontrollers
//
$pc = CPageController::getInstance();

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();

$intFilter->FrontControllerIsVisitedOrDie();
$intFilter->UserIsSignedInOrRecirectToSignIn();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$participants	= $pc->POSTisSetOrSetDefault('parts');
$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);

CPageController::IsNumericOrDie($selectedTournament);

// $log -> debug(print_r($participants, true));

//if (is_array($participants) && count($participants) >= 1) {
    
    $updateMap = array();
    
    // Prepare for database access
    $db = new CDatabaseController();
    $mysqli = $db->Connect();
    
    if (CTournamentManager::mayEditTournament($db, $selectedTournament)) {

        // Read all active users and mark, in the result set, which users participate
        // in the selected tournament.
        $tUser = DBT_User;
        $tUserTournament = DBT_UserTournament;
        $query = <<< EOD
        SELECT
        idUser,
        accountUser,
        nameUser,
        armyUser,
        T.UserTournament_idUser as part
    FROM {$tUser} AS U LEFT OUTER JOIN (SELECT UserTournament_idUser FROM {$tUserTournament} WHERE UserTournament_idTournament = {$selectedTournament}) AS T ON T.UserTournament_idUser = U.idUser
    WHERE U.deletedUser = FALSE
          AND U.activeUser = TRUE;
EOD;

        $result = Array();

        // Perform the query and manage results
        $result = $db->Query($query);
        while($row = $result->fetch_object()) {
            $currentId = $row->idUser;
            $oldPart = !is_null($row->part); // is the user currently participating in the tournament?

            $newPart = in_array($currentId, $participants); // does the user want to join or leave the tournament?

            // If old participation is not equal to new participatin a change has been made.
            // Set leave or join SP call depending on the situation.
            if ($oldPart != $newPart) {
                if ($newPart) {
                    $updateMap[$currentId] = DBSP_JoinTournament;
                } else {
                    $updateMap[$currentId] = DBSP_LeaveTournament;
                }
            }
        }
        $result -> close();

        while ($key = key($updateMap)) {
            $query = "CALL {$updateMap[$key]}({$selectedTournament}, {$key});";
            $res = $db->MultiQuery($query);

            $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();

    //        if($nrOfStatements < 1) {
    //        }

            next($updateMap);
        }
    }
    $mysqli->close();
//}

// -------------------------------------------------------------------------------------------
//
// Redirect to another page
//
$pc->RedirectTo($pc->POSTisSetOrSetDefault('redirect'));
exit;

?>