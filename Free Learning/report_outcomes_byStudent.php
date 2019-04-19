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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_outcomes_byStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Outcomes by Student'));
         
    echo '<h2>';
    echo __($guid, 'Choose Student', 'Free Learning');
    echo '</h2>';

    $gibbonPersonID = null;
    if (isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }
    ?>

    <form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
        <table class='smallIntBorder' cellspacing='0' style="width: 100%">
            <tr>
                <td style='width: 275px'>
                    <b><?php echo __($guid, 'Student') ?> *</b><br/>
                </td>
                <td class="right">
                    <select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
                        <option></option>
                        <optgroup label='--<?php echo __($guid, 'Students by Roll Group') ?>--'>
                            <?php
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
                    while ($rowSelect = $resultSelect->fetch()) {
                        $selected = '';
                        if ($rowSelect['gibbonPersonID'] == $gibbonPersonID) {
                            $selected = 'selected';
                        }
                        echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".htmlPrep($rowSelect['name']).' - '.formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
                    }
                    ?>
                        </optgroup>
                        <optgroup label='--<?php echo __($guid, 'Students by Name') ?>--'>
                            <?php
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
                    while ($rowSelect = $resultSelect->fetch()) {
                        echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['name']).')</option>';
                    }
                    ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan=2 class="right">
                    <input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_outcomes_byStudent.php">
                    <input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
                </td>
            </tr>
        </table>
    </form>
    <?php

    if ($gibbonPersonID != '') {
        $output = '';
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        //Check the years groups the student has been enroled into
        $proceed = true;
        $gibbonYearGroupIDWhere = '';
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT gibbonRollGroup.name AS rollGroup, gibbonSchoolYear.name AS schoolYear, gibbonStudentEnrolment.gibbonYearGroupID FROM gibbonStudentEnrolment JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed due to a database error.');
            echo '</div>';
            $proceed = false;
        } else {
            //Make list of year groups for where
            $gibbonYearGroupIDWhere = '(';
            while ($row = $result->fetch()) {
                $gibbonYearGroupIDWhere .= "gibbonYearGroupIDList LIKE '%".$row['gibbonYearGroupID']."%' OR ";
            }
            $gibbonYearGroupIDWhere = substr($gibbonYearGroupIDWhere, 0, -4).')';
        }

        if ($proceed == true) {
            //Create array of each outcome the student has met in Free Learning
            try {
                $dataFreeLearning = array('gibbonPersonIDStudent' => $gibbonPersonID);
                $sqlFreeLearning = "SELECT gibbonOutcomeID, freeLearningUnit.name FROM freeLearningUnit
                    JOIN freeLearningUnitOutcome ON (freeLearningUnitOutcome.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    WHERE (status='Complete - Approved' OR status='Exempt') AND gibbonPersonIDStudent=:gibbonPersonIDStudent
                    ORDER BY gibbonOutcomeID";
                $resultFreeLearning = $connection2->prepare($sqlFreeLearning);
                $resultFreeLearning->execute($dataFreeLearning);
            } catch (PDOException $e) {
            }

            $outcomesMet = array();
            $outcomesNotMet = array();
            $outcomesNotMetCount = 0;
            while ($rowFreeLearning = $resultFreeLearning->fetch()) {
                if (isset($outcomesMet[$rowFreeLearning['gibbonOutcomeID']][0])) {
                    ++$outcomesMet[$rowFreeLearning['gibbonOutcomeID']][0];
                    $outcomesMet[$rowFreeLearning['gibbonOutcomeID']][1] .= ', '.$rowFreeLearning['name'];
                } else {
                    $outcomesMet[$rowFreeLearning['gibbonOutcomeID']][0] = 1;

                    $outcomesMet[$rowFreeLearning['gibbonOutcomeID']][1] = $rowFreeLearning['name'];
                }
            }

            //Get all school and department outcomes for the students' years in school and store in variable
            $output = '';
            $output .= '<h4>';
            $output .= __($guid, 'Outcome Completion', 'Free Learning');
            $output .= '</h4>';
            try {
                $dataOutcomes = array('gibbonPersonID' => $gibbonPersonID);
                $sqlOutcomes = "SELECT gibbonOutcome.*, gibbonDepartment.name AS department FROM gibbonOutcome LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE $gibbonYearGroupIDWhere AND active='Y' ORDER BY field(Scope,'School','Learning Area'), category, name";
                $resultOutcomes = $connection2->prepare($sqlOutcomes);
                $resultOutcomes->execute($dataOutcomes);
            } catch (PDOException $e) {
            }

            if ($resultOutcomes->rowCount() < 1) {
                $output .= "<div class='error'>";
                $output .= __($guid, 'There are no records to display.');
                $output .= '</div>';
            } else {
                $output .= "<table cellspacing='0' style='width: 100%'>";
                $output .= "<tr class='head'>";
                $output .= '<th>';
                $output .= __($guid, 'Scope').'<br/>';
                $output .= "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Category').'</span>';
                $output .= '</th>';
                $output .= '<th>';
                $output .= __($guid, 'Name');
                $output .= '</th>';
                $output .= '<th>';
                $output .= __($guid, 'Status');
                $output .= '</th>';
                $output .= '</tr>';

                $count = 0;
                while ($rowOutcomes = $resultOutcomes->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                        //COLOR ROW BY STATUS!
                        $output .= "<tr class=$rowNum>";
                    $output .= '<td>';
                    if ($rowOutcomes['scope'] == 'School') {
                        $output .= 'School';
                    } elseif ($rowOutcomes['department'] != '') {
                        $output .= $rowOutcomes['department'];
                    }
                    if ($rowOutcomes['category'] != '') {
                        $output .= "<br/><span style='font-size: 85%; font-style: italic'>".$rowOutcomes['category'].'</span>';
                    }
                    $output .= '</td>';
                    $output .= '<td>';
                    $output .= '<b>'.$rowOutcomes['name'].'</b><br/>';
                    $output .= '</td>';
                    $output .= '<td>';
                    if (isset($outcomesMet[$rowOutcomes['gibbonOutcomeID']][0]) == false) {
                        $output .= "<img title='".__($guid, 'Outcome not met', 'Free Learning')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                        $outcomesNotMet[$outcomesNotMetCount] = $rowOutcomes['gibbonOutcomeID'];
                        ++$outcomesNotMetCount;
                    } else {
                        $output .= "<img title='".__($guid, 'Outcome met in units:', 'Free Learning').' '.htmlPrep($outcomesMet[$rowOutcomes['gibbonOutcomeID']][1])."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> x".$outcomesMet[$rowOutcomes['gibbonOutcomeID']][0];
                    }
                    $output .= '</td>';
                    $output .= '</tr>';

                    ++$count;
                }
                $output .= '</table>';
            }

            //Recommend units based on missing outcomes, year group and prerequisites met
            if (count($outcomesNotMet) > 0) {
                $outcomesNotMetWhere = '(';
                foreach ($outcomesNotMet as $outcomeNotMet) {
                    $outcomesNotMetWhere .= "gibbonOutcomeID=$outcomeNotMet OR ";
                }
                $outcomesNotMetWhere = substr($outcomesNotMetWhere, 0, -4).')';

                try {
                    $dataRecommend['gibbonPersonID'] = $gibbonPersonID;
                    $dataRecommend['gibbonPersonID2'] = $gibbonPersonID;
                    $dataRecommend['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                    $sqlRecommend = "SELECT DISTINCT freeLearningUnitStudent.status, freeLearningUnit.freeLearningUnitID, freeLearningUnit.*, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2
                        FROM freeLearningUnit
                        JOIN freeLearningUnitOutcome ON (freeLearningUnitOutcome.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                        LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID2)
                        LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID)
                        JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                        JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID)
                        WHERE $outcomesNotMetWhere AND (status IS NULL OR NOT status='Current') AND active='Y' AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber)
                        ORDER BY RAND() LIMIT 0, 3";
                    $resultRecommend = $connection2->prepare($sqlRecommend);
                    $resultRecommend->execute($dataRecommend);
                } catch (PDOException $e) {
                    echo $e->getMessage();
                }

                if ($resultRecommend->rowCount() > 0) {
                    $learningAreaArray = getLearningAreaArray($connection2);
                    $authors = getAuthorsArray($connection2);
                    $blocks = getBlocksArray($connection2);

                    echo '<h4>';
                    echo __($guid, 'Recommended Units', 'Free Learning');
                    echo '</h4>';
                    echo '<p>';
                    echo __($guid, 'The units below (up to a total of 3) are chosen at random from a list of units that have outcomes this student has not met, but can do based on year group.', 'Free Learning');
                    echo '</p>';

                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo "<th style='width: 150px!important; text-align: center'>";
                    echo __($guid, 'Name').'</br>';
                    echo '</th>';
                    echo "<th style='width: 100px!important'>";
                    echo __($guid, 'Authors', 'Free Learning').'<br/>';
                    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Learning Areas').'</span>';
                    echo '</th>';
                    echo "<th style='max-width: 325px!important'>";
                    echo __($guid, 'Difficulty', 'Free Learning').'</br>';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Length', 'Free Learning').'</br>';
                    echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Minutes', 'Free Learning').'</span>';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Grouping', 'Free Learning').'</br>';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Prerequisites', 'Free Learning').'</br>';
                    echo '</th>';
                    echo "<th style='min-width: 70px'>";
                    echo __($guid, 'Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    while ($rowRecommend = $resultRecommend->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                        echo "<td style='text-align: center; font-size: 125%'>";
                        echo $rowRecommend['status'];
                        echo "<div style='font-weight: bold; margin-top: 5px; margin-bottom: -6px ;'>".$rowRecommend['name'].'</div><br/>';
                        if ($rowRecommend['logo'] == null) {
                            echo "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_125.jpg'/><br/>";
                        } else {
                            echo "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$rowRecommend['logo']."'/><br/>";
                        }
                        echo '</td>';
                        echo '<td>';
                        foreach ($authors as $author) {
                            if ($author[0] == $rowRecommend['freeLearningUnitID']) {
                                if ($author[3] == '') {
                                    echo $author[1].'<br/>';
                                } else {
                                    echo "<a target='_blank' href='".$author[3]."'>".$author[1].'</a><br/>';
                                }
                            }
                        }
                        if ($rowRecommend['gibbonDepartmentIDList'] != '') {
                            echo "<span style='font-size: 85%;'>";
                            $departments = explode(',', $rowRecommend['gibbonDepartmentIDList']);
                            foreach ($departments as $department) {
                                echo $learningAreaArray[$department].'<br/>';
                            }
                            echo '</span>';
                        }
                        echo '</td>';
                        echo '<td>';
                        echo '<b>'.$rowRecommend['difficulty'].'</b><br/>';
                        echo '</td>';
                        echo '<td>';
                        $timing = null;
                        if ($blocks != false) {
                            foreach ($blocks as $block) {
                                if ($block[0] == $rowRecommend['freeLearningUnitID']) {
                                    if (is_numeric($block[2])) {
                                        $timing += $block[2];
                                    }
                                }
                            }
                        }
                        if (is_null($timing)) {
                            echo '<i>'.__($guid, 'N/A').'</i>';
                        } else {
                            echo $timing;
                        }
                        echo '</td>';
                        echo '<td>';
                        if ($rowRecommend['grouping'] != '') {
                            $groupings = explode(',', $rowRecommend['grouping']);
                            foreach ($groupings as $grouping) {
                                echo ucwords($grouping).'<br/>';
                            }
                        }
                        echo '</td>';
                        echo '<td>';
                        $prerequisitesActive = prerequisitesRemoveInactive($connection2, $rowRecommend['freeLearningUnitIDPrerequisiteList']);
                        if ($prerequisitesActive != false) {
                            $prerequisites = explode(',', $prerequisitesActive);
                            $units = getUnitsArray($connection2);
                            foreach ($prerequisites as $prerequisite) {
                                echo $units[$prerequisite][0].'<br/>';
                            }
                        } else {
                            echo '<i>'.__($guid, 'None', 'Free Learning').'<br/></i>';
                        }
                        if ($prerequisitesActive != false) {
                            $prerequisitesMet = prerequisitesMet($connection2, $gibbonPersonID, $prerequisitesActive);
                            if ($prerequisitesMet) {
                                echo "<span style='font-weight: bold; color: #00cc00'>".__($guid, 'OK!', 'Free Learning').'</span>';
                            } else {
                                echo "<span style='font-weight: bold; color: #cc0000'>".__($guid, 'Not Met', 'Free Learning').'</span>';
                            }
                        }
                        echo '</td>';
                        echo '<td>';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$rowRecommend['freeLearningUnitID']."&gibbonDepartmentID=&difficulty=&name='><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }

            //Output table of met outcomes
            echo $output;
        }
    }
}
?>
