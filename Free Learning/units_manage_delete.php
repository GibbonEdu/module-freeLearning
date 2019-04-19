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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_delete.php') == false) {
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
        $freeLearningUnitID = $_GET['freeLearningUnitID'];
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


        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if unit specified
        if ($freeLearningUnitID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Units_all') {
                    $data = array('freeLearningUnitID' => $freeLearningUnitID);
                    $sql = 'SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
                } elseif ($highestAction == 'Manage Units_learningAreas') {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'freeLearningUnitID' => $freeLearningUnitID);
                    $sql = "SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND freeLearningUnitID=:freeLearningUnitID ORDER BY difficulty, name";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The specified record cannot be found.');
                echo '</div>';
            } else {
                //Let's go!
                $row = $result->Fetch();
                if ($gibbonDepartmentID != '' or $difficulty != '' or $name != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }

                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_manage_deleteProcess.php?freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view" ?>">
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
?>
