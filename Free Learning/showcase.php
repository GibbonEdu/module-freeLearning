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

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

$canEdit = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php');

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/showcase.php') == true or ($publicUnits == 'Y' and !$gibbon->session->exists('username')))) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Beadcrumbs
    $page->breadcrumbs
         ->add(__m('Free Learning Showcase'));

    //Set pagination variable
    $page = $_GET['page'] ?? 1;
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Spit out exemplar work
    try {
        $dataWork = array();
        $sqlWork = "SELECT freeLearningUnit.*, freeLearningUnitStudent.*, preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE active='Y' AND exemplarWork='Y' ORDER BY timestampCompleteApproved DESC";
        $resultWork = $connection2->prepare($sqlWork);
        $resultWork->execute($dataWork);
    } catch (PDOException $e) { echo "<div class='error'>".$e->getMessage().'</div>';
    }
    $sqlPage = $sqlWork.' LIMIT '.$gibbon->session->get('pagination').' OFFSET '.(($page - 1) * $gibbon->session->get('pagination'));

    if ($resultWork->rowCount() < 1) { echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($resultWork->rowCount() > $gibbon->session->get('pagination')) {
            printPagination($guid, $resultWork->rowCount(), $page, $gibbon->session->get('pagination'), 'top', '');
        }

        while ($rowWork = $resultWork->fetch()) {
            $students = '';
            $studentList = [];
            if ($rowWork['grouping'] == 'Individual') { //Created by a single student
                $studentList[] = $rowWork['preferredName'];
            } else { //Created by a group of students
                try {
                    $dataStudents = array('collaborationKey' => $rowWork['collaborationKey']);
                    $sqlStudents = "SELECT preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE active='Y' AND collaborationKey=:collaborationKey ORDER BY preferredName";
                    $resultStudents = $connection2->prepare($sqlStudents);
                    $resultStudents->execute($dataStudents);
                } catch (PDOException $e) {
                }
                while ($rowStudents = $resultStudents->fetch()) {
                    $studentList[] = $rowStudents['preferredName'];
                }
            }
            if (!empty($studentList)) {
                $studentList = array_values(array_unique($studentList));
                $last  = array_slice($studentList, -1);
                $first = join(', ', array_slice($studentList, 0, -1));
                $students = join(' & ', array_filter(array_merge(array($first), $last), 'strlen'));
            }

            echo "<h3 style='margin-bottom: 5px'>";
            echo $rowWork['name']."<span style='font-size: 75%; text-transform: none'> by ".$students.'</span>';
            echo '</h3>';
            echo "<p style='font-style: italic; margin-top 0; margin-bottom: 5px; font-size: 10.5px'>";
            echo __($guid, 'Shared on', 'Free Learning').' '.dateConvertBack($guid, $rowWork['timestampCompleteApproved']);
            echo '</p>';
            if ($canEdit) {
                echo "<div class='linkTop'>";
                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitID='.$rowWork['freeLearningUnitID'].'&freeLearningUnitStudentID='.$rowWork['freeLearningUnitStudentID']."&sidebar=true'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'Edit')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a>";
                echo '</div>';
            }
            echo "<table style='width: 100%'>";
            echo '<tr>';
            echo "<td style='text-align: center; vertical-align: top; width: 160px; border-right: none'>";
            if ($rowWork['exemplarWorkThumb'] != '') {
                echo "<img style='width: 150px; height: 150px; margin-bottom: 5px' class='user' src='".$rowWork['exemplarWorkThumb']."'/>";
                if ($rowWork['exemplarWorkLicense'] != '') {
                    echo "<span style='font-size: 85%; font-style: italic'>".$rowWork['exemplarWorkLicense'].'</span>';
                }
            } else {
                if ($rowWork['logo'] != '') {
                    echo "<img style='height: 150px; width: 150px; opacity: 1.0' class='user' src='".$rowWork['logo']."'/><br/>";
                }
                else {
                    echo "<img style='height: 150px; width: 150px; opacity: 1.0' class='user' src='".$gibbon->session->get('absoluteURL').'/themes/'.$gibbon->session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/><br/>";
                }
            }
            echo '</td>';
            echo "<td style='vertical-align: top; border-left: none'>";
            //DISPLAY WORK.
            echo '<h4 style=\'margin-top: 0px\'>'.__($guid, 'Student Work', 'Free Learning').'</h4>';
            if ($rowWork['exemplarWorkEmbed'] =='') { //It's not an embed
                $extension = strrchr($rowWork['evidenceLocation'], '.');
                if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) { //Its an image
                    echo "<p>";
                    if ($rowWork['evidenceType'] == 'File') { //It's a file
                        echo "<a target='_blank' href='".$gibbon->session->get('absoluteURL').'/'.$rowWork['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$gibbon->session->get('absoluteURL').'/'.$rowWork['evidenceLocation']."'/></a>";
                    } else { //It's a link
                        echo "<a target='_blank' href='".$gibbon->session->get('absoluteURL').'/'.$rowWork['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$rowWork['evidenceLocation']."'/></a>";
                    }
                    echo '</p>';
                } else { //Not an image
                    echo '<p class=\'button\'>';
                    if ($rowWork['evidenceType'] == 'File') { //It's a file
                        echo "<a class='button' target='_blank' href='".$gibbon->session->get('absoluteURL').'/'.$rowWork['evidenceLocation']."'>".__($guid, 'Click to View Work', 'Free Learning').'</a>';
                    } else { //It's a link
                        echo "<a class='button' target='_blank' href='".$rowWork['evidenceLocation']."'>".__($guid, 'Click to View Work', 'Free Learning').'</a>';
                    }
                    echo '</p>';
                }
            } else {
                echo '<p>';
                print $rowWork['exemplarWorkEmbed'] ;
                echo '</p>';
            }
            //DISPLAY STUDENT COMMENT
            if ($rowWork['commentStudent'] != '') {
                echo '<h4>'.__($guid, 'Student Comment', 'Free Learning').'</h4>';
                echo '<p style=\'margin-bottom: 0px\'>';
                echo nl2br($rowWork['commentStudent']);
                echo '</p>';
            }
            //DISPLAY TEACHER COMMENT
            if ($rowWork['commentApproval'] != '') {
                if ($rowWork['commentStudent'] != '') {
                    echo '<br/>';
                }
                echo '<h4>'.__($guid, 'Teacher Comment', 'Free Learning').'</h4>';
                echo '<p>';
                echo $rowWork['commentApproval'];
                echo '</p>';
            }
            echo '</td>';
            echo '</tr>';
            echo '</table>';
        }
        if ($resultWork->rowCount() > $gibbon->session->get('pagination')) {
            printPagination($guid, $resultWork->rowCount(), $page, $gibbon->session->get('pagination'), 'bottom', '');
        }
    }
}
