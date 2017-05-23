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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');
$schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details_approval.php', $connection2);

//Get params
$freeLearningUnitStudentID = '';
if (isset($_GET['freeLearningUnitStudentID'])) {
    $freeLearningUnitStudentID = $_GET['freeLearningUnitStudentID'];
}
$freeLearningUnitID = '';
if (isset($_GET['freeLearningUnitID'])) {
    $freeLearningUnitID = $_GET['freeLearningUnitID'];
}
$canManage = false;
if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
    $canManage = true;
}
$showInactive = 'N';
if ($canManage and isset($_GET['showInactive'])) {
    $showInactive = $_GET['showInactive'];
}
$applyAccessControls = 'Y';
if ($canManage and isset($_GET['applyAccessControls'])) {
    $applyAccessControls = $_GET['applyAccessControls'];
}
$gibbonDepartmentID = '';
if (isset($_GET['gibbonDepartmentID'])) {
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
}
$difficulty = '';
if (isset($_GET['difficulty'])) {
    $difficulty = $_GET['difficulty'];
}
$name = '';
if (isset($_GET['name'])) {
    $name = $_GET['name'];
}
$view = '';
if (isset($_GET['view'])) {
    $view = $_GET['view'];
}
if ($view != 'grid' and $view != 'map') {
    $view = 'list';
}
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
if ($canManage) {
    if (isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }
}

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_delete.php') == false and !$canManage) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    if ($highestAction == false) { echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'>".__($guid, 'Browse Units', 'Free Learning')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse_details.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&sidebar=true&tab=2'>".__($guid, 'Unit Details', 'Free Learning')."</a> > </div><div class='trailEnd'>".__($guid, 'Delete Enrolment', 'Free Learning').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        if ($freeLearningUnitID == '' or $freeLearningUnitStudentID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                $sql = 'SELECT * FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();

                $proceed = false;
                //Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s) or if canManage is true
                $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php', 'Manage Units_all');
                if ($manageAll == true) {
                    $proceed = true;
                }
                else if ($row['enrolmentMethod'] == 'schoolMentor' && $row['gibbonPersonIDSchoolMentor'] == $_SESSION[$guid]['gibbonPersonID']) {
                    $proceed = true;
                } else {
                    $learningAreas = getLearningAreas($connection2, $guid, true);
                    if ($learningAreas != '') {
                        for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                $proceed = true;
                            }
                        }
                    }
                }

                if ($proceed == false) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_browse_details_deleteProcess.php?freeLearningUnitStudentID=$freeLearningUnitStudentID&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view" ?>">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">
							<tr>
								<td>
									<b><?php echo __($guid, 'Are you sure you want to delete this record?'); ?></b><br/>
									<span style="font-size: 90%; color: #cc0000"><i><?php echo __($guid, 'This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!'); ?></i></span>
								</td>
								<td class="right">

								</td>
							</tr>
							<tr>
								<td>
									<input name="freeLearningUnitStudentID" id="freeLearningUnitStudentID" value="<?php echo $freeLearningUnitStudentID ?>" type="hidden">
									<input name="freeLearningUnitID" id="freeLearningUnitID" value="<?php echo $freeLearningUnitID ?>" type="hidden">
									<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
									<input type="submit" value="<?php echo __($guid, 'Yes'); ?>">
								</td>
								<td class="right">

								</td>
							</tr>
						</table>
					</form>
					<?php

                }
            }
        }
    }
}
?>
