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
    classInputRows    : "tr.inputRow",
    
    infoMsg           : "Resultat har ändrats, glöm inte att spara!",
    
    proxyFilter       : [],
    
    init : function (nLink, actionUrl, theProxyFilter) {
        $(tournament.matches.idSaveScoreButton).attr('disabled', 'disabled');
    
        tournament.matches.proxyFilter = theProxyFilter;
    
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
    
    getProxyScore : function (scorePlayerOne, scorePlayerTwo) {
        var retObj = {
            playerOne : 0,
            playerTwo : 0
        };
        
        var scoreOne = isNaN(scorePlayerOne) ? 0 : scorePlayerOne;
        var scoreTwo = isNaN(scorePlayerTwo) ? 0 : scorePlayerTwo;
        
        var orgDiff = scoreOne - scoreTwo;
        
        var diff = Math.abs(orgDiff);
        
        var proxy = false;
        
        for (var key in tournament.matches.proxyFilter) {
            // console.log(key, tournament.matches.proxyFilter[key]);
            if (tournament.matches.proxyFilter[key].diffLow <= diff && tournament.matches.proxyFilter[key].diffHigh >= diff) {
                proxy = true;
                retObj.playerOne = tournament.matches.proxyFilter[key].scorePlayerOne;
                retObj.playerTwo = tournament.matches.proxyFilter[key].scorePlayerTwo;
                break;
            }
        }
        
        if (orgDiff < 0 && proxy) {
            var temp = retObj.playerOne;
            retObj.playerOne = retObj.playerTwo;
            retObj.playerTwo = temp;
        }
        
        return retObj;
    },
    
    fixProxyScore : function () {
        // console.log("fixProxyScore()");
        // Fix proxy score
        $(tournament.matches.classInputRows).each( function() {       
            var inputElements = $(this).find(tournament.matches.classScoreInput);
            var spanElements = $(this).find("span");
            if (typeof inputElements !== "undefined" && inputElements.length > 0) {
                // console.log(hasInput[0].val() + " " + hasInput[1].val());
                // console.log($(inputElements[0]).val());
                // console.log($(inputElements[1]).val());
                var obj = tournament.matches.getProxyScore($(inputElements[0]).val(), $(inputElements[1]).val());
                $(spanElements[0]).html("(" + obj.playerOne + ")");
                $(spanElements[1]).html("(" + obj.playerTwo + ")");
            }
        });
    },
    
    saveScores : function (actionUrl) {
        tournament.matches.fixProxyScore();
        var scoreList = {};
        $(tournament.matches.classScoreInput).each( function() {
            // console.log("heere: " + this);
            var tempId = $(this).attr('id');
            var indexOfHashmark = tempId.indexOf('#');
            var player = tempId.substring(0, indexOfHashmark);
            var matchId = tempId.substring(indexOfHashmark + 1);
            
            if (typeof scoreList[matchId] === "undefined") {
                scoreList[matchId] = {};
                scoreList[matchId]['matchId'] = matchId;
            }
            
            // Below means that we are accessing an object looking like this (example):
            // 
            // var scoreList = {
            //     "14": {
            //         "matchId":"14",
            //         "playerOneScore":"21",
            //         "playerTwoScore":"0"
            //     }
            // }
            scoreList[matchId][player] = $(this).val();
            // console.log("ooo " + scoreList[matchId][player]);
        });
        
        // The scoreList above will now be transformed into an array (with objects)
        var revisedScoreList = [];
        for (var key in scoreList) {
            // hasOwnProperty checks so that (in this case) 'key' is a property of the
            // actual scoreList-object, and not an inherited one. This is only useful
            // when iterating with 'for in'.
            if (scoreList.hasOwnProperty(key)) {
               revisedScoreList.push(scoreList[key]);
            }
        }
        
        var jsonScore = JSON.stringify(revisedScoreList);
        // console.log(jsonScore);

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