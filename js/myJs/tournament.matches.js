// -------------------------------------------------------------------------
// --
// --          THE MATCHES NAMESPACE
// --
// -------------------------------------------------------------------------

/**
 * Js for the PPairingOfMatches.php page.
 * This library requires jQuery.
 *
 * @author Mats Ljungquist, 2013
 */

// This is for the PPairingOfMatches.php page
tournament.namespace('matches');

tournament.matches = {
    idSaveScoreButton : "input#saveScoreButton",
    idInfo            : "span#info",
    idScoreSubmitDiv  : "#scoreSubmitDiv",
    
    classScoreInput   : "input.scoreInput",
    textFields        : ".round input:text",
    
    infoMsg           : "Resultat har ändrats, glöm inte att spara!",
    
    init : function (nLink, actionUrl) {
        $(tournament.matches.idSaveScoreButton).attr('disabled', 'disabled');
    
        // Event declaration
        $(tournament.matches.idSaveScoreButton).click(function(event) {
            $(event.target).attr('disabled', 'disabled');
            $(tournament.matches.idInfo).html('');
            if (tournament.matches.isComplete()) {
                $(tournament.matches.idScoreSubmitDiv).html(nLink);
            } else {
                $(tournament.matches.idScoreSubmitDiv).html("");
            }
            tournament.matches.saveScores(actionUrl);
        });
        
        $(tournament.matches.classScoreInput).bind('keyup', function() {
//            if (tournament.matches.isComplete() && !$(tournament.matches.idInfo).html()) {
//                $(tournament.matches.idScoreSubmitDiv).html(nLink);
//            } else {
                $(tournament.matches.idScoreSubmitDiv).html("");
//            }
            $(tournament.matches.idSaveScoreButton).removeAttr('disabled');
            $(tournament.matches.idInfo).html(tournament.matches.infoMsg);
        });
    },
    
    /**
     * Validates the scores. Every round consists of a score-value-pair. At least
     * one of the score-values on a score-value-pair has to have a value other
     * than 0 or '' in order for that match (score-value-pair) to be considered
     * as valid.
     */
    isComplete : function () {
        var allTrue = true;
        var minKey = null,
            maxKey = null;
        var targetArray = [];
        $(tournament.matches.textFields).each(function() {
            var inpId = $(this).attr("id");
            var index = inpId.indexOf("#");
            var key = inpId.substring(index + 1);
            key = key * 1;
            // console.log("Found key" + key);
            if (minKey == null || key < minKey) {
            //    console.log("Setting minkey to: " + key);
                minKey = key;
            }
            if (maxKey == null || key > maxKey) {
            //    console.log("Setting maxkey to: " + key);
                maxKey = key;
            }
            var inpVal = parseInt($(this).val(), 10);
            if (isNaN(inpVal)) {
                inpVal = 0;
            }
            if (!(key in targetArray)) {
                targetArray[key] = 0;
            }
            targetArray[key] = Math.abs(inpVal) + targetArray[key];
            
        });
        
        for (var i = minKey; i <= maxKey; i++) {
            // console.log("for i: " + i + " - " + targetArray[i]);
            if (targetArray[i] == '' || targetArray[i] == 0) {
                allTrue = false;
            }
        }
//        console.log("minkey: " + minKey);
//        console.log("maxkey: " + maxKey);
//        console.log(targetArray.length);
        return allTrue;
    },
    
    saveScores : function (actionUrl) {

        var scoreList = {};
        $(tournament.matches.classScoreInput).each( function() {
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
            url: actionUrl,
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
};