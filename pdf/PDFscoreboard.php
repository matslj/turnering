<?php
/**
 * Creates an example PDF TEST document using TCPDF
 * 
 * @author Mats Ljungquist
 */

$log = logging_CLogger::getInstance(__FILE__);

require_once(TP_ROOT . 'pdf/tcpdf_config_alt.php');

// Include the main TCPDF library (search for installation path).
require_once(TP_PDFGENPATH . 'tcpdf.php');

// extend TCPF with custom functions
class ScorePDF extends TCPDF {
    
    private $headerTitle = '';
    
    //Page header
	public function Header() {
		// Logo
		// $image_file = K_PATH_IMAGES.'logo_example.jpg';
		// $this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		// Set font
		$this->SetFont('helvetica', 'B', 20);
		// Title
		$this->Cell(0, 15, 'Resultatlista - Warhammer fantasy battle', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln();
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(0, 15, $this->headerTitle, 0, false, 'C', 0, '', 0, false, 'M', 'M');
	}

	// Page footer
	public function Footer() {
		// Position at 15 mm from bottom
		$this->SetY(-15);
		// Set font
		$this->SetFont('helvetica', 'I', 8);
		// Page number
		$this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}

	// Load table data from file
	public function LoadData($file) {
		// Read file lines
		$lines = file($file);
		$data = array();
		foreach($lines as $line) {
			$data[] = explode(';', chop($line));
		}
		return $data;
	}

	// Colored table
	public function ColoredTable($header,$data) {
		// Colors, line width and bold font
		$this->SetFillColor(255, 142, 0);
		$this->SetTextColor(255);
		$this->SetDrawColor(128, 0, 0);
		$this->SetLineWidth(0.3);
		$this->SetFont('', 'B');
		// Header - column widths
		$w = array(10, 20, 20, 120);
        
        // Header - Draw header style, color and content
        $i = 0;
        $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        $this->Cell($w[$i+1], 7, $header[$i+1], 1, 0, 'C', 1);
        $this->Cell($w[$i+2], 7, $header[$i+2], 1, 0, 'C', 1);
        $this->Cell($w[$i+3], 7, $header[$i+3], 1, 0, 'L', 1);
		$this->Ln();
		// Color and font restoration
		$this->SetFillColor(224, 235, 255);
		$this->SetTextColor(0);
		$this->SetFont('');
		// Data - Content row data
		$fill = 0;
		foreach($data as $row) {
			$this->Cell($w[0], 6, $row[0], 'LR', 0, 'C', $fill);
			$this->Cell($w[1], 6, number_format($row[1]), 'LR', 0, 'R', $fill);
			$this->Cell($w[2], 6, $row[2], 'LR', 0, 'C', $fill);
			$this->Cell($w[3], 6, $row[3], 'LR', 0, 'L', $fill);
			$this->Ln();
			$fill=!$fill;
		}
		$this->Cell(array_sum($w), 0, '', 'T');
	}
    
    public function setHeaderTitle($headerTitle) {
        $this->headerTitle = $headerTitle;
    }

}

// ** Start get request parameters
$pc = CPageController::getInstance();
$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);
CPageController::IsNumericOrDie($selectedTournament);

// ** Start loading tournament data
$db 	= new CDatabaseController();
$mysqli = $db->Connect();
$tempManager = new CTournamentManager();
$tournament = $tempManager->getTournament($db, $selectedTournament);
$participants = $tournament->getParticipantsSortedByScore($db);
$data = array(); // This is the data which is to be sent to the pdf table
$i = 1;
if (!empty($participants)) {
    foreach ($participants as $value) {
        $subArray = array();
        $subArray[] = $i;
        $subArray[] = $value->getTotalScore();
        $subArray[] = $tournament->numberOfRoundsPlayed($value);
        $subArray[] = "{$value->getName()} ({$value->getArmy()})";
        $data[] = $subArray;
        $i++;
    }
}

$mysqli->close();
// ** End loading tournament data

// create new PDF document
$pdf = new ScorePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setHeaderTitle("(turneringsdatum: " . $tournament->getTournamentDateFrom()->getDate() . " - " . $tournament->getTournamentDateTom()->getDate() . ", " . $tournament->getPlace() . ")");

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ML');
$pdf->SetTitle(WS_TITLE);
$pdf->SetSubject(WS_SUB_TITLE);

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT + 5, PDF_MARGIN_TOP + 10, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER + 10);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set font
$pdf->SetFont('helvetica', '', 12);

// add a page
$pdf->AddPage();

// column titles
$header = array('#', 'Poäng', 'Matcher', 'Namn');

// print colored table
$pdf->ColoredTable($header, $data);

// ---------------------------------------------------------

// close and output PDF document
$pdf->Output('scoreboard.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
