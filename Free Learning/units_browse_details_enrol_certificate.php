<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$tcpdfFile = '../../lib/tcpdf/tcpdf.php';
if (is_file($tcpdfFile)) {
    include $tcpdfFile;
}

$output = '';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false) {
    //Acess denied
    $output .= "<div class='error'>";
    $output .= __($guid, 'You do not have access to this action.');
    $output .= '</div>';
} else {

    $freeLearningUnitID = '';
    if (isset($_GET['freeLearningUnitID'])) {
        $freeLearningUnitID = $_GET['freeLearningUnitID'];
    }

    if ($freeLearningUnitID == '') {
        $output .= "<div class='error'>";
        $output .= __($guid, 'You have not specified one or more required parameters.');
        $output .= '</div>';
    } else {

        try {
            $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT freeLearningUnit.name, surname, preferredName, (SELECT sum(length) FROM freeLearningUnitBlock WHERE freeLearningUnitBlock.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) AS length, timestampCompleteApproved
                FROM freeLearningUnit
                    JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                WHERE freeLearningUnit.freeLearningUnitID=:freeLearningUnitID
                    AND gibbonPersonIDStudent=:gibbonPersonID
                    AND freeLearningUnitStudent.status='Complete - Approved'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $output .= "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            $output .= "<div class='error'>";
            $output .= __($guid, 'The selected record does not exist, or you do not have access to it.');
            $output .= '</div>';
        } else {
            $row = $result->fetch();

            $output .= "<p></p><p></p>";
            $output .= "<p style=\"font-style: italics; font-size: 150%; text-align: center\">This document certifies that</p>";
            $output .= "<h1 style=\"font-style: italics; font-size: 300%; text-align: center\">".$row['preferredName']." ".$row['surname']."</h1>";
            $output .= "<p style=\"font-style: italics; font-size: 150%; text-align: center\">has successfully completed</p>";
            $output .= "<h1 style=\"font-style: italics; font-size: 300%; text-align: center\">".$row['name']."</h1>";
            if (is_numeric($row['length']) && $row['length'] > 0) {
                    $output .= "<p style=\"font-style: italics; font-size: 150%; text-align: center\">undertaking an estimated ".$row['length']." minutes work on</p>";
            }
            else {
                $output .= "<p style=\"font-style: italics; font-size: 150%; text-align: center\">on".$row['length']."</p>";
            }
            $output .= "<h1 style=\"font-style: italics; font-size: 300%; text-align: center\">".$_SESSION[$guid]['organisationName']."</h1>";
            $output .= "<p style=\"font-style: italics; font-size: 150%; text-align: center\">Approved on ".dateConvertBack($guid, substr($row['timestampCompleteApproved'], 0, 10))."</p>";
            $output .= "<p></p><p></p>";
            $output .= "<div style=\"margin-top: -100px; text-align: center\"><img style=\"height: 100px; width: 400px; background-color: #ffffff ; border: 1px solid #000000 ; padding: 4px ; box-shadow: 2px 2px 2px rgba(50,50,50,0.35);\" src=\"".$_SESSION[$guid]['absoluteURL'].'/'.$_SESSION[$guid]['organisationLogo']."\"/></div><br/>";
        }
    }
}

//Create PDF objects
$pdf = new TCPDF ('P', 'mm', 'A4', true, 'UTF-8', false);

$fontFile = $_SESSION[$guid]['absolutePath']. '/resources/assets/fonts/DroidSansFallback.ttf';
if (is_file($fontFile)) {
    $pdf->addTTFfont($fontFile, 'TrueTypeUnicode', '', 32);
} else {
    $pdf->addTTFfont('DroidSansFallback');
}

$pdf->SetCreator($_SESSION[$guid]['organisationName']);
$pdf->SetAuthor($_SESSION[$guid]['organisationName']);
$pdf->SetTitle($_SESSION[$guid]['organisationName'].' Free Learning');

$pdf->SetHeaderData('', 0, $_SESSION[$guid]['organisationName']);

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}
$pdf->SetFont('helvetica', '', 10);

$pdf->AddPage();

$pdf->writeHTML($output, true, 0, true, 0);

$pdf->lastPage();
$pdf->Output($_SESSION[$guid]['organisationName'].' Free Learning', 'I');
?>
