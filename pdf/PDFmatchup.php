<?php
/**
 * Creates an example PDF TEST document using TCPDF
 * 
 * @author Mats Ljungquist
 */

require_once(TP_ROOT . 'pdf/tcpdf_config_alt.php');

// Include the main TCPDF library (search for installation path).
require_once(TP_PDFGENPATH . 'tcpdf.php');

// ** Start get request parameters
$pc = CPageController::getInstance();
$round = $pc->GETisSetOrSetDefault('round', 0);
// ** End get request parameters

// ** Start loading tournament data
$db 	= new CDatabaseController();
$mysqli = $db->Connect();
$tempManager = new CTournamentManager();
$tournament = $tempManager->getTournament($db);
$matchupHtml = $tournament->getRoundAsHtmlForPDF($round);
$mysqli->close();
// ** End loading tournament data

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$html = <<< EOD
    <style>
        td.marker {
            width: 20px;
        }
        td.first {
            width: 160px;
            text-align: right;
        }
        p span {
            font-weight: bold;
            color: red;
        }
    </style>
    
    {$matchupHtml}
EOD;

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ML');
$pdf->SetTitle('Matchning');
$pdf->SetSubject('Turnering');

// set default header data
$pdf->SetHeaderData('', 0, "Turnering", "(turneringsdatum: " . $tournament->getTournamentDateFrom()->getDate() . " - " . $tournament->getTournamentDateTom()->getDate() . ")");

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

// close and output PDF document
$pdf->Output('matchup.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
