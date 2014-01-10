// -------------------------------------------------------------------------
// --
// --          THE PARTICIPATION NAMESPACE
// --
// -------------------------------------------------------------------------

/**
 * Js for the PUpcomingTournament.php page.
 * This library requires jQuery.
 *
 * @author Mats Ljungquist, 2014
 */

// This is for the PUpcomingTournament.php page
tournament.namespace('participation');

tournament.participation = {
    idLoginJoinLeaveDiv : "#loginJoinLeave",
    classJoinLeaveLink  : ".joinLeave",
    idJoinLink          : "#join",
    idLeaveLink         : "#leave",
    idParticipantTable  : "#participantList",
    idNrOfParticipants  : "#antalDeltagare",
    
    init : function (theActionUrl, theTournamentId) {
        
        $(tournament.participation.idLoginJoinLeaveDiv).click(function(event) {
            if ($(event.target).is(tournament.participation.idJoinLink)) {
                tournament.participation.saveStatus(theActionUrl, theTournamentId, "join");
                event.preventDefault();
            } else if ($(event.target).is(tournament.participation.idLeaveLink)) {
                tournament.participation.saveStatus(theActionUrl, theTournamentId, "leave");
                event.preventDefault();
            }
        });
    },
    
    saveStatus : function (actionUrl, theTournamentId, theAction) {
        
        var status = {
            tournamentId: theTournamentId,
            action: theAction
        };
        
        var jsonData = JSON.stringify(status);

        // Förbered Ajax-call
        $.ajax({
            url: actionUrl,
            type:'POST',
            dataType: "json",
            data: {"status":jsonData},
            success: function(data) {
                if (data.status == 'ok') {
                    if (data.action == 'join') {
                        $(tournament.participation.idLoginJoinLeaveDiv).html("<a id='leave' class='joinLeave' href='#'>Lämna turneringen</a>");
                    } else {
                        $(tournament.participation.idLoginJoinLeaveDiv).html("<a id='join' class='joinLeave' href='#'>Gå med i turneringen</a>");
                    }
                    tournament.participation.createParticipantTable(data.participants);
                } else {
                    console.log(data.message);
                }
            }
        });
    },
    
    createParticipantTable : function (participantListJson) {
        var htmlOut = "";
        var counter = 0;
        for (var i in participantListJson) {
            htmlOut += "<tr>";
            htmlOut += "<td>" + participantListJson[i].account + "</td>";
            htmlOut += "<td>" + participantListJson[i].army + "</td>";
            htmlOut += "</tr>";
            counter++;
        }
        $(tournament.participation.idParticipantTable).html(htmlOut);
        $(tournament.participation.idNrOfParticipants).html("Antal: " + counter);
    }
};