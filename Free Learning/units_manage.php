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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) { echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Units').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $gibbonDepartmentID = null;
        if (isset($_GET['gibbonDepartmentID'])) {
            $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
        }
        $difficulty = null;
        if (isset($_GET['difficulty'])) {
            $difficulty = $_GET['difficulty'];
        }
        $name = null;
        if (isset($_GET['name'])) {
            $name = $_GET['name'];
        }

        echo '<h3>';
        echo __($guid, 'Filter');
        echo '</h3>';
        echo "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage.php'>";
        echo "<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
        ?>
		<tr>
			<td>
				<b><?php echo __($guid, 'Learning Area') ?></b><br/>
				<span style="font-size: 90%"><i></i></span>
			</td>
			<td class="right">
				<select name="gibbonDepartmentID" id="gibbonDepartmentID" style="width: 302px">
					<option value=""></option>
					<?php
					$learningAreas = getLearningAreas($connection2, $guid);
					for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
						if ($gibbonDepartmentID == $learningAreas[$i]) {
							echo "<option selected value='".$learningAreas[$i]."'>".__($guid, $learningAreas[($i + 1)]).'</option>';
						} else {
							echo "<option value='".$learningAreas[$i]."'>".__($guid, $learningAreas[($i + 1)]).'</option>';
						}
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<b><?php echo __($guid, 'Difficulty') ?></b><br/>
				<span style="font-size: 90%"><i></i></span>
			</td>
			<td class="right">
				<?php
				$difficulties = getSettingByScope($connection2, 'Free Learning', 'difficultyOptions');
				echo "<select name='difficulty' id='difficulty' style='width: 302px'>";
				echo "<option value=''></option>";
				$difficultiesList = explode(',', $difficulties);
				foreach ($difficultiesList as $difficultyOption) {
					$selected = '';
					if ($difficulty == $difficultyOption) {
						$selected = 'selected';
					}
					echo "<option $selected value='".$difficultyOption."'>".$difficultyOption.'</option>';
				}
				echo '</select>';
				?>
			</td>
		</tr>
		<tr>
			<td>
				<b><?php echo __($guid, 'Name') ?></b><br/>
				<span style="font-size: 90%"><i></i></span>
			</td>
			<td class="right">
				<?php
				echo "<input name='name' value='".$name."' type='text' style='width: 300px'/>";
       			?>
			</td>
		</tr>
		<?php

		echo '<tr>';
        echo "<td class='right' colspan=2>";
        echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage.php'>".__($guid, 'Clear Filters').'</a> ';
        echo "<input type='submit' value='".__($guid, 'Go')."'>";
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';

        //Fetch units
        $difficulties = getSettingByScope($connection2, 'Free Learning', 'difficultyOptions');
        $difficultyOrder = '';
        if ($difficulties != false) {
            $difficultyOrder = 'FIELD(difficulty';
            $difficulties = explode(',', $difficulties);
            foreach ($difficulties as $difficultyOption) {
                $difficultyOrder .= ",'".$difficultyOption."'";
            }
            $difficultyOrder .= '), ';
        }
        try {
            $data = array();
            $sqlWhere = 'AND ';
            if ($gibbonDepartmentID != '') {
                $data['gibbonDepartmentID'] = $gibbonDepartmentID;
                $sqlWhere .= "gibbonDepartmentIDList LIKE concat('%', :gibbonDepartmentID, '%') AND ";
            }
            if ($difficulty != '') {
                $data['difficulty'] = $difficulty;
                $sqlWhere .= 'difficulty=:difficulty AND ';
            }
            if ($name != '') {
                $data['name'] = $name;
                $sqlWhere .= "freeLearningUnit.name LIKE concat('%', :name, '%') AND ";
            }
            if ($sqlWhere == 'AND ') {
                $sqlWhere = '';
            } else {
                $sqlWhere = substr($sqlWhere, 0, -5);
            }
            if ($highestAction == 'Manage Units_all') {
                $sql = "SELECT * FROM freeLearningUnit WHERE true $sqlWhere ORDER BY $difficultyOrder name";
            } elseif ($highestAction == 'Manage Units_learningAreas') {
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $sql = "SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')  $sqlWhere ORDER BY $difficultyOrder name";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        $learningAreas = getLearningAreaArray($connection2);

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_manage_add.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Name');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Difficulty');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Learning Areas');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Active');
            echo '</th>';
            echo "<th style='width: 100px'>";
            echo __($guid, 'Actions');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }

                if ($row['active'] == 'N') {
                    $rowNum = 'error';
                }

				//COLOR ROW BY STATUS!
				echo "<tr class=$rowNum>";
                echo '<td>';
                echo $row['name'];
                echo '</td>';
                echo '<td>';
                echo $row['difficulty'];
                echo '</td>';
                echo "<td style='max-width: 270px'>";
                if (is_null($row['gibbonDepartmentIDList']) == false) {
                    $departments = explode(',', $row['gibbonDepartmentIDList']);
                    foreach ($departments as $department) {
                        if (isset($learningAreas[$department])) {
                            echo $learningAreas[$department].'<br/>';
                        }
                    }
                } else {
                    echo '<i>'.__($guid, 'None').'</i>';
                }
                echo '</td>';
                echo '<td>';
                echo ynExpander($guid, $row['active']);
                echo '</td>';
                echo '<td>';
                if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php')) {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                }
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_manage_edit.php&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_manage_delete.php&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                echo '</td>';
                echo '</tr>';

                ++$count;
            }
            echo '</table>';
        }
    }
}
?>
