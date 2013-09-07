/* 
 * This library requires jQuery.
 * 
 * @author Mats Ljungquist, 2013
 */

// -------------------------------------------------------------------------
// --
// --          THE TOURNAMENT NAMESPACE (this is the root namespace)
// --
// -------------------------------------------------------------------------

var tournament = tournament || {};

tournament = {
    // Below are tournament constants
    errorClass     : ".errorMsg",
    
    // Messages
    infoMsg        : "Glöm inte att spara/uppdatera!",
    
    /**
     * Namespace creator
     */
    namespace : function (name) {
        var parts = name.split('.');
        var current = tournament;
        for (var i in parts) {
            if (!current[parts[i]]) {
                current[parts[i]] = {};
            }
            current = current[parts[i]];
        }
    },
    
    /**
     * Creates an error message in the form of a html ul-list.
     * Places the error message in the element with the errorClass class.
     */
    createErrorMsg : function (errors) {
        var retHtml = "<ul>";
        for (var i = 0; i < errors.length; i++) {
            retHtml = retHtml + "<li>" + errors[i] + "</li>";
        }
        retHtml = retHtml + "</ul>";
        $(this.errorClass).html(retHtml);
    },
    
    /**
     * Clears the error messege (if set).
     */
    clearErrorMsg  : function () {
        $(this.errorClass).html('');
    },
    
    /**
     * Checks if an incoming string is a date.
     */
    isDate         : function (input) {
        var validformat=/^\d{4}-\d{2}-\d{2}$/ //Basic check for format validity
        if (!validformat.test(input)) {
            return false;
        } else { 
            //Detailed check for valid date ranges

            var yearfield=input.split("-")[0]
            var monthfield=input.split("-")[1]
            var dayfield=input.split("-")[2]
            
            if (parseInt(yearfield, 10) > 2030) {
                return false;
            }

            var dayobj = new Date(yearfield, monthfield-1, dayfield)
            if ((dayobj.getMonth()+1!=monthfield) || (dayobj.getDate()!=dayfield) || (dayobj.getFullYear()!=yearfield)) {
                return false;
            }
        }
        return true;
    }
};

// -------------------------------------------------------------------------
// --
// --          THE CONFIG NAMESPACE
// --
// -------------------------------------------------------------------------

// This is for the PTournament.php page
tournament.namespace('config');

tournament.config = {
    // Id constants
    idDateFrom  : "input#dateFrom",
    idDateTom   : "input#dateTom",
    idInfo      : "td#info",
    idForm      : "#turneringForm",
    
    // Date pattern
    datePattern : "yy-mm-dd",
    
    init : function () {
        $(this.idDateFrom).datepicker({
            onSelect : function() {
                $(tournament.config.idInfo).html(tournament.infoMsg);
            },
            minDate: 0,
            dateFormat: tournament.config.datePattern
        });
        $(this.idDateTom).datepicker({
            onSelect : function() {
                $(tournament.config.idInfo).html(tournament.infoMsg);
            },
            minDate: 0,
            dateFormat: tournament.config.datePattern
        });

        $(this.idForm).ajaxForm( { beforeSubmit: tournament.config.validate } ); 
        
        $('input').bind('keyup', function() {
            $(tournament.config.idInfo).html(tournament.infoMsg);
        });
    },
    validate : function (formData, jqForm, options) {
        tournament.clearErrorMsg();
        
        var errors = [];
        var dateFrom = $('input[name=dateFrom]').fieldValue()[0]; 
        var hourFrom = $('input[name=hourFrom]').fieldValue()[0];
        var minuteFrom = $('input[name=minuteFrom]').fieldValue()[0];
        var dateTom = $('input[name=dateTom]').fieldValue()[0]; 
        var hourTom = $('input[name=hourTom]').fieldValue()[0];
        var minuteTom = $('input[name=minuteTom]').fieldValue()[0];
        var nrOfRounds = $('input[name=nrOfRounds]').fieldValue()[0];
        var byeScore = $('input[name=byeScore]').fieldValue()[0];

        if (!tournament.isDate(dateFrom, '-')) { 
            errors.push("Ogiltigt datumformat på startdatum");
        }
        if (!tournament.isDate(dateTom, '-')) { 
            errors.push("Ogiltigt datumformat på slutdatum");
        }
        
        var intRegex = /^\d+$/;
        
        if(!intRegex.test(hourFrom) || hourFrom < 0 || hourFrom > 23) {
           errors.push("Felaktigt format på timmar på startdatum");
        }
        if(!intRegex.test(minuteFrom) || minuteFrom < 0 || minuteFrom > 59) {
           errors.push("Felaktigt format på minuter på startdatum");
        }
        if(!intRegex.test(hourTom) || hourTom < 0 || hourTom > 23) {
           errors.push("Felaktigt format på timmar på slutdatum");
        }
        if(!intRegex.test(minuteTom) || minuteTom < 0 || minuteTom > 59) {
           errors.push("Felaktigt format på minuter på slutdatum");
        }

        if(!intRegex.test(nrOfRounds)) {
           errors.push("Antal rundor måste vara ett positivt heltal");
        }
        if(!intRegex.test(byeScore)) {
           errors.push("Bye score måste vara ett positivt heltal");
        }

        if (errors.length != 0) {
            tournament.createErrorMsg(errors);
            return false;
        }
        $(tournament.config.idInfo).html('');
    }
    
};

// -------------------------------------------------------------------------
// --
// --          THE MATCHES NAMESPACE
// --
// -------------------------------------------------------------------------

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
            if (tournament.matches.isComplete() && !$(tournament.matches.idInfo).html()) {
                $(tournament.matches.idScoreSubmitDiv).html(nLink);
            } else {
                $(tournament.matches.idScoreSubmitDiv).html("");
            }
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
            if (minKey == null || key < minKey) {
                minKey = key;
            }
            if (maxKey == null || key > maxKey) {
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