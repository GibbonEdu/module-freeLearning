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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_edit.php') == false) {
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
        $canManage = false;
        if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and getHighestGroupedAction($guid, '/modules/Free Learning/units_browse.php', $connection2) == 'Browse Units_all') {
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

        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_manage.php'>".__($guid, 'Manage Units')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Unit', 'Free Learning').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        if (isset($_GET['addReturn'])) {
            $addReturn = $_GET['addReturn'];
        } else {
            $addReturn = '';
        }
        $addReturnMessage = '';
        $class = 'error';
        if (!($addReturn == '')) {
            if ($addReturn == 'success0') {
                $addReturnMessage = __($guid, 'Your Smart Unit was successfully created: you can now edit it using the form below.', 'Free Learning');
                $class = 'success';
            }
            echo "<div class='$class'>";
            echo $addReturnMessage;
            echo '</div>';
        }

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
            $row = $result->fetch();
            if ($gibbonDepartmentID != '' or $difficulty != '' or $name != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php')) {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'>".__($guid, 'View')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                echo '</div>';
            }

            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_manage_editProcess.php?freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&address=".$_GET['q'] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Unit Basics', 'Free Learning') ?></h3>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=40 value="<?php echo htmlPrep($row['name']); ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<?php
                    $difficulties = getSettingByScope($connection2, 'Free Learning', 'difficultyOptions');
					if ($difficulties != false) {
						$difficulties = explode(',', $difficulties);
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Difficulty', 'Free Learning') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php echo __($guid, 'How hard is this unit?', 'Free Learning') ?></i></span>
							</td>
							<td class="right">
								<select name="difficulty" id="difficulty" style="width: 302px">
									<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
									<?php
                                    for ($i = 0; $i < count($difficulties); ++$i) {
                                        $selected = '';
                                        if ($row['difficulty'] == trim($difficulties[$i])) {
                                            $selected = 'selected';
                                        }
                                        ?>
										<option <?php echo $selected;
                                        ?> value="<?php echo __($guid, trim($difficulties[$i]), 'Free Learning') ?>"><?php echo __($guid, trim($difficulties[$i]), 'Free Learning') ?></option>
									<?php

                                    }
                				?>
								</select>
								<script type="text/javascript">
									var difficulty=new LiveValidation('difficulty');
									difficulty.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								 </script>
							</td>
						</tr>
						<?php

					}
					?>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Blurb', 'Free Learning') ?> *</b>
							<textarea name='blurb' id='blurb' rows=5 style='width: 300px'><?php echo htmlPrep($row['blurb']) ?></textarea>
							<script type="text/javascript">
								var blurb=new LiveValidation('blurb');
								blurb.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Learning Areas') ?></b><br/>
						</td>
						<td class="right">
							<?php
                            $learningAreaRestriction = getSettingByScope($connection2, 'Free Learning', 'learningAreaRestriction');
							if ($highestAction == 'Manage Units_all' or $learningAreaRestriction == 'N') {
								$learningAreas = getLearningAreas($connection2, $guid);
							} elseif ($highestAction == 'Manage Units_learningAreas') {
								$learningAreas = getLearningAreas($connection2, $guid, true);
							}
							if ($learningAreas == '') {
								echo '<i>'.__($guid, 'No Learning Areas available.').'</i>';
							} else {
								for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
									$checked = '';
									if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
										$checked = 'checked ';
									}
									echo __($guid, $learningAreas[($i + 1)])." <input $checked type='checkbox' name='gibbonDepartmentIDCheck".($i) / 2 ."'><br/>";
									echo "<input type='hidden' name='gibbonDepartmentID".($i) / 2 ."' value='".$learningAreas[$i]."'>";
								}
							}
							?>
							<input type="hidden" name="count" value="<?php echo(count($learningAreas)) / 2 ?>">
						</td>
					</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Course') ?></b><br/>
						<span style="font-size: 90%"><i><?php echo __($guid, 'Add this unit into an ad hoc course?', 'Free Learning') ?></i></span>
					</td>
					<td class="right">
						<input name="course" id="course" maxlength=50 value="<?php echo $row['course'] ?>" type="text" style="width: 300px">
						<script type="text/javascript">
						$(function() {
							var availableTags=[
								<?php
                                try {
                                    $dataAuto = array();
                                    $sqlAuto = 'SELECT DISTINCT course FROM freeLearningUnit WHERE active=\'Y\'  ORDER BY course';
                                    $resultAuto = $connection2->prepare($sqlAuto);
                                    $resultAuto->execute($dataAuto);
                                } catch (PDOException $e) {
                                }
								while ($rowAuto = $resultAuto->fetch()) {
									echo '"'.$rowAuto['course'].'", ';
								}
								?>
							];
							$( "#course" ).autocomplete({source: availableTags});
						});
					</script>
					</td>
				</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'License', 'Free Learning') ?></b><br/>
							<span style="font-size: 90%"><i><?php echo __($guid, 'Under what conditions can this work be reused?', 'Free Learning'); ?></i></span>
						</td>
						<td class="right">
							<select name="license" id="license" style="width: 302px">
								<option <?php if ($row['license'] == '') { echo 'selected'; } ?> value=""></option>
								<option <?php if ($row['license'] == 'Copyright') { echo 'selected'; } ?> value="Copyright"><?php echo __($guid, 'Copyright', 'Free Learning') ?></option>
								<option <?php if ($row['license'] == 'Creative Commons BY') { echo 'selected'; } ?> value="Creative Commons BY"><?php echo __($guid, 'Creative Commons BY', 'Free Learning') ?></option>
								<option <?php if ($row['license'] == 'Creative Commons BY-SA') { echo 'selected'; } ?> value="Creative Commons BY-SA"><?php echo __($guid, 'Creative Commons BY-SA', 'Free Learning') ?></option>
								<option <?php if ($row['license'] == 'Creative Commons BY-SA-NC') { echo 'selected'; } ?> value="Creative Commons BY-SA-NC"><?php echo __($guid, 'Creative Commons BY-SA-NC', 'Free Learning') ?></option>
								<option <?php if ($row['license'] == 'Public Domain') { echo 'selected'; } ?> value="Public Domain"><?php echo __($guid, 'Public Domain', 'Free Learning') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Logo', 'Free Learning') ?></b><br/>
							<span style="font-size: 90%"><i><?php echo __($guid, "125x125px jpg/png/gif") ?></i><br/></span>
							<?php if ($row['logo'] != '') { ?>
							<span style="font-size: 90%"><i><?php echo __($guid, 'Will overwrite existing attachment.') ?></i></span>
							<?php
							}
            				?>
						</td>
						<td class="right">
							<?php
                            $logoText = $row['logo'];
                            if (strlen($logoText) > 50) {
                                $logoText = substr($logoText, 0, 50)."...";
                            }
                            if ($row['logo'] != '') {
                                echo __($guid, 'Current attachment:')." <a target='_blank' href='".$row['logo']."'>".$logoText.'</a><br/><br/>';
                            }
                            ?>
							<input type="file" name="file" id="file"><br/><br/>
							<?php
                            echo getMaxUpload($guid);
							$ext = "'.png','.jpeg','.jpg','.gif'";
							?>
							<script type="text/javascript">
								var file=new LiveValidation('file');
								file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "<?php echo __($guid, 'Illegal file type!') ?>", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Major Edit', 'Free Learning') ?> * </b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'If checked, you will be added as an author.', 'Free Learning'); ?></i></span>
                        </td>
                        <td class="right">
                            <input type="checkbox" name="majorEdit" value="Y" /> <?php echo __($guid, 'Yes') ?>
                        </td>
                    </tr>


                    <tr class='break'>
                        <td colspan=2>
                            <h3><?php echo __($guid, 'Access', 'Free Learning') ?></h3>
                            <p><?php echo __($guid, 'Users with permission to manage units can override avaiability preferences.', 'Free Learning'); ?></p>
                        </td>
                    </tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Active') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
                            <input <?php if ($row['active'] == 'Y') { echo 'checked'; } ?> type="radio" name="active" value="Y" /> <?php echo __($guid, 'Yes') ?>
                            <input <?php if ($row['active'] == 'N') { echo 'checked'; } ?> type="radio" name="active" value="N" /> <?php echo __($guid, 'No') ?>
						</td>
					</tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Available To Students', 'Free Learning') ?> * </b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'Should students be able to browse and enrol?', 'Free Learning'); ?></i></span>
                        </td>
                        <td class="right">
                            <input <?php if ($row['availableStudents'] == 'Y') { echo 'checked'; } ?> type="radio" name="availableStudents" value="Y" /> <?php echo __($guid, 'Yes') ?>
                            <input <?php if ($row['availableStudents'] == 'N') { echo 'checked'; } ?> type="radio" name="availableStudents" value="N" /> <?php echo __($guid, 'No') ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Available To Staff', 'Free Learning') ?> * </b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'Should staff be able to browse and enrol?', 'Free Learning'); ?></i></span>
                        </td>
                        <td class="right">
                            <input <?php if ($row['availableStaff'] == 'Y') { echo 'checked'; } ?> type="radio" name="availableStaff" value="Y" /> <?php echo __($guid, 'Yes') ?>
                            <input <?php if ($row['availableStaff'] == 'N') { echo 'checked'; } ?> type="radio" name="availableStaff" value="N" /> <?php echo __($guid, 'No') ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Available To Parents', 'Free Learning') ?> * </b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'Should parents be able to browse and enrol?', 'Free Learning'); ?></i></span>
                        </td>
                        <td class="right">
                            <input <?php if ($row['availableParents'] == 'Y') { echo 'checked'; } ?> type="radio" name="availableParents" value="Y" /> <?php echo __($guid, 'Yes') ?>
                            <input <?php if ($row['availableParents'] == 'N') { echo 'checked'; } ?> type="radio" name="availableParents" value="N" /> <?php echo __($guid, 'No') ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Available To Others', 'Free Learning') ?> * </b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'Should other users be able to browse and enrol?', 'Free Learning'); ?></i></span>
                        </td>
                        <td class="right">
                            <input <?php if ($row['availableOther'] == 'Y') { echo 'checked'; } ?> type="radio" name="availableOther" value="Y" /> <?php echo __($guid, 'Yes') ?>
                            <input <?php if ($row['availableOther'] == 'N') { echo 'checked'; } ?> type="radio" name="availableOther" value="N" /> <?php echo __($guid, 'No') ?>
                        </td>
                    </tr>
                    <?php
                    $makeUnitsPublic = getSettingByScope($connection2, 'Free Learning', 'publicUnits');
					if ($makeUnitsPublic == 'Y') {
						?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Shared Publically', 'Free Learning') ?> * </b><br/>
								<span style="font-size: 90%"><i><?php echo __($guid, 'Share this unit via the public listing of units? Useful for building MOOCS.', 'Free Learning'); ?></i></span>
							</td>
							<td class="right">
								<input <?php if ($row['sharedPublic'] == 'Y') { echo 'checked'; } ?> type="radio" name="sharedPublic" value="Y" /> <?php echo __($guid, 'Yes') ?>
								<input <?php if ($row['sharedPublic'] == 'N') { echo 'checked'; } ?> type="radio" name="sharedPublic" value="N" /> <?php echo __($guid, 'No') ?>
							</td>
						</tr>
						<?php
					}
					?>

					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Constraints', 'Free Learning') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Prerequisite Units', 'Free Learning') ?></b><br/>
							<span style="font-size: 90%"><i><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="right">
							<select name="prerequisites[]" id="prerequisites[]" multiple style="width: 302px; height: 150px">
								<?php
                                try {
                                    $dataSelect = array('freeLearningUnitID' => $freeLearningUnitID);
                                    $sqlSelect = 'SELECT * FROM freeLearningUnit WHERE NOT freeLearningUnitID=:freeLearningUnitID ORDER BY name';
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if (is_numeric(strpos($row['freeLearningUnitIDPrerequisiteList'], str_pad($rowSelect['freeLearningUnitID'], 10, '0', STR_PAD_LEFT)))) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['freeLearningUnitID']."'>".$rowSelect['name'].' ('.$rowSelect['difficulty'].')';
									if ($rowSelect['active'] == 'N') {
										echo ' - '.__($guid, 'Inactive', 'Free Learning');
									}
									echo '</option>';
								}
								?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Minimum Year Group', 'Free Learning') ?></b><br/>
							<span style="font-size: 90%"><i><?php echo __($guid, 'Lowest age group allowed to view unit.', 'Free Learning').'<br/>'.__($guid, 'Public sharing disabled if set.', 'Free Learning') ?></i></span>
						</td>
						<td class="right">
							<select name="gibbonYearGroupIDMinimum" id="gibbonYearGroupIDMinimum" style="width: 302px">
								<?php
								echo "<option value=''></option>";
								try {
									$dataSelect = array();
									$sqlSelect = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									$selected = '';
									if ($rowSelect['gibbonYearGroupID'] == $row['gibbonYearGroupIDMinimum']) {
										$selected = 'selected';
									}
									echo "<option $selected value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Grouping', 'Free Learning') ?></b><br/>
							<span style="font-size: 90%"><i><?php echo __($guid, 'How should students work during this unit?', 'Free Learning') ?></i></span>
						</td>
						<td class="right">
							<?php
							$checked = '';
							if (strpos($row['grouping'], 'Individual') !== false) {
								$checked = 'checked';
							}
							echo __($guid, 'Individual', 'Free Learning')."<input $checked type='checkbox' name='Individual'><br/>";
							$checked = '';
							if (strpos($row['grouping'], 'Pairs') !== false) {
								$checked = 'checked';
							}
							echo __($guid, 'Pairs', 'Free Learning')."<input $checked type='checkbox' name='Pairs'><br/>";
							$checked = '';
							if (strpos($row['grouping'], 'Threes') !== false) {
								$checked = 'checked';
							}
							echo __($guid, 'Threes', 'Free Learning')."<input $checked type='checkbox' name='Threes'><br/>";
							$checked = '';
							if (strpos($row['grouping'], 'Fours') !== false) {
								$checked = 'checked';
							}
							echo __($guid, 'Fours', 'Free Learning')."<input $checked type='checkbox' name='Fours'><br/>";
							$checked = '';
							if (strpos($row['grouping'], 'Fives') !== false) {
								$checked = 'checked';
							}
							echo __($guid, 'Fives', 'Free Learning')."<input $checked type='checkbox' name='Fives'><br/>";
							?>
						</td>
					</tr>
					<?php

                    $enableSchoolMentorEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableSchoolMentorEnrolment');
    				if ($enableSchoolMentorEnrolment == 'Y') {
    					?>
                        <tr class='break'>
                            <td colspan=2>
                                <h3><?php echo __($guid, 'Mentorship', 'Free Learning') ?></h3>
                            </td>
                        </tr>
                        <tr>
    						<td>
    							<b><?php echo __($guid, 'Completors', 'Free Learning') ?> * </b><br/>
    							<span style="font-size: 90%"><i><?php echo __($guid, 'Allow students who have completed a unit to become a mentor?', 'Free Learning'); ?></i></span>
    						</td>
    						<td class="right">
    							<input <?php if ($row['schoolMentorCompletors'] == 'Y') { echo 'checked' ; } ?> type="radio" name="schoolMentorCompletors" value="Y" /> <?php echo __($guid, 'Yes') ?>
    							<input <?php if ($row['schoolMentorCompletors'] == 'N' || is_null($row['schoolMentorCompletors'])) { echo 'checked' ; } ?> type="radio" name="schoolMentorCompletors" value="N" /> <?php echo __($guid, 'No') ?>
    						</td>
    					</tr>
                        <tr>
    						<td>
    							<b><?php echo __($guid, 'Specific Users', 'Free Learning') ?> * </b><br/>
    							<span style="font-size: 90%"><i><?php echo __($guid, 'Choose specific users who can act as mentors.', 'Free Learning'); ?></i></span>
                                <span class="emphasis small"><?php echo __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
    						</td>
                			<td class="right">
                				<select name="schoolMentorCustom[]" id="schoolMentorCustom[]" multiple class='standardWidth' style="height: 150px">
                					<?php
                                    try {
                						$dataSelect = array();
                						$sqlSelect = "SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                						$resultSelect = $connection2->prepare($sqlSelect);
                						$resultSelect->execute($dataSelect);
                					} catch (PDOException $e) {
                					}
                					while ($rowSelect = $resultSelect->fetch()) {
                                        $selected = '' ;
                                        $staffs = explode(",", $row['schoolMentorCustom']);
                                        foreach ($staffs AS $staff) {
                                            if ($staff == $rowSelect['gibbonPersonID']) {
                                                $selected = 'selected';
                                            }
                                        }
                                        echo "<option $selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true)."</option>";
                					}
                                    ?>
                				</select>
                			</td>
    					</tr>
                        <?php
                    }
                    ?>

					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Outcomes') ?></h3>
						</td>
					</tr>
					<?php
                    $type = 'outcome';
					$allowOutcomeEditing = getSettingByScope($connection2, 'Planner', 'allowOutcomeEditing');
					$categories = array();
					$categoryCount = 0;
					?>
					<style>
						#<?php echo $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
						#<?php echo $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
						div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
						html>body #<?php echo $type ?> li { min-height: 58px; line-height: 1.2em; }
						.<?php echo $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
						.<?php echo $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
					</style>
					<script>
						$(function() {
							$( "#<?php echo $type ?>" ).sortable({
								placeholder: "<?php echo $type ?>-ui-state-highlight",
								axis: 'y'
							});
						});
					</script>
					<tr>
						<td colspan=2>
							<p><?php echo __($guid, 'Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.', 'Free Learning') ?></p>
							<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
								<?php
                                $i = 1;
								$usedArrayFill = '';
								try {
									$dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
									$sqlBlocks = "SELECT freeLearningUnitOutcome.*, scope, name, category FROM freeLearningUnitOutcome JOIN gibbonOutcome ON (freeLearningUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y' ORDER BY sequenceNumber";
									$resultBlocks = $connection2->prepare($sqlBlocks);
									$resultBlocks->execute($dataBlocks);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}
								if ($resultBlocks->rowCount() < 1) {
									echo "<div id='outcomeOuter0'>";
									echo "<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>".__($guid, 'Outcomes listed here...').'</div>';
									echo '</div>';
								} else {
									while ($rowBlocks = $resultBlocks->fetch()) {
										makeBlockOutcome($guid, $i, 'outcome', $rowBlocks['gibbonOutcomeID'],  $rowBlocks['name'],  $rowBlocks['category'], $rowBlocks['content'], '', true, $allowOutcomeEditing);
										$usedArrayFill .= '"'.$rowBlocks['gibbonOutcomeID'].'",';
										++$i;
									}
								}
								?>
							</div>
							<div style='width: 100%; padding: 0px 0px 0px 0px'>
								<div class="ui-state-default_dud" style='padding: 0px; min-height: 50px'>
									<table class='blank' cellspacing='0' style='width: 100%'>
										<tr>
											<td style='width: 50%'>
												<script type="text/javascript">
													var outcomeCount=<?php echo $i ?>;
												</script>
												<select class='all' id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
													<option class='all' value='0'><?php echo __($guid, 'Choose an outcome to add it to this unit') ?></option>
													<?php
                                                    $currentCategory = '';
													$lastCategory = '';
													$switchContents = '';
													try {
														$dataSelect = array();
														$sqlSelect = "SELECT * FROM gibbonOutcome WHERE active='Y' AND scope='School' ORDER BY category, name";
														$resultSelect = $connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													} catch (PDOException $e) {
														echo "<div class='error'>".$e->getMessage().'</div>';
													}
													echo "<optgroup label='--".__($guid, 'SCHOOL OUTCOMES')."--'>";
													while ($rowSelect = $resultSelect->fetch()) {
														$currentCategory = $rowSelect['category'];
														if (($currentCategory != $lastCategory) and $currentCategory != '') {
															echo "<optgroup label='--".$currentCategory."--'>";
															echo "<option class='$currentCategory' value='0'>".__($guid, 'Choose an outcome to add it to this unit').'</option>';
															$categories[$categoryCount] = $currentCategory;
															++$categoryCount;
														}
														echo "<option class='all ".$rowSelect['category']."'   value='".$rowSelect['gibbonOutcomeID']."'>".$rowSelect['name'].'</option>';
														$switchContents .= 'case "'.$rowSelect['gibbonOutcomeID'].'": ';
														$switchContents .= "$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
														$switchContents .= '$("#outcomeOuter" + outcomeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Free%20Learning/units_manage_add_blockOutcomeAjax.php","type=outcome&id=" + outcomeCount + "&title='.urlencode($rowSelect['name'])."\&category=".urlencode($rowSelect['category']).'&gibbonOutcomeID='.$rowSelect['gibbonOutcomeID'].'&contents='.urlencode($rowSelect['description']).'&allowOutcomeEditing='.urlencode($allowOutcomeEditing).'") ;';
														$switchContents .= 'outcomeCount++ ;';
														$switchContents .= "$('#newOutcome').val('0');";
														$switchContents .= 'break;';
														$lastCategory = $rowSelect['category'];
													}

													$currentCategory = '';
													$lastCategory = '';
													$currentLA = '';
													$lastLA = '';
													try {
														$countClause = 0;
														$departments = explode(',', $row['gibbonDepartmentIDList']);
														$dataSelect = array();
														$sqlSelect = '';
														foreach ($departments as $department) {
															$dataSelect['clause'.$countClause] = $department;
															$sqlSelect .= "(SELECT gibbonOutcome.*, gibbonDepartment.name AS learningArea FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:clause".$countClause.') UNION ';
															++$countClause;
														}
														$resultSelect = $connection2->prepare(substr($sqlSelect, 0, -6).'ORDER BY learningArea, category, name');
														$resultSelect->execute($dataSelect);
													} catch (PDOException $e) {
														echo "<div class='error'>".$e->getMessage().'</div>';
													}
													while ($rowSelect = $resultSelect->fetch()) {
														$currentCategory = $rowSelect['category'];
														$currentLA = $rowSelect['learningArea'];
														if (($currentLA != $lastLA) and $currentLA != '') {
															echo "<optgroup label='--".strToUpper($currentLA).' '.__($guid, 'OUTCOMES')."--'>";
														}
														if (($currentCategory != $lastCategory) and $currentCategory != '') {
															echo "<optgroup label='--".$currentCategory."--'>";
															echo "<option class='$currentCategory' value='0'>".__($guid, 'Choose an outcome to add it to this unit').'</option>';
															$categories[$categoryCount] = $currentCategory;
															++$categoryCount;
														}
														echo "<option class='all ".$rowSelect['category']."'   value='".$rowSelect['gibbonOutcomeID']."'>".$rowSelect['name'].'</option>';
														$switchContents .= 'case "'.$rowSelect['gibbonOutcomeID'].'": ';
														$switchContents .= "$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
														$switchContents .= '$("#outcomeOuter" + outcomeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Free%20Learning/units_manage_add_blockOutcomeAjax.php","type=outcome&id=" + outcomeCount + "&title='.urlencode($rowSelect['name'])."\&category=".urlencode($rowSelect['category']).'&gibbonOutcomeID='.$rowSelect['gibbonOutcomeID'].'&contents='.urlencode($rowSelect['description']).'&allowOutcomeEditing='.urlencode($allowOutcomeEditing).'") ;';
														$switchContents .= 'outcomeCount++ ;';
														$switchContents .= "$('#newOutcome').val('0');";
														$switchContents .= 'break;';
														$lastCategory = $rowSelect['category'];
														$lastLA = $rowSelect['learningArea'];
													}

													?>
												</select><br/>
												<?php
                                                if (count($categories) > 0) {
                                                    ?>
													<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
														<option value='all'><?php echo __($guid, 'View All') ?></option>
														<?php
                                                        $categories = array_unique($categories);
                                                    $categories = msort($categories);
                                                    foreach ($categories as $category) {
                                                        echo "<option value='$category'>$category<?php echo ynExpander($guid, 'Y') ;?></option>";
                                                    }
                                                    ?>
													</select>
													<script type="text/javascript">
														$("#newOutcome").chainedTo("#outcomeFilter");
													</script>
													<?php

                                                }
            									?>
												<script type='text/javascript'>
													var <?php echo $type ?>Used=new Array(<?php echo substr($usedArrayFill, 0, -1) ?>);
													var <?php echo $type ?>UsedCount=<?php echo $type ?>Used.length ;

													function outcomeDisplayElements(number) {
														$("#<?php echo $type ?>Outer0").css("display", "none") ;
														if (<?php echo $type ?>Used.indexOf(number)<0) {
															<?php echo $type ?>Used[<?php echo $type ?>UsedCount]=number ;
															<?php echo $type ?>UsedCount++ ;
															switch(number) {
																<?php echo $switchContents ?>
															}
														}
														else {
															alert("This element has already been selected!") ;
															$('#newOutcome').val('0');
														}
													}
												</script>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</td>
					</tr>


					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Unit Outline') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<p><?php echo __($guid, 'The contents of this field are viewable to all users, SO AVOID CONFIDENTIAL OR SENSITIVE DATA!', 'Free Learning') ?></p>
							<?php echo getEditor($guid,  true, 'outline', $row['outline'], 40, true, false, false) ?>
						</td>
					</tr>


					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Smart Blocks') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<p>
								<?php echo __($guid, 'Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller chunks. As well as predefined fields to fill, Smart Blocks provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.', 'Free Learning') ?>
							</p>

							<style>
								#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
								#sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
								div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
								html>body #sortable li { min-height: 58px; line-height: 1.2em; }
								#sortable .ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
							</style>
							<script>
								$(function() {
									$( "#sortable" ).sortable({
										placeholder: "ui-state-highlight",
										axis: 'y'
									});
								});
							</script>


							<div class="sortable" id="sortable" style='width: 100%; padding: 5px 0px 0px 0px'>
								<?php
                                try {
                                    $dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
                                    $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';
                                    $resultBlocks = $connection2->prepare($sqlBlocks);
                                    $resultBlocks->execute($dataBlocks);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
								$i = 1;
								while ($rowBlocks = $resultBlocks->fetch()) {
									makeBlock($guid, $connection2, $i, 'masterEdit', $rowBlocks['title'], $rowBlocks['type'], $rowBlocks['length'], $rowBlocks['contents'], 'N', $rowBlocks['freeLearningUnitBlockID'], '', $rowBlocks['teachersNotes'], true);
									++$i;
								}
								?>
							</div>
							<div style='width: 100%; padding: 0px 0px 0px 0px'>
								<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
									<table class='blank' cellspacing='0' style='width: 100%'>
										<tr>
											<td style='width: 50%'>
												<script type="text/javascript">
													var count=<?php echo $resultBlocks->rowCount() + 1 ?> ;
													$(document).ready(function(){
														$("#new").click(function(){
															$("#sortable").append('<div id=\'blockOuter' + count + '\' class=\'blockOuter\'><img style=\'margin: 10px 0 5px 0\' src=\'<?php echo $_SESSION[$guid]['absoluteURL'] ?>/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');
															$("#blockOuter" + count).load("<?php echo $_SESSION[$guid]['absoluteURL'] ?>/modules/Free%20Learning/units_manage_add_blockAjax.php","id=" + count + "&mode=masterEdit") ;
															count++ ;
														 });
													});
												</script>
												<div id='new' style='cursor: default; float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; color: #999; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px'><?php echo __($guid, 'Click to create a new block') ?></div><br/>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</td>
					</tr>

					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php echo __($guid, 'denotes a required field'); ?></i></span>
						</td>
						<td class="right">
							<input id="submit" type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>
