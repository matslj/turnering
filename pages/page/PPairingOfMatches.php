<?php
// ===========================================================================================
//
// File: PPairingOfMatches.php
//
// Description: Sets up the matches.
//
// Author: Mats Ljungquist
//

$log = logging_CLogger::getInstance(__FILE__);

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
$intFilter->UserIsSignedInOrRecirectToSignIn();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$createRound = $pc->GETisSetOrSetDefault('cr', 0);

CPageController::IsNumericOrDie($createRound);

$redirect = "?p=" . $pc->computePage();
$action = $redirect . "p";
$actionProcess = $redirect. "ap";

$uo = CUserData::getInstance();
$account = $uo -> getAccount();
$userId	= $uo -> getId();
$admin = $uo-> isAdmin();

// $log -> debug("userid: " . $userId);
// Always check whats coming in...
//$pc->IsNumericOrDie($articleId, 0);

// -------------------------------------------------------------------------------------------
//
// Get content of file archive from database
//
$db = new CDatabaseController();
$mysqli = $db->Connect();

$tManager = new CTournamentManager();
$tournament = $tManager->getTournament($db);
$tournamentHtml = $tManager->getTournamentMatchupsAsHtml($db, $admin);

//$query 	= $uo -> isAdmin() ? "CALL {$spListFolders}('')" : "CALL {$spListFolders}({$userId})";
//$res = $db->MultiQuery($query);
//$results = Array();
//$db->RetrieveAndStoreResultsFromMultiQuery($results);
//
//while($row = $results[0]->fetch_object()) {
//    $total = $total + $row->facet;
//    $classSelected = "";
//    if ($row->id == $folderFilter) {
//        $currentFolderName = $row->name;
//        $currentTotal = $row->facet;
//        $classSelected = " selected";
//    }
//    $folderHtml .= "<div class='row{$classSelected}'><a href='{$redirect}&ff={$row->id}'>{$row->name} ({$row->facet})</a></div>";
//}

//$results[0]->close();

$htmlHead = "";
$javaScript = "";

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
    $title      = $row->title;
    $content    = $row->content;
}
$results[0]->close();

$htmlPageTitleLink = "";
$htmlPageContent = "";
$htmlPageTextDialog = "";

require_once(TP_PAGESPATH . 'page/PPageEditDialog.php');

// -------------------------------------------------------------------------------------------
//
// Close DB connection
//
$mysqli->close();

// Link to images
$imageLink = WS_IMAGES;

$nR = $tournament->getNextRound();
$redirectRecreate = $actionProcess . "&cr={$nR}";
$nextLink = "<a href='{$redirectRecreate}'><img style='border: 0;' src='{$imageLink}play_48.png' /></a>";
if ($nR > $tournament->getNrOfRounds() || !$admin) {
    $nextLink = "";
}

// -------------------------------------------------------------------------------------------
//
// Add JavaScript and html head stuff related to JavaScript
//
$js = WS_JAVASCRIPT;
$needjQuery = TRUE;
$htmlHead .= <<<EOD
    <!-- jQuery UI -->
    <script src="{$js}jquery-ui/jquery-ui-1.9.2.custom.min.js"></script>

    <!-- jQuery Form Plugin -->
    <script type='text/javascript' src='{$js}jquery-form/jquery.form.js'></script>
    <script type='text/javascript' src='{$js}myJs/disimg-utils.js'></script>
EOD;

$javaScript .= <<<EOD
// ----------------------------------------------------------------------------------------------
//
//
//
var nLink = "{$nextLink}";

(function($){
    $(document).ready(function() {
        $('input#saveScoreButton').attr('disabled', 'disabled');
    
        // Event declaration
        $('#saveScoreButton').click(function(event) {
            $(event.target).attr('disabled', 'disabled');
            $('span#info').html('');
            if (isComplete()) {
                $("#scoreSubmitDiv").html(nLink);
            } else {
                $("#scoreSubmitDiv").html("");
            }
            saveScores();
        });
        
        $('input.scoreInput').bind('keyup', function() {
            if (isComplete() && !$('span#info').html()) {
                $("#scoreSubmitDiv").html(nLink);
            } else {
                $("#scoreSubmitDiv").html("");
            }
            $('input#saveScoreButton').removeAttr('disabled');
            $('span#info').html('Resultat har ändrats, glöm inte att spara!')
        });
    });
    
    function isComplete() {
        var allTrue = true;
        var minKey = -1,
            maxKey = -1;
        var targetArray = [];
        $(".round input:text").each(function() {
            var inpId = $(this).attr("id");
            var index = inpId.indexOf("#");
            var key = inpId.substring(index + 1);
            if (minKey < 0 || key < minKey) {
                minKey = key;
            }
            if (maxKey < 0 || key > maxKey) {
                maxKey = key;
            }
            var inpVal = parseInt($(this).val(), 10);
            if (isNaN(inpVal)) {
                inpVal = 0;
            }
            
            if (!(key in targetArray)) {
                targetArray[key] = 0;
            }
            targetArray[key] = inpVal + targetArray[key];
            
        });
        
        for (var i = minKey; i <= maxKey; i++) {
            if (targetArray[i] == '' || targetArray[i] == 0) {
                allTrue = false;
            }
        }
//        console.log("minkey: " + minKey);
//        console.log("maxkey: " + maxKey);
//        console.log(targetArray.length);
        return allTrue;
    }
    
    function saveScores() {

        var scoreList = {};
        $('input.scoreInput').each( function() {
            var tempId = $(this).attr('id');
            var indexOfHashmark = tempId.indexOf('#');
            var player = tempId.substring(0, indexOfHashmark);
            var matchId = tempId.substring(indexOfHashmark + 1);
            
            if (typeof scoreList[matchId] === "undefined") {
                scoreList[matchId] = {};
                scoreList[matchId]['matchId'] = matchId;
            }
            
            scoreList[matchId][player] = $(this).val();
        });
        
        var revisedScoreList = [];
        for (var key in scoreList) {
            if (scoreList.hasOwnProperty(key)) {
               revisedScoreList.push(scoreList[key]);
            }
        }
        
        var jsonScore = JSON.stringify(revisedScoreList);

        // Förbered Ajax-call
        $.ajax({
            url:'{$action}',
            type:'POST',
            dataType: "json",
            data: {"scores":jsonScore},
            success: function(data) {
                if (data.status == 'ok') {
                    console.log("klar!!");
                } else {
                    console.log(data.message);
                }
            }
        });
    }
})(jQuery);
EOD;
            
$redirectRecreate = $redirect . "&cr={$tournament->getNextRound()}";
$nextRound = "<div style='width: 48px; margin: 0 auto;' id='scoreSubmitDiv'>";
if ($tournament->isCurrentRoundComplete()) {
    $nextRound .= $nextLink;
}
$nextRound .= "</div>";
// $headerHtml = empty($currentFolderName) ? "Alla bilder" : "Bilder i katalogen: " . $currentFolderName;

// -------------------------------------------------------------------------------------------
//
// Create HTML for page
//
$htmlMain = <<<EOD
    <h1>{$htmlPageTitleLink}</h1>
    <p>
        {$htmlPageContent}
    </p>
    <div class='sectionMatchup'>
    {$tournamentHtml}
    {$nextRound}
    {$htmlPageTextDialog}
    </div>
EOD;

$htmlRight = "";

// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
$page = new CHTMLPage();

// Creating the left menu panel
$htmlLeft = "";

$page->printPage('Matchning', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery);
exit;

?>