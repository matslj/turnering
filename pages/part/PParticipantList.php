<?php
// ===========================================================================================
//
// File: PScoreboard.php
//
// Description: This provides the content for a score board dialog in html format.
//
// Author: Mats Ljungquist
//

// $log = logging_CLogger::getInstance(__FILE__);

$pc = CPageController::getInstance();

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();
$intFilter->FrontControllerIsVisitedOrDie();

$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);

// $log->debug("@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ starting this page @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@");

CPageController::IsNumericOrDie($selectedTournament);

// -------------------------------------------------------------------------------------------
//
// Get content of file archive from database
//
$db = new CDatabaseController();
$mysqli = $db->Connect();

$tUser = DBT_User;
$tUserTournament = DBT_UserTournament;
$participantListHtml = "";
$numberOfParticipants = 0;
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
$participantListHtml .= "<form id='pForm' action='{$action}' method='POST'><table id='aptable'><tr><th>&nbsp;</th><th>deltar</th></tr>";
while($row = $result->fetch_object()) {
    $checked = $row->part != null ? " CHECKED" : "";
    $numberOfParticipants++;
    $participantListHtml .= "<tr id='apUser_{$row->idUser}'>"; // This is matched in the tournament.paticipation.js
    $participantListHtml .= "<td>{$row->accountUser}</td>";
    $participantListHtml .= "<td><input id='part_{$row->idUser}' type='checkbox' name='part_{$row->idUser}' {$checked}></td>";
    $participantListHtml .= "</tr>";
}
$participantListHtml .= "</table></form>";
$result -> close();
                
// $log->debug($htmlMain);

// Print the header and page
$charset	= WS_CHARSET;
header("Content-Type: text/html; charset={$charset}");
echo $participantListHtml;
exit;

?>