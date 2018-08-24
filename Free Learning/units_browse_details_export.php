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

@session_start();

include '../../functions.php';
include '../../config.php';

include './moduleFunctions.php';

$output = '';

//New PDO DB connection
try {
    $connection2 = new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
    $connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $output .= $e->getMessage();
}

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_export.php') == false) {
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
            $data = array('freeLearningUnitID' => $freeLearningUnitID);
            $sql = 'SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
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

            $output .= "<h1>".$row['name']."</h1>";
            $output .= "<p>".$row['blurb']."</p>";

            $output .= "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\">";
            $output .= '<tbody>';


            $output .= '<tr>';
            $output .= "<td style=\"vertical-align: top; border-top: 1px solid #000\">";
            $output .= "<span style=\"font-size: 115%; font-weight: bold\">".__($guid, 'Unit Name', 'Free Learning').'</span><br/>';
            $output .= '</td>';
            $output .= "<td style=\"vertical-align: top; border-top: 1px solid #000\">";
            $output .= "<span style=\"font-size: 115%; font-weight: bold\">".__($guid, 'Time', 'Free Learning').'</span><br/>';
            $output .= '</td>';
            $output .= "<td style=\"vertical-align: top; text-align: right\" rowspan=\"8\">";
            if ($row['logo'] == null) {
                $output .= "<img style=\"margin: 5px; height: 125px; width: 125px; background-color: #ffffff ; border: 1px solid #000000 ; padding: 4px ; box-shadow: 2px 2px 2px rgba(50,50,50,0.35);\" src=\"".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_125.jpg\"/><br/>";
            } else {
                $output .= "<img style=\"margin: 5px; height: 125px; width: 125px; background-color: #ffffff ; border: 1px solid #000000 ; padding: 4px ; box-shadow: 2px 2px 2px rgba(50,50,50,0.35);\" src=\"".$row['logo']."\"/><br/>";
            }
            $output .= '</td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output .= "<td style='vertical-align: top'>";
            $output .= '<i>'.$row['name'].'</i>';
            $output .= '</td>';
            $output .= "<td style='vertical-align: top'>";
            $timing = null;
            $blocks = getBlocksArray($connection2, $freeLearningUnitID);
            if ($blocks != false) {
                foreach ($blocks as $block) {
                    if ($block[0] == $row['freeLearningUnitID']) {
                        if (is_numeric($block[2])) {
                            $timing += $block[2];
                        }
                    }
                }
            }
            if (is_null($timing)) {
                $output .= '<i>'.__($guid, 'N/A').'</i>';
            } else {
                $output .= '<i>'.$timing.'</i>';
            }
            $output .= '</td>';
            $output .= '</tr>';


            $output .= '<tr style="">';
            $output .= "<td style=\"padding-top: 15px; vertical-align: top; border-top: 1px solid #000\">";
            $output .= "<span style=\"font-size: 115%; font-weight: bold\">".__($guid, 'Difficulty', 'Free Learning').'</span><br/>';
            $output .= '</td>';
            $output .= "<td style=\"padding-top: 15px; vertical-align: top; border-top: 1px solid #000\">";
            $output .= "<span style=\"font-size: 115%; font-weight: bold\">".__($guid, 'Prerequisites', 'Free Learning').'</span><br/>';
            $output .= '</td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output .= "<td style=\"padding-top: 15px; vertical-align: top\">";
            $output .= '<i>'.$row['difficulty'].'</i>';
            $output .= '</td>';
            $output .= "<td style=\"padding-top: 15px; vertical-align: top\">";
            $prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
            if ($prerequisitesActive != false) {
                $prerequisites = explode(',', $prerequisitesActive);
                $units = getUnitsArray($connection2);
                foreach ($prerequisites as $prerequisite) {
                    $output .= '<i>'.$units[$prerequisite][0].'</i><br/>';
                }
            } else {
                $output .= '<i>'.__($guid, 'None', 'Free Learning').'<br/></i>';
            }
            $output .= '</td>';
            $output .= '</tr>';


            $output .= '<tr>';
            $output .= "<td style=\"vertical-align: top; border-top: 1px solid #000\">";
            $output .= "<span style=\"font-size: 115%; font-weight: bold\">".__($guid, 'Departments', 'Free Learning').'</span><br/>';
            $output .= '</td>';
            $output .= "<td style=\"vertical-align: top; border-top: 1px solid #000\">";
            $output .= "<span style=\"font-size: 115%; font-weight: bold\">".__($guid, 'Authors', 'Free Learning').'</span><br/>';
            $output .= '</td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output .= "<td style='vertical-align: top'>";
            $learningAreas = getLearningAreas($connection2, $guid);
            if ($learningAreas == '') {
                $output .= '<i>'.__($guid, 'No Learning Areas available.', 'Free Learning').'</i>';
            } else {
                for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                    if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                        $output .= '<i>'.__($guid, $learningAreas[($i + 1)]).'</i><br/>';
                    }
                }
            }
            $output .= '</td>';
            $output .= "<td style='vertical-align: top'>";
            $authors = getAuthorsArray($connection2, $freeLearningUnitID);
            foreach ($authors as $author) {
                if ($author[3] == '') {
                    $output .= '<i>'.$author[1].'</i><br/>';
                } else {
                    $output .= "<i><a target='_blank' href='".$author[3]."'>".$author[1].'</a></i><br/>';
                }
            }
            $output .= '</td>';
            $output .= '</tr>';



            $output .= '<tr>';
            $output .= "<td style=\"vertical-align: top; border-top: 1px solid #000\">";
            $output .= "<span style=\"font-size: 115%; font-weight: bold\">".__($guid, 'Groupings', 'Free Learning').'</span><br/>';
            $output .= '</td>';
            $output .= "<td style=\"vertical-align: top; border-top: 1px solid #000\">";

            $output .= '</td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output .= "<td style=\"vertical-align: top; border-bottom: 1px solid #000\">";
            if ($row['grouping'] != '') {
                $groupings = explode(',', $row['grouping']);
                foreach ($groupings as $grouping) {
                    $output .= ucwords($grouping).', ';
                }
                $output = substr($output, 0, -2);
            }
            $output .= '</td>';
            $output .= "<td style=\"vertical-align: top; border-bottom: 1px solid #000\">";

            $output .= '</td>';
            $output .= '</tr>';


            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '<div style="height: 30px"></pre>';


            try {
                $dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
                $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';
                $resultBlocks = $connection2->prepare($sqlBlocks);
                $resultBlocks->execute($dataBlocks);
            } catch (PDOException $e) {
                $output .= "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultBlocks->rowCount() < 1) {
                $output .= "<div class='error'>";
                $output .= __($guid, 'There are no records to display.');
                $output .= '</div>';
            } else {
                $resourceContents = '';
                $countBlock = 1;
                while ($rowBlocks = $resultBlocks->fetch()) {
                    //$output .= displayBlockContent($guid, $connection2, $rowBlocks['title'], $rowBlocks['type'], $rowBlocks['length'], $rowBlocks['contents'], $rowBlocks['teachersNotes']);
                    $output .= "<h3>".$countBlock.'. '.$rowBlocks['title']."</h3>";
                    $output .= "<hr style=\"padding-top: 20px\"/>";
                    $output .= "<p>".$rowBlocks['contents']."</p>";

                    $resourceContents .= $rowBlocks['contents'];

                    $countBlock++;
                }
            }


        }
    }
}

//String replacements
//$output = str_replace(array("<br>", "&#13;", "<br/>", "\n"), "<br />", $output);

//Setup for PDF
include "../../lib/tcpdf/tcpdf.php";

//Create PDF objects
$pdf = new TCPDF ('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->addTTFfont('DroidSansFallback');

$pdf->SetCreator($_SESSION[$guid]['organisationName']);
$pdf->SetAuthor($_SESSION[$guid]['organisationName']);
$pdf->SetTitle($_SESSION[$guid]['organisationName'].' Free Learning');

$pdf->SetHeaderData('', 0, $_SESSION[$guid]['organisationName'], 'Free Learning Unit Export');

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
