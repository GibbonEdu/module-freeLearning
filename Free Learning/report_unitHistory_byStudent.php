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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_unitHistory_byStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) { echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        $page->breadcrumbs
             ->add(__m('Unit History By Student'));
              
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
                        <?php
                        if ($highestAction == 'Unit History By Student_myChildren') {
                            ?>
                            <select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
                                <option></option>
                                <?php
                                try {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonFamilyChild ON (gibbonPerson.gibbonPersonID=gibbonFamilyChild.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND childDataAccess='Y') WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $selected = '';
                                if ($rowSelect['gibbonPersonID'] == $gibbonPersonID) {
                                    $selected = 'selected';
                                }
                                echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
                            }
                            ?>
                            </select>
                            <?php

                        } else {
                            ?>
                            <select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
                                <option></option>
                                <optgroup label='--<?php echo __($guid, 'Students by Roll Group', 'Free Learning') ?>--'>
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
                                <optgroup label='--<?php echo __($guid, 'All Users by Name', 'Free Learning') ?>--'>
                                    <?php
                                    try {
                                        $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                        $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID OR gibbonRollGroup.gibbonSchoolYearID IS NULL) AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $selected = '';
                                if ($rowSelect['gibbonPersonID'] == $gibbonPersonID AND $rowSelect['name'] == '') {
                                    $selected = 'selected';
                                }
                                echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true);
                                if ($rowSelect['name'] != '')
                                    echo ' ('.htmlPrep($rowSelect['name']).')';
                                echo '</option>';
                            }
                            ?>
                                </optgroup>
                            </select>
                            <?php
                        }
                           ?>
                    </td>
                </tr>
                <tr>
                    <td colspan=2 class="right">
                        <input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_unitHistory_byStudent.php">
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

            //Check access for parents
            if ($highestAction == 'Unit History By Student_myChildren') {
                try {
                    $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyAdult.childDataAccess='Y' AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                } catch (PDOException $e) {
                }

                if ($resultChild->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'An error occurred.');
                    echo '</div>';
                } else {
                    echo getStudentHistory($connection2, $guid, $gibbonPersonID);
                }
            } else {
                echo getStudentHistory($connection2, $guid, $gibbonPersonID);
            }
        }
    }
}
?>
