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

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == true or ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false))) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
        $highestAction = 'Browse Units_all';
        $roleCategory = null ;
    } else {
        $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
    }
    if ($highestAction == false) { echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        if (isset($_SESSION[$guid]['username']) == false) {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Browse Units', 'Free Learning').'</div>';
        } else {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > </div><div class='trailEnd'>".__($guid, 'Browse Units', 'Free Learning').'</div>';
        }
        echo '</div>';

        if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
            echo "<div class='linkTop'>";
                echo "<a class='button' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/showcase.php&sidebar=false'>".__($guid, 'View Our Free Learning Showcase', 'Free Learning')."</a>";
            echo '</div>';
        }

        //Get params
        $canManage = false;
        if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
            $canManage = true;
        }
        $showInactive = 'N';
        if ($canManage and isset($_GET['showInactive'])) {
            $showInactive = $_GET['showInactive'];
        }
        $applyAccessControls = 'Y';
        if ($canManage) {
            $applyAccessControls = 'N';
            if (isset($_GET['applyAccessControls'])) {
                $applyAccessControls = $_GET['applyAccessControls'];
            }
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
        $view = null;
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

        //Get data on learning areas, authors and blocks in an efficient manner
        $learningAreaArray = getLearningAreaArray($connection2);
        $authors = getAuthorsArray($connection2);
        $blocks = getBlocksArray($connection2);

        echo '<h3>';
        echo __($guid, 'Filter');
        echo '</h3>';
        echo "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_browse.php'>";
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
				<b><?php echo __($guid, 'Difficulty', 'Free Learning') ?></b><br/>
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
					echo "<option $selected value='".__($guid, $difficultyOption, 'Free Learning')."'>".__($guid, $difficultyOption, 'Free Learning').'</option>';
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
		if ($canManage) {
			?>
			<tr>
				<td>
					<b><?php echo __($guid, 'Show Inactive Units?', 'Free Learning') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select name="showInactive" id="showInactive" style="width: 302px">
						<?php
						$selected = '';
						if ($showInactive == 'N') {
							$selected = 'selected';
						}
						echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
						$selected = '';
						if ($showInactive == 'Y') {
							$selected = 'selected';
						}
						echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Apply Access Controls?', 'Free Learning') ?></b><br/>
                    <span style="font-size: 90%"><i><?php echo __($guid, 'Restricts access to staff units.', 'Free Learning') ?></i></span>
				</td>
				<td class="right">
					<select name="applyAccessControls" id="applyAccessControls" style="width: 302px">
						<?php
                        $selected = '';
						if ($applyAccessControls == 'N') {
							$selected = 'selected';
						}
						echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
                        $selected = '';
						if ($applyAccessControls == 'Y') {
							$selected = 'selected';
						}
						echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
						?>
					</select>
				</td>
			</tr>
            <tr>
                <td style='width: 275px'>
                    <b><?php echo __($guid, 'View As') ?> *</b><br/>
                </td>
                <td class="right">
                    <?php
                    if ($schoolType == 'Physical') {
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

                    } else {
                        ?>
                        <select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
                            <option></option>
                            <?php
                                try {
                                    $dataSelect = array();
                                    $sqlSelect = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname, username FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonRole.gibbonRoleID LIKE concat( '%', gibbonPerson.gibbonRoleIDAll, '%' ) AND category='Student') WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = '';
                            if ($rowSelect['gibbonPersonID'] == $gibbonPersonID) {
                                $selected = 'selected';
                            }
                            echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['username'].')</option>';
                        }
                        ?>
                        </select>
                        <?php
                    }
                    ?>
                </td>
            </tr>
			<?php

		}

        echo '<tr>';
        echo "<td class='right' colspan=2>";
        echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
        echo "<input type='hidden' name='view' value='$view'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_browse.php&view=$view'>".__($guid, 'Clear Filters').'</a> ';
        echo "<input type='submit' value='".__($guid, 'Go')."'>";
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';

        echo "<div class='linkTop' style='margin-top: 40px; margin-bottom: -35px'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=list'>".__($guid, 'List', 'Free Learning')." <img style='margin-bottom: -5px' title='".__($guid, 'List', 'Free Learning')."' src='./modules/Free Learning/img/iconList.png'/></a> ";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=grid'>".__($guid, 'Grid', 'Free Learning')." <img style='margin-bottom: -5px' title='".__($guid, 'Grid', 'Free Learning')."' src='./modules/Free Learning/img/iconGrid.png'/></a> ";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=map'>".__($guid, 'Map', 'Free Learning')." <img style='margin-bottom: -5px' title='".__($guid, 'Map', 'Free Learning')."' src='./modules/Free Learning/img/iconMap.png'/></a> ";
        echo '</div>';

        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        //Search with filters applied
        try {
            $unitList = getUnitList($connection2, $guid, $gibbonPersonID, $roleCategory, $highestAction, $schoolType, $gibbonDepartmentID, $difficulty, $name, $showInactive, $applyAccessControls, $publicUnits, null, $difficulties);
            $data = $unitList[0];
            $sql = $unitList[1];
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);

        echo '<h3>';
        echo __($guid, 'Units')." <span style='font-size: 65%; font-style: italics'> x".$result->rowCount().'</span>';
        echo '</h3>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($view == 'list') {
                if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID");
                }

                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo "<th style='width: 150px!important; text-align: center'>";
                echo __($guid, 'Name').'</br>';
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Status').'</span>';
                echo '</th>';
                echo "<th style='width: 100px!important'>";
                echo __($guid, 'Authors', 'Free Learning').'<br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Learning Areas', 'Free Learning').'</span>';
                echo '</th>';
                echo "<th style='max-width: 325px!important'>";
                echo __($guid, 'Difficulty', 'Free Learning').'</br>';
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Blurb', 'Free Learning').'</span>';
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Length', 'Free Learning').'</br>';
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Minutes').'</span>';
                echo '</th>';
                if ($schoolType == 'Physical') {
                    echo '<th>';
                    echo __($guid, 'Grouping', 'Free Learning').'</br>';
                    echo '</th>';
                }
                echo "<th style='min-width: 150px'>";
                if ($schoolType == 'Online') {
                    echo __($guid, 'Recommended', 'Free Learning').'<br/>';
                }
                echo __($guid, 'Prerequisites', 'Free Learning').'</br>';
                echo '</th>';
                if (isset($_SESSION[$guid]['username'])) { //Likes only if logged in!
                            echo "<th style='min-width: 50px'>";
                    echo __($guid, 'Like');
                    echo '</th>';
                }
                echo "<th style='min-width: 50px'>";
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                try {
                    $resultPage = $connection2->prepare($sqlPage);
                    $resultPage->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($row = $resultPage->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    if ($row['status'] == 'Complete - Approved' or $row['status'] == 'Exempt') {
                        $rowNum = 'current';
                    } elseif ($row['status'] == 'Current' or $row['status'] == 'Evidence Not Approved' or $row['status'] == 'Complete - Pending') {
                        $rowNum = 'warning';
                    }
                    ++$count;

					//COLOR ROW BY STATUS!
					echo "<tr class=$rowNum>";
                    echo "<td style='text-align: center; font-size: 125%'>";
                    echo "<div style='font-weight: bold; margin-top: 5px; margin-bottom: -6px ;'>".$row['name'].'</div><br/>';
                    if ($row['logo'] == null) {
                        echo "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_125.jpg'/><br/>";
                    } else {
                        echo "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$row['logo']."'/><br/>";
                    }
                    echo "<span style='font-size: 85%;'>";
                    echo $row['status'];
                    echo '</span>';
                    echo '</td>';
                    echo '<td>';
                    $amAuthor = false;
                    foreach ($authors as $author) {
                        if ($author[0] == $row['freeLearningUnitID']) {
                            if ($author[3] == '') {
                                echo $author[1].'<br/>';
                            } else {
                                echo "<a target='_blank' href='".$author[3]."'>".$author[1].'</a><br/>';
                            }
                            if (isset($_SESSION[$guid]['username'])) { //Am author chekc only if logged in!
								if ($author[2] == $_SESSION[$guid]['gibbonPersonID']) { // Check to see if I am one of the authors
									$amAuthor = true;
								}
                            }
                        }
                    }
                    if ($row['gibbonDepartmentIDList'] != '') {
                        echo "<span style='font-size: 85%;'>";
                        $departments = explode(',', $row['gibbonDepartmentIDList']);
                        foreach ($departments as $department) {
                            if (isset($learningAreaArray[$department])) {
                                echo $learningAreaArray[$department].'<br/>';
                            }
                        }
                        echo '</span>';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo '<b>'.__($guid, $row['difficulty'], 'Free Learning').'</b><br/>';
                    echo "<div style='font-size: 100%; text-align: justify'>";
                    echo $row['blurb'];
                    echo '</div>';
                    echo '</td>';
                    echo '<td>';
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
                    echo '</td>';
                    if ($schoolType == 'Physical') {
                        echo '<td>';
                        if ($row['grouping'] != '') {
                            $groupings = explode(',', $row['grouping']);
                            foreach ($groupings as $grouping) {
                                echo ucwords($grouping).'<br/>';
                            }
                        }
                        echo '</td>';
                    }
                    echo '<td>';
                    $prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
                    if ($highestAction == 'Browse Units_prerequisites') {
                        if ($prerequisitesActive != false) {
                            $prerequisitesMet = prerequisitesMet($connection2, $_SESSION[$guid]['gibbonPersonID'], $prerequisitesActive);
                            if ($prerequisitesMet) {
                                echo "<span style='font-weight: bold; color: #00cc00'>".__($guid, 'OK!', 'Free Learning').'<br/></span>';
                            } else {
                                if ($schoolType == 'Online') {
                                    echo "<span style='font-weight: bold; color: #D65602'>".__($guid, 'Consider Studying', 'Free Learning').'<br/></span>';
                                } else {
                                    echo "<span style='font-weight: bold; color: #cc0000'>".__($guid, 'Not Met', 'Free Learning').'<br/></span>';
                                }
                            }
                        }
                    }
                    if ($prerequisitesActive != false) {
                        $prerequisites = explode(',', $prerequisitesActive);
                        $units = getUnitsArray($connection2);
                        foreach ($prerequisites as $prerequisite) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=list&freeLearningUnitID=".$prerequisite."'>".$units[$prerequisite][0]."<a/><br/>";
                        }
                    } else {
                        echo '<i>'.__($guid, 'None', 'Free Learning').'<br/></i>';
                    }
                    echo '</td>';
                    if (isset($_SESSION[$guid]['username'])) { //Likes only if logged in!
						echo '<td>';
							//DEAL WITH LIKES
							if ($amAuthor) { //I am one of the authors, so cannot like
								echo countLikesByContextAndRecipient($connection2, 'Free Learning', 'freeLearningUnitID', $row['freeLearningUnitID'], $_SESSION[$guid]['gibbonPersonID']);
							} else { //I am not one of the authors, and so can like
								echo "<div id='star".$row['freeLearningUnitID']."'>";
								$likesGiven = countLikesByContextAndGiver($connection2, 'Free Learning', 'freeLearningUnitID', $row['freeLearningUnitID'], $_SESSION[$guid]['gibbonPersonID']);
								$comment = addSlashes($row['name']);
								$authorList = '';
								foreach ($authors as $author) {
									if ($author[0] == $row['freeLearningUnitID']) {
										$authorList .= $author[2].',';
									}
								}
								if ($authorList != '') {
									$authorList = substr($authorList, 0, -1);
								}
								echo '<script type="text/javascript">';
								echo '$(document).ready(function(){';
								echo '$("#starAdd'.$row['freeLearningUnitID'].'").click(function(){';
								echo '$("#star'.$row['freeLearningUnitID'].'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Free%20Learning/units_browse_starAjax.php",{"freeLearningUnitID": "'.$row['freeLearningUnitID'].'", "mode": "add", "comment": "'.$comment.'", "authorList": "'.$authorList.'"});';
								echo '});';
								echo '$("#starRemove'.$row['freeLearningUnitID'].'").click(function(){';
								echo '$("#star'.$row['freeLearningUnitID'].'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Free%20Learning/units_browse_starAjax.php",{"freeLearningUnitID": "'.$row['freeLearningUnitID'].'", "mode": "remove", "comment": "'.$comment.'", "authorList": "'.$authorList.'"});';
								echo '});';
								echo '});';
								echo '</script>';
								if ($likesGiven < 1) {
									echo "<a id='starAdd".$row['freeLearningUnitID']."' onclick='return false;' href='#'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";
								} else {
									echo "<a id='starRemove".$row['freeLearningUnitID']."' onclick='return false;' href='#'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
								}
							}
                        echo '</div>';
                        echo '</td>';
                    }
                    echo '<td>';
                    if ($highestAction == 'Browse Units_all' or $schoolType == 'Online') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'><img style='padding-left: 5px' title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                    } elseif ($highestAction == 'Browse Units_prerequisites') {
                        if ($row['freeLearningUnitIDPrerequisiteList'] == null or $row['freeLearningUnitIDPrerequisiteList'] == '') {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'><img style='padding-left: 5px' title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                        } else {
                            if ($prerequisitesMet) {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'><img style='padding-left: 5px' title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                            }
                        }
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID");
                }
            } elseif ($view == 'grid') {
                echo "<table cellspacing='0' style='width: 100%'>";
                $count = 0;
                $columns = 4;

                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    //Row header if needed
					if ($count % $columns == 0) {
						echo "<tr class='odd'>";
					}

					//Cell style
					$cellClass = '';
                    if ($row['status'] == 'Complete - Approved' or $row['status'] == 'Exempt') {
                        $cellClass = 'current';
                    } elseif ($row['status'] == 'Current' or $row['status'] == 'Complete - Pending') {
                        $cellClass = 'warning';
                    }
                    echo "<td class='$cellClass' style='vertical-align: top ; text-align: center; font-size: 125%; width: ".(100 / $columns)."%'>";
                    echo "<div style='height: 40px; font-weight: bold; margin-top: 5px; margin-bottom: -6px ;'>".$row['name'].'</div><br/>';
                    $title = 'Difficulty: '.$row['difficulty'].'.';
                    $title .= ' '.$row['blurb'];
                    if ($row['logo'] == null) {
                        echo "<img title='".htmlPrep($title)."' style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_125.jpg'/><br/>";
                    } else {
                        echo "<img title='".htmlPrep($title)."' style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$row['logo']."'/><br/>";
                    }

					//Actions
					$prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
                    if ($prerequisitesActive != false) {
                        $prerequisites = explode(',', $prerequisitesActive);
                        $units = getUnitsArray($connection2);
                    }
                    if ($highestAction == 'Browse Units_prerequisites') {
                        if ($prerequisitesActive != false) {
                            $prerequisitesMet = prerequisitesMet($connection2, $_SESSION[$guid]['gibbonPersonID'], $prerequisitesActive);
                        }
                    }
                    if ($highestAction == 'Browse Units_all' or $schoolType == 'Online') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                    } elseif ($highestAction == 'Browse Units_prerequisites') {
                        if ($row['freeLearningUnitIDPrerequisiteList'] == null or $row['freeLearningUnitIDPrerequisiteList'] == '') {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                        } else {
                            if ($prerequisitesMet) {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_browse_details.php&sidebar=true&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                            }
                        }
                    }
                    echo '</td>';

                    if ($count % $columns == ($columns - 1)) {
                        echo '</tr>';
                    }
                    ++$count;
                }

                for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                    echo '<td></td>';
                }

                if ($count % $columns != 0) {
                    echo '</tr>';
                }
                echo '</table>';
            } elseif ($view == 'map') {
                echo '<p>';
                echo __($guid, 'The map below shows all units selected by the filters above. Lines between units represent prerequisites. Units without prerequisites, which make good starting units, are highlighted by a blue border.', 'Free Learning');
                echo '</p>'; ?>
				<script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/vis/dist/vis.js"></script>
				<link href="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/vis/dist/vis.css" rel="stylesheet" type="text/css" />

				<style type="text/css">
					div#map {
						width: 100%;
						height: 800px;
						border: 1px solid #000;
						background-color: #ddd;
						margin-bottom: 20px ;
					}
				</style>

				<div id="map"></div>

				<?php
                //PREP NODE AND EDGE ARRAYS DATA
                $nodeArray = array();
                $edgeArray = array();
                $nodeList = '';
                $edgeList = '';
                $idList = '';
                $countNodes = 0;
                while ($row = $result->fetch()) {
                    if ($result->rowCount() <= 125) {
                        if ($row['logo'] != '') {
                            $image = $row['logo'];
                        } else {
                            $image = $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/anonymous_240_square.jpg';
                        }
                    }
                    else {
                        $image = 'undefined';
                    }
                    $titleTemp = $string = trim(preg_replace('/\s\s+/', ' ', $row['blurb']));
                    $title = addSlashes($row['name']);
                    if (strlen($row['blurb']) > 90) {
                        $title .= ': '.addSlashes(substr($titleTemp, 0, 90)).'...';
                    } else {
                        $title .= ': '.addSlashes($titleTemp);
                    }

                    if ($row['status'] == 'Complete - Approved' or $row['status'] == 'Exempt') {
                        $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'#390', background:'#D4F6DC'}, borderWidth: 2},";
                    } elseif ($row['status'] == 'Current' or $row['status'] == 'Evidence Not Approved' or $row['status'] == 'Complete - Pending') {
                        $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'#D65602', background:'#FFD2A9'}, borderWidth: 2},";
                    }
                    else {
                        if ($row['freeLearningUnitIDPrerequisiteList'] == '') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: '$image', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'blue'}, borderWidth: 7},";
                        } else {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: '$image', label: '".addSlashes($row['name'])."', title: '".$title."', borderWidth: 2},";
                        }
                    }

                    $nodeArray[$row['freeLearningUnitID']][0] = $countNodes;
                    $nodeArray[$row['freeLearningUnitID']][1] = $row['freeLearningUnitID'];
                    $nodeArray[$row['freeLearningUnitID']][2] = $row['freeLearningUnitIDPrerequisiteList'];
                    $idList .= "'".$row['freeLearningUnitID']."',";
                    ++$countNodes;
                }
                if ($nodeList != '') {
                    $nodeList = substr($nodeList, 0, -1);
                }
                if ($idList != '') {
                    $idList = substr($idList, 0, -1);
                }

                foreach ($nodeArray as $node) {
                    if (isset($node[2])) {
                        $edgeExplode = explode(',', $node[2]);
                        foreach ($edgeExplode as $edge) {
                            if (isset($nodeArray[$edge][0])===true) {
                                if (is_numeric($nodeArray[$edge][0])) {
                                    $edgeList .= '{from: '.$nodeArray[$node[1]][0].', to: '.$nodeArray[$edge][0].", arrows:'from'},";
                                }
                            }
                        }
                    }
                }
                if ($edgeList != '') {
                    $edgeList = substr($edgeList, 0, -1);
                }

                ?>
				<script type="text/javascript">
					//CREATE NODE ARRAY
					var nodes = new vis.DataSet([<?php echo $nodeList; ?>]);

					//CREATE EDGE ARRAY
					var edges = new vis.DataSet([<?php echo $edgeList ?>]);

					//CREATE NODE TO freeLearningUnitID ARRAY
					var ids = new Array(<?php echo $idList ?>);

					//CREATE NETWORK
					var container = document.getElementById('map');
					var data = {
					nodes: nodes,
					edges: edges
					};
					var options = {
						nodes: {
							borderWidth:4,
							size:30,
							color: {
								border: '#222222',
								background: '#999999'
							},
							font:{color:'#333'},
            				shadow:true
						},
						edges: {
							color: '#333',
            				shadow:true
						},
					  	interaction:{
							navigationButtons: true,
    						zoomView: false
						},
						layout: {
							randomSeed: 0.5,
							improvedLayout:true
						}
					};
					var network = new vis.Network(container, data, options);

					//CLICK LISTENER
					network.on( 'click', function(properties) {
						var nodeNo = properties.nodes ;
						window.location = '<?php echo $_SESSION[$guid]['absoluteURL'] ?>/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID=' + ids[nodeNo] + '&gibbonDepartmentID=<?php echo $gibbonDepartmentID ?>&difficulty=<?php echo $difficulty ?>&name=<?php echo $name ?>&view=<?php echo $view ?>';
					});
				</script>
				<?php

            }
        }
    }
}
?>
