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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_currentUnitByClass.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > </div><div class='trailEnd'>".__($guid, 'Current Unit By Class', 'Free Learning').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Class', 'Free Learning');
    echo '</h2>';

    $gibbonCourseClassID = null;
    if (isset($_GET['gibbonCourseClassID'])) {
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    }
    $sort = null;
    if (isset($_GET['sort'])) {
        $sort = $_GET['sort'];
    }

    ?>

	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">
			<tr>
				<td style='width: 275px'>
					<b><?php echo __($guid, 'Class') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonCourseClassID">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
							$sqlSelect = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort as class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							if ($gibbonCourseClassID == $rowSelect['gibbonCourseClassID']) {
								echo "<option selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
							} else {
								echo "<option value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Sort By', 'Free Learning') ?></b><br/>
				</td>
				<td class="right">
					<select name="sort" style="width: 300px">
						<option value="unit" <?php if ($sort == 'unit') { echo 'selected'; } ?>>Unit</option>
						<option value="student" <?php if ($sort == 'student') { echo 'selected'; } ?>>Student</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_currentUnitByClass.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($gibbonCourseClassID != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort as class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            echo "<p style='margin-bottom: 0px'><b>".__($guid, 'Class').'</b>: '.$row['course'].'.'.$row['class'].'</p>';

            //Get data on blocks in an efficient manner
            $blocks = getBlocksArray($connection2);

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                if ($sort == 'student') {
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnit.freeLearningUnitID, freeLearningUnit.name AS unitName, timestampJoined, collaborationKey, freeLearningUnitStudent.status, fields FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role='Student') LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Current - Pending' OR freeLearningUnitStudent.status='Complete - Pending' OR freeLearningUnitStudent.status='Evidence Not Yet Approved')) LEFT JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName, unitName";
                } else {
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnit.freeLearningUnitID, freeLearningUnit.name AS unitName, timestampJoined, collaborationKey, freeLearningUnitStudent.status, fields FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role='Student') LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Current - Pending' OR freeLearningUnitStudent.status='Complete - Pending' OR freeLearningUnitStudent.status='Evidence Not Yet Approved')) LEFT JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID ORDER BY unitName, collaborationKey, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/report_students_byRollGroup_print.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            //Check for custom field
            $customField = getSettingByScope($connection2, 'Free Learning', 'customField');

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Number');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Student');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Group', 'Free Learning');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Unit').'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Status').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Date Started', 'Free Learning');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Days Since Started', 'Free Learning');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Length', 'Free Learning').'</br>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Minutes').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Time Spent', 'Free Learning').'</br>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Minutes By Day\'s End', 'Free Learning').'</span>';
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            $group = 0;
            $collaborationKeys = array();
            while ($row = $result->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

				//COLOR ROW BY STATUS!
				echo "<tr class=$rowNum>";
                echo '<td>';
                echo $count;
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a><br/>';
                $fields = unserialize($row['fields']);
                if (!empty($fields[$customField])) {
                    $value = $fields[$customField];
                    if ($value != '') {
                        echo "<span style='font-size: 85%; font-style: italic'>".$value.'</span>';
                    }
                }
                echo '</td>';
                echo '<td>';
                if ($row['collaborationKey'] != '') {
                    if (isset($collaborationKeys[$row['collaborationKey']]) == false) {
                        ++$group;
                        $collaborationKeys[$row['collaborationKey']] = $group;
                    }
                    echo $collaborationKeys[$row['collaborationKey']];
                }
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=&difficulty=&name='>".htmlPrep($row['unitName']).'</a>';
                echo "<br/><span style='font-size: 85%; font-style: italic'>".$row['status'].'</span>';
                echo '</td>';
                echo '<td>';
                if ($row['timestampJoined'] != '') {
                    echo dateConvertBack($guid, substr($row['timestampJoined'], 0, 10));
                }
                echo '</td>';
                echo '<td>';
                if ($row['timestampJoined'] != '') {
                    echo round((time() - strtotime($row['timestampJoined'])) / (60 * 60 * 24));
                }
                echo '</td>';
                echo '<td>';
                    if ($row['timestampJoined'] != '') {
                        $timing = null;
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
                            echo '<i>'.__($guid, 'N/A').'</i>';
                        } else {
                            echo $timing;
                        }
                    }
                echo '</td>';
                echo '<td>';
                    $spent = 0;
                    if ($row['timestampJoined'] != '') {
                        try {
                            $dataLessons = array('gibbonCourseClassID' => $gibbonCourseClassID, "dateJoined" => substr($row['timestampJoined'], 0, 10), "today" => date('Y-m-d'));
                            $sqlLessons = "SELECT date, timeStart, timeEnd FROM gibbonPlannerEntry WHERE name LIKE '%Free Learning%' AND gibbonCourseClassID=:gibbonCourseClassID AND date>=:dateJoined AND date<=:today";
                            $resultLessons = $connection2->prepare($sqlLessons);
                            $resultLessons->execute($dataLessons);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultLessons->rowCount() < 1) {
                            echo $spent;
                        }
                        else {
                            while ($rowLessons = $resultLessons->fetch()) {
                                $start_date = new DateTime($rowLessons['date'].' '.$rowLessons['timeStart']);
                                $since_start = $start_date->diff(new DateTime($rowLessons['date'].' '.$rowLessons['timeEnd']));
                                $spent += (60*$since_start->h) + $since_start->i;
                            }

                            if (is_null($timing)) { //No length to compare to, so just spit out answer
                                echo $spent;
                            }
                            else if ($spent<=$timing) { //OK for time, spit out in green
                                echo "<span style='font-weight: bold; color: #390;'>$spent</span>";
                            }
                            else { //Over time, spit out in orange
                                echo "<span style='font-weight: bold; color: #D65602;'>$spent</span>";
                            }

                        }
                    }
                echo '</td>';
                echo '</tr>';
            }
            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=3>';
                echo __($guid, 'There are no records to display.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>
