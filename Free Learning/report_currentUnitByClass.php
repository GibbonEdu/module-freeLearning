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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_currentUnitByClass.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Current Unit By Class').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Class');
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
					<b><?php echo __($guid, 'Sort By') ?></b><br/>
				</td>
				<td class="right">
					<select name="sort" style="width: 300px">
						<option value="unit" <?php if ($sort == 'unit') {
    echo 'selected';
}
    ?>>Unit</option>
						<option value="student" <?php if ($sort == 'student') {
    echo 'selected';
}
    ?>>Student</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_currentUnitByClass.php">
					<input type="submit" value="<?php echo __($guid, 'Submit');
    ?>">
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

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                if ($sort == 'student') {
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnit.freeLearningUnitID, freeLearningUnit.name, timestampJoined, collaborationKey, freeLearningUnitStudent.status FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role='Student') LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Complete - Pending' OR freeLearningUnitStudent.status='Evidence Not Approved')) LEFT JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName, freeLearningUnit.name";
                } else {
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnit.freeLearningUnitID, freeLearningUnit.name, timestampJoined, collaborationKey, freeLearningUnitStudent.status FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role='Student') LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Complete - Pending' OR freeLearningUnitStudent.status='Evidence Not Approved')) LEFT JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID ORDER BY freeLearningUnit.name, collaborationKey, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/report_students_byRollGroup_print.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Number');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Student');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Unit');
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Status').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Date Started');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Days Since Started');
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=&difficulty=&name='>".$row['name'].'</a>';
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