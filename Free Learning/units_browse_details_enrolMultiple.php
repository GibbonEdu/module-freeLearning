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

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details.php', $connection2);
    if ($highestAction == false) { echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        //Get params
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

        //Get action with highest precendence
        $urlParamas = compact('$roleCategory','$freeLearningUnitID','$canManage','$showInactive','$gibbonDepartmentID','$difficulty','$name','$view','$gibbonPersonID');

        $page->breadcrumbs
   			 ->add(__m('Browse Units'), 'units_browse.php', $urlParamas)
   			 ->add(__m('Unit Details'), 'units_browse_details.php', $urlParamas)
   			 ->add(__m('Add Multiple'));

        if ($freeLearningUnitID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $unitList = getUnitList($connection2, $guid, $_SESSION[$guid]['gibbonPersonID'], $roleCategory, $highestAction, null, null, null, $showInactive, $publicUnits, $freeLearningUnitID, null);
                $data = $unitList[0];
                $sql = $unitList[1];
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

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }
                ?>

				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_browse_details_enrolMultipleProcess.php?freeLearningUnitID='.$_GET['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view" ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">
						<tr>
							<td>
								<b><?php echo __($guid, 'Unit') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php echo __($guid, 'This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<input readonly style='width: 300px' type='text' value='<?php echo $row['name'] ?>' />
							</td>
						</tr>

						<tr>
							<td style='width: 275px'>
								<b><?php echo __($guid, 'Class') ?></b><br/>
							</td>
							<td class="right">
								<?php
                                    $highestAction2 = getHighestGroupedAction($guid, '/modules/Free Learning/units_manage.php', $connection2);
                                ?>
								<select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
									<?php
                                    try {
                                        if ($highestAction2 == 'Manage Units_all') {
                                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                            $sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
                                        } else {
                                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                            $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Teacher' AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class";
                                        }
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
									while ($rowSelect = $resultSelect->fetch()) {
										echo "<option value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).' - '.$rowSelect['name'].'</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style='width: 275px'>
								<b><?php echo __($guid, 'Students In Class', 'Free Learning') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?> </span>
							</td>
							<td class="right">
								<select multiple name="gibbonPersonIDMulti[]" id="gibbonPersonIDMulti" style="width: 302px; height:150px">
									<?php
                                    try {
                                        $dataSelect2 = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                        $sqlSelect2 = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name, gibbonCourseClassID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName";
                                        $resultSelect2 = $connection2->prepare($sqlSelect2);
                                        $resultSelect2->execute($dataSelect2);
                                    } catch (PDOException $e) {
                                    }
									while ($rowSelect2 = $resultSelect2->fetch()) {
										echo "<option class='".$rowSelect2['gibbonCourseClassID']."' value='".$rowSelect2['gibbonPersonID']."'>".htmlPrep($rowSelect2['name']).' - '.formatName('', htmlPrep($rowSelect2['preferredName']), htmlPrep($rowSelect2['surname']), 'Student', true).'</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<script type="text/javascript">
							$("#gibbonPersonIDMulti").chainedTo("#gibbonCourseClassID");
						</script>
						<tr>
							<td>
								<b><?php echo __($guid, 'Status', 'Free Learning') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="status" id="status" style="width: 302px">
									<option value="Exempt"><?php echo __($guid, 'Exempt', 'Free Learning') ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <?php echo __($guid, 'denotes a required field'); ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input type="submit" value="<?php echo __($guid, 'Next') ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php

            }
        }
    }
}
?>
