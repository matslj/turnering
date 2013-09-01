<?php
// ===========================================================================================
//
// File: PPairingOfMatches.php
//
// Description: Sets up the matches.
//
// Author: Mats Ljungquist
//

// $log = logging_CLogger::getInstance(__FILE__);

// -------------------------------------------------------------------------------------------
//
// Get pagecontroller helpers. Useful methods to use in most pagecontrollers
//
$pc = CPageController::getInstance();
//$pc->LoadLanguage(__FILE__);

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();

$intFilter->FrontControllerIsVisitedOrDie();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//

$redirect = "?p=" . $pc->computePage();
$action = $redirect . "p";

// -------------------------------------------------------------------------------------------
//
// Get content of file archive from database
//
$db = new CDatabaseController();
$mysqli = $db->Connect();

$tManager = new CTournamentManager();
$scoreBoardHtmlTable = $tManager->getScoreboardAsHtml($db);

// -------------------------------------------------------------------------------------------
// 
// Read editable text for page
//
$pageName = basename(__FILE__);
$title          = "";
$content 	= "";
$pageId         = 0;

// Get the SP names
$spGetSidaDetails	= DBSP_PGetSidaDetails;

$query = <<< EOD
CALL {$spGetSidaDetails}('$pageName', 0);
EOD;

// Perform the query
$results = Array();
$res = $db->MultiQuery($query);
$db->RetrieveAndStoreResultsFromMultiQuery($results);

// Get article details
$row = $results[0]->fetch_object();
if ($row) {
    $pageId     = $row->id;
    $content    = $row->content;
}
$results[0]->close();

$htmlPageTitleLink = "";
$htmlPageContent = "";
$htmlPageTextDialog = "";

$htmlHead = "";
$javaScript = "";
$needjQuery = true;

require_once(TP_PAGESPATH . 'page/PPageEditDialog.php');

// -------------------------------------------------------------------------------------------
//
// Close DB connection
//
$mysqli->close();

// Link to images
$imageLink = WS_IMAGES;

// -------------------------------------------------------------------------------------------
//
// Create HTML for page
//
$htmlMain = <<<EOD
<h1>{$htmlPageTitleLink}</h1>
    <p>
        {$htmlPageContent}
    </p>
EOD;
if (!empty($htmlPageContent)) {
    $htmlMain .= "<hr class='style-two' />";
}
$htmlMain .= "<div class='section'>";
$htmlMain .= <<<EOD
    <table id='scoreboardPresentation'>
        <tr>
            <td id='firstColScore'></td>
            <td id='secondColScore'>
                {$scoreBoardHtmlTable}
            </td>
            <td id='thirdColScore'></td>
        </tr>
    </table>
EOD;
$htmlMain .= "</div>";
$htmlMain .= $htmlPageTextDialog;

$htmlRight = "";

// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
$page = new CHTMLPage();

// Creating the left menu panel
$htmlLeft = "";

$page->printPage('Resultatlista', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery);
exit;

?>