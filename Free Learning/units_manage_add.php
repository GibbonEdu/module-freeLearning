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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_add.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'>".__($guid, 'Manage Units', 'Free Learning')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Unit', 'Free Learning').'</div>';
        echo '</div>';

        $returns = array();
        $editLink = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID='.$_GET['editID'].'&gibbonDepartmentID='.$_GET['gibbonDepartmentID'].'&difficulty='.$_GET['difficulty'].'&name='.$_GET['name'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, $returns);
        }

        if ($gibbonDepartmentID != '' or $difficulty != '' or $name != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }

        ?>
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_manage_addProcess.php?address='.$_GET['q']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view" ?>"  enctype="multipart/form-data">
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
						<input name="name" id="name" maxlength=40 value="" type="text" style="width: 300px">
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
                                    ?>
									<option value="<?php echo __($guid, trim($difficulties[$i]), 'Free Learning') ?>"><?php echo __($guid, trim($difficulties[$i]), 'Free Learning') ?></option>
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
						<textarea name='blurb' id='blurb' rows=5 style='width: 300px'></textarea>
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
								echo __($guid, $learningAreas[($i + 1)])." <input type='checkbox' name='gibbonDepartmentIDCheck".($i) / 2 ."'><br/>";
								echo "<input type='hidden' name='gibbonDepartmentID".($i) / 2 ."' value='".$learningAreas[$i]."'>";
							}
						}
						?>
						<input type="hidden" name="count" value="<?php echo(count($learningAreas)) / 2 ?>">
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'License', 'Free Learning') ?></b><br/>
						<span style="font-size: 90%"><i><?php echo __($guid, 'Under what conditions can this work be reused?', 'Free Learning'); ?></i></span>
					</td>
					<td class="right">
						<select name="license" id="license" style="width: 302px">
							<option value=""></option>
							<option value="Copyright"><?php echo __($guid, 'Copyright', 'Free Learning') ?></option>
							<option value="Creative Commons BY"><?php echo __($guid, 'Creative Commons BY', 'Free Learning') ?></option>
							<option value="Creative Commons BY-SA"><?php echo __($guid, 'Creative Commons BY-SA', 'Free Learning') ?></option>
							<option value="Creative Commons BY-SA-NC"><?php echo __($guid, 'Creative Commons BY-SA-NC', 'Free Learning') ?></option>
							<option value="Public Domain"><?php echo __($guid, 'Public Domain', 'Free Learning') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Logo', 'Free Learning') ?></b><br/>
						<span style="font-size: 90%"><i>125x125px jpg/png/gif</i></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file"><br/><br/>
						<?php
                        echo getMaxUpload($guid);
						$ext = "'.png','.jpeg','.jpg','.gif'";
						?>
						<script type="text/javascript">
							var file=new LiveValidation('file');
							file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
						</script>
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
                        <input checked type="radio" name="active" value="Y" /> <?php echo __($guid, 'Yes') ?>
                        <input type="radio" name="active" value="N" /> <?php echo __($guid, 'No') ?>
					</td>
				</tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Available To Students', 'Free Learning') ?> * </b><br/>
                        <span style="font-size: 90%"><i><?php echo __($guid, 'Should students be able to browse and enrol?', 'Free Learning'); ?></i></span>
                    </td>
                    <td class="right">
                        <input checked type="radio" name="availableStudents" value="Y" /> <?php echo __($guid, 'Yes') ?>
                        <input type="radio" name="availableStudents" value="N" /> <?php echo __($guid, 'No') ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Available To Staff', 'Free Learning') ?> * </b><br/>
                        <span style="font-size: 90%"><i><?php echo __($guid, 'Should staff be able to browse and enrol?', 'Free Learning'); ?></i></span>
                    </td>
                    <td class="right">
                        <input checked type="radio" name="availableStaff" value="Y" /> <?php echo __($guid, 'Yes') ?>
                        <input type="radio" name="availableStaff" value="N" /> <?php echo __($guid, 'No') ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Available To Parents', 'Free Learning') ?> * </b><br/>
                        <span style="font-size: 90%"><i><?php echo __($guid, 'Should parents be able to browse and enrol?', 'Free Learning'); ?></i></span>
                    </td>
                    <td class="right">
                        <input checked type="radio" name="availableParents" value="Y" /> <?php echo __($guid, 'Yes') ?>
                        <input type="radio" name="availableParents" value="N" /> <?php echo __($guid, 'No') ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b><?php echo __($guid, 'Available To Others', 'Free Learning') ?> * </b><br/>
                        <span style="font-size: 90%"><i><?php echo __($guid, 'Should other users be able to browse and enrol?', 'Free Learning'); ?></i></span>
                    </td>
                    <td class="right">
                        <input checked type="radio" name="availableOther" value="Y" /> <?php echo __($guid, 'Yes') ?>
                        <input type="radio" name="availableOther" value="N" /> <?php echo __($guid, 'No') ?>
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
							<input type="radio" name="sharedPublic" value="Y" /> <?php echo __($guid, 'Yes') ?>
							<input checked type="radio" name="sharedPublic" value="N" /> <?php echo __($guid, 'No') ?>
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
                                $dataSelect = array();
                                $sqlSelect = 'SELECT * FROM freeLearningUnit ORDER BY name';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                            }
							while ($rowSelect = $resultSelect->fetch()) {
								echo "<option value='".$rowSelect['freeLearningUnitID']."'>".$rowSelect['name'].' ('.$rowSelect['difficulty'].')';
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
					echo "<option value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
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
						echo __($guid, 'Individual')."<input checked type='checkbox' name='Individual'><br/>";
						echo __($guid, 'Pairs')."<input checked type='checkbox' name='Pairs'><br/>";
						echo __($guid, 'Threes')."<input checked type='checkbox' name='Threes'><br/>";
						echo __($guid, 'Fours')."<input checked type='checkbox' name='Fours'><br/>";
						echo __($guid, 'Fives')."<input checked type='checkbox' name='Fives'><br/>";
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
							<input type="radio" name="schoolMentorCompletors" value="Y" /> <?php echo __($guid, 'Yes') ?>
							<input checked type="radio" name="schoolMentorCompletors" value="N" /> <?php echo __($guid, 'No') ?>
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
            						echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true)."</option>";
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
				<tr>
					<td colspan=2>
						<div class='warning'>
							<?php echo __($guid, 'Outcomes can only be set after the new unit has been saved once. Click submit below, and when you land on the edit page, you will be able to manage outcomes.', 'Free Learning') ?>
						</div>
					</td>
				</tr>

				<tr class='break'>
					<td colspan=2>
						<h3><?php echo __($guid, 'Unit Outline', 'Free Learning') ?></h3>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<?php $unitOutline = getSettingByScope($connection2, 'Free Learning', 'unitOutlineTemplate') ?>
						<p><?php echo __($guid, 'The contents of this field are viewable to all users, SO AVOID CONFIDENTIAL OR SENSITIVE DATA!', 'Free Learning') ?></p>
						<?php echo getEditor($guid,  true, 'outline', $unitOutline, 40, true, false, false) ?>
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
                            for ($i = 1; $i <= 5; ++$i) {
                                makeBlock($guid, $connection2, $i);
                            }
       		 				?>
						</div>

						<div style='width: 100%; padding: 0px 0px 0px 0px'>
							<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
								<table class='blank' cellspacing='0' style='width: 100%'>
									<tr>
										<td style='width: 50%'>
											<script type="text/javascript">
												var count=6 ;
												/* Unit type control */
												$(document).ready(function(){
													$("#new").click(function(){
														$("#sortable").append('<div id=\'blockOuter' + count + '\' class=\'blockOuter\'><img style=\'margin: 10px 0 5px 0\' src=\'<?php echo $_SESSION[$guid]['absoluteURL'] ?>/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');
														$("#blockOuter" + count).load("<?php echo $_SESSION[$guid]['absoluteURL'] ?>/modules/Free%20Learning/units_manage_add_blockAjax.php","id=" + count) ;
														count++ ;
													 });
												});
											</script>
											<div id='new' style='cursor: default; float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; color: #999; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px'><?php echo __($guid, 'Click to create a new block', 'Free Learning') ?></div><br/>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</td>
				</tr>

				<tr>
					<td class="right" colspan=2>
						<script type="text/javascript">
							$(document).ready(function(){
								$("#submit").click(function(){
									$("#blockCount").val(count) ;
								 });
							});
						</script>
						<input name="blockCount" id=blockCount value="5" type="hidden">
						<input type="submit" id="submit" value="Submit">
					</td>
				</tr>
				<tr>
					<td class="right" colspan=2>
						<span style="font-size: 90%"><i>* <?php echo __($guid, 'denotes a required field'); ?></i></span>
					</td>
				</tr>
			</table>
		</form>
		<?php

    }
}
?>
