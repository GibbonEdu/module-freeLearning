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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

@session_start();

//Module includes
include './modules/Free Learning/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/badges_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo 'You do not have access to this action.';
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>Home</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".getModuleName($_GET['q'])."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/badges_manage.php'>".__($guid, 'Manage Badges')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Badges').'</div>';
    echo '</div>';

    if (isModuleAccessible($guid, $connection2, '/modules/Badges/badges_manage.php') == false) {
        //Acess denied
        echo "<div class='error'>";
        echo 'This functionality requires the Badges module to be installed, active and available.';
        echo '</div>';
    } else {
        //Acess denied
        echo "<div class='success'>";
        echo 'The Badges module is installed, active and available, so you can access this functionality.';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $freeLearningBadgeID = $_GET['freeLearningBadgeID'];
        if ($freeLearningBadgeID == '') { echo "<div class='error'>";
            echo 'You have not specified a policy.';
            echo '</div>';
        } else {
            try {
                $data = array('freeLearningBadgeID' => $freeLearningBadgeID);
                $sql = 'SELECT freeLearningBadge.*, name, category, logo, description
                    FROM freeLearningBadge
                        JOIN badgesBadge ON (freeLearningBadge.badgesBadgeID=badgesBadge.badgesBadgeID)
                    WHERE freeLearningBadgeID=:freeLearningBadgeID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo 'The selected policy does not exist.';
                echo '</div>';
            } else {
                //Let's go!
                $row = $result->fetch();

                if ($_GET['search'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/badges_manage.php&search='.$_GET['search']."'>Back to Search Results</a>";
                    echo '</div>';
                }
                ?>
    			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL']."/modules/Free Learning/badges_manage_editProcess.php?freeLearningBadgeID=$freeLearningBadgeID&search=".$_GET['search'] ?>" enctype="multipart/form-data">
    				<table class='smallIntBorder' cellspacing='0' style="width: 100%">
                        <tr>
            				<td>
            					<b><?php echo __($guid, 'Badge') ?> *</b><br/>
            					<span style="font-size: 90%"><i></i></span>
            				</td>
            				<td class="right">
            					<?php
                                try {
                                    $dataPurpose = array();
                                    $sqlPurpose = 'SELECT * FROM badgesBadge WHERE active=\'Y\' ORDER BY category, name';
                                    $resultPurpose = $connection2->prepare($sqlPurpose);
                                    $resultPurpose->execute($dataPurpose);
                                } catch (PDOException $e) {
                                }

            					echo "<select name='badgesBadgeID' id='badgesBadgeID' style='width: 302px'>";
            					echo "<option value='Please select...'>Please select...</option>";
            					$lastCategory = '';
            					while ($rowPurpose = $resultPurpose->fetch()) {
            						$currentCategory = $rowPurpose['category'];
            						if ($currentCategory != $lastCategory) {
            							echo "<optgroup label='--".$currentCategory."--'>";
            						}
                                    $selected = '';
                                    if ($row['badgesBadgeID'] == $rowPurpose['badgesBadgeID'])
                                        $selected = 'selected';
                                    echo "<option $selected value='".$rowPurpose['badgesBadgeID']."'>".$rowPurpose['name'].'</option>';
            						$lastCategory = $currentCategory;
            					}
            					echo '</select>';
            					?>
            					<script type="text/javascript">
            						var badgesBadgeID=new LiveValidation('badgesBadgeID');
            						badgesBadgeID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
            					</script>
            				</td>
            			</tr>
            			<tr>
            				<td>
            					<b>Active *</b><br/>
            					<span style="font-size: 90%"><i></i></span>
            				</td>
            				<td class="right">
            					<select name="active" id="active" style="width: 302px">
            						<option <?php if ($row['active'] == 'Y') { echo 'selected '; } ?>value="Y">Y</option>
            						<option <?php if ($row['active'] == 'N') { echo 'selected '; } ?>value="N">N</option>
            					</select>
            				</td>
            			</tr>
                        <tr class='break'>
                            <td colspan=2>
                                <h3><?php echo __($guid, 'Conditions') ?></h3>
                                <p><?php echo __($guid, 'This award will automatically be awarded on unit completion, if all of the following conditions are met. Fields left blank will be disregarded.') ?></p>
                            </td>
                        </tr>
                        <tr>
        					<td>
        						<b><?php echo __($guid, 'Units Completed - Total') ?> *</b><br/>
        						<span class="emphasis small"><?php echo __($guid, 'Enter a number greater than zero, or leave blank.') ?></span>
        					</td>
        					<td class="right">
        						<input name="unitsCompleteTotal" ID="unitsCompleteTotal" value="<?php echo $row['unitsCompleteTotal'] ?>" maxlength=2 type="text" class="standardWidth">
        						<script type="text/javascript">
        							var unitsCompleteTotal=new LiveValidation('unitsCompleteTotal');
        							unitsCompleteTotal.add(Validate.Numericality, { minimum: 0 } );
        						</script>
        					</td>
        				</tr>
                        <tr>
        					<td>
        						<b><?php echo __($guid, 'Units Completed - This Year') ?> *</b><br/>
        						<span class="emphasis small"><?php echo __($guid, 'Enter a number greater than zero, or leave blank.') ?></span>
        					</td>
        					<td class="right">
        						<input name="unitsCompleteThisYear" ID="unitsCompleteThisYear" value="<?php echo $row['unitsCompleteThisYear'] ?>" maxlength=2 type="text" class="standardWidth">
        						<script type="text/javascript">
        							var unitsCompleteThisYear=new LiveValidation('unitsCompleteThisYear');
        							unitsCompleteThisYear.add(Validate.Numericality, { minimum: 0 } );
        						</script>
        					</td>
        				</tr>
                        <tr>
        					<td>
        						<b><?php echo __($guid, 'Units Completed - Department Spread') ?> *</b><br/>
        						<span class="emphasis small"><?php echo __($guid, 'Enter a number greater than zero, or leave blank.') ?></span>
        					</td>
        					<td class="right">
        						<input name="unitsCompleteDepartmentCount" ID="unitsCompleteDepartmentCount" value="<?php echo $row['unitsCompleteDepartmentCount'] ?>" maxlength=2 type="text" class="standardWidth">
        						<script type="text/javascript">
        							var unitsCompleteDepartmentCount=new LiveValidation('unitsCompleteDepartmentCount');
        							unitsCompleteDepartmentCount.add(Validate.Numericality, { minimum: 0 } );
        						</script>
        					</td>
        				</tr>
                        <tr>
        					<td>
        						<b><?php echo __($guid, 'Units Completed - Individual') ?> *</b><br/>
        						<span class="emphasis small"><?php echo __($guid, 'Enter a number greater than zero, or leave blank.') ?></span>
        					</td>
        					<td class="right">
        						<input name="unitsCompleteIndividual" ID="unitsCompleteIndividual" value="<?php echo $row['unitsCompleteIndividual'] ?>" maxlength=2 type="text" class="standardWidth">
        						<script type="text/javascript">
        							var unitsCompleteIndividual=new LiveValidation('unitsCompleteIndividual');
        							unitsCompleteIndividual.add(Validate.Numericality, { minimum: 0 } );
        						</script>
        					</td>
        				</tr>
                        <tr>
        					<td>
        						<b><?php echo __($guid, 'Units Completed - Group') ?> *</b><br/>
        						<span class="emphasis small"><?php echo __($guid, 'Enter a number greater than zero, or leave blank.') ?></span>
        					</td>
        					<td class="right">
        						<input name="unitsCompleteGroup" ID="unitsCompleteGroup" value="<?php echo $row['unitsCompleteGroup'] ?>" maxlength=2 type="text" class="standardWidth">
        						<script type="text/javascript">
        							var unitsCompleteGroup=new LiveValidation('unitsCompleteGroup');
        							unitsCompleteGroup.add(Validate.Numericality, { minimum: 0 } );
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
        							<b><?php echo __($guid, 'Difficulty Level Threshold') ?> *</b><br/>
        							<span style="font-size: 90%"><i></i></span>
        						</td>
        						<td class="right">
        							<select name="difficultyLevelMaxAchieved" id="difficultyLevelMaxAchieved" style="width: 302px">
        								<option value=""></option>
        								<?php
                                        for ($i = 0; $i < count($difficulties); ++$i) {
                                            $selected = '';
                                            if ($row['difficultyLevelMaxAchieved'] == trim($difficulties[$i]))
                                                $selected = 'selected';
                                            echo "<option $selected value='".trim($difficulties[$i])."'>".trim($difficulties[$i])."</option>";
                                        }
                    					?>
        							</select>
        						</td>
        					</tr>
        					<?php

        				}
        				?>
    					<tr>
    						<td>
    							<span style="font-size: 90%"><i>* denotes a required field</i></span>
    						</td>
    						<td class="right">
    							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
    							<input type="submit" value="Submit">
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
