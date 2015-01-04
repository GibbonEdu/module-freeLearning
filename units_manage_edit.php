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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
		$freeLearningUnitID=$_GET["freeLearningUnitID"]; 
		
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_manage.php'>" . _('Manage Units') . "</a> > </div><div class='trailEnd'>" . _('Edit Unit') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
		$updateReturnMessage="" ;
		$class="error" ;
		if (!($updateReturn=="")) {
			if ($updateReturn=="fail0") {
				$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($updateReturn=="fail1") {
				$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($updateReturn=="fail2") {
				$updateReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($updateReturn=="fail3") {
				$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($updateReturn=="fail4") {
				$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($updateReturn=="fail5") {
				$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
			}
			else if ($updateReturn=="fail6") {
				$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage=_("Your request was completed successfully.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="success0") {
				$addReturnMessage=_("Your Smart Unit was successfully created: you can now edit it using the form below.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
		
		try {
			if ($highestAction=="Manage Units_all") {
				$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
				$sql="SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID" ;
			}
			else if ($highestAction=="Manage Units_learningAreas") {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "freeLearningUnitID"=>$freeLearningUnitID); 
				$sql="SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND freeLearningUnitID=:freeLearningUnitID ORDER BY difficulty, name" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_manage_editProcess.php?freeLearningUnitID=$freeLearningUnitID&&address=" . $_GET["q"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Unit Basics') ?></h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Name') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=40 value="<?php print htmlPrep($row["name"]) ; ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<?php
					$difficulties=getSettingByScope($connection2, "Free Learning", "difficultyOptions") ;
					if ($difficulties!=FALSE) {
						$difficulties=explode(",", $difficulties) ;
						?>
						<tr>
							<td> 
								<b><?php print _('Difficulty') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('How hard is this unit?') ?></i></span>
							</td>
							<td class="right">
								<select name="difficulty" id="difficulty" style="width: 302px">
									<option value="Please select..."><?php print _('Please select...') ?></option>
									<?php
									for ($i=0; $i<count($difficulties); $i++) {
										$selected="" ;
										if ($row["difficulty"]==trim($difficulties[$i])) {
											$selected="selected" ;
										}
										?>
										<option <?php print $selected ; ?> value="<?php print trim($difficulties[$i]) ?>"><?php print trim($difficulties[$i]) ?></option>
									<?php
									}
									?>
								</select>
								<script type="text/javascript">
									var difficulty=new LiveValidation('difficulty');
									difficulty.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
								 </script>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td colspan=2> 
							<b><?php print _('Blurb') ?> *</b> 
							<textarea name='blurb' id='blurb' rows=5 style='width: 300px'><?php print htmlPrep($row["blurb"]) ?></textarea>
							<script type="text/javascript">
								var blurb=new LiveValidation('blurb');
								blurb.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Learning Areas') ?></b><br/>
						</td>
						<td class="right">
							<?php 
							if ($highestAction=="Manage Units_all") {
								$learningAreas=getLearningAreas($connection2, $guid) ;
							}
							else if ($highestAction=="Manage Units_learningAreas") {
								$learningAreas=getLearningAreas($connection2, $guid, TRUE) ;
							}
							if ($learningAreas=="") {
								print "<i>" . _('No Learning Areas available.') . "</i>" ;
							}
							else {
								for ($i=0; $i<count($learningAreas); $i=$i+2) {
									$checked="" ;
									if (is_numeric(strpos($row["gibbonDepartmentIDList"], $learningAreas[$i]))) {
										$checked="checked " ;
									}
									print _($learningAreas[($i+1)]) . " <input $checked type='checkbox' name='gibbonDepartmentIDCheck" . ($i)/2 . "'><br/>" ; 
									print "<input type='hidden' name='gibbonDepartmentID" . ($i)/2 . "' value='" . $learningAreas[$i] . "'>" ;
								}
							}
							?>
							<input type="hidden" name="count" value="<?php print (count($learningAreas))/2 ?>">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _("License") ?></b><br/>
							<span style="font-size: 90%"><i><?php print _("Under what conditions can this work be reused?") ; ?></i></span>
						</td>
						<td class="right">
							<select name="license" id="license" style="width: 302px">
								<option <?php if ($row["license"]=="") { print "selected" ; } ?> value=""></option>
								<option <?php if ($row["license"]=="Copyright") { print "selected" ; } ?> value="Copyright"><?php print _('Copyright') ?></option>
								<option <?php if ($row["license"]=="Creative Commons BY") { print "selected" ; } ?> value="Creative Commons BY"><?php print _('Creative Commons BY') ?></option>
								<option <?php if ($row["license"]=="Creative Commons BY-SA") { print "selected" ; } ?> value="Creative Commons BY-SA"><?php print _('Creative Commons BY-SA') ?></option>
								<option <?php if ($row["license"]=="Creative Commons BY-SA-NC") { print "selected" ; } ?> value="Creative Commons BY-SA-NC"><?php print _('Creative Commons BY-SA-NC') ?></option>
								<option <?php if ($row["license"]=="Public Domain") { print "selected" ; } ?> value="Public Domain"><?php print _('Public Domain') ?></option>
							</select>
						</td>
					</tr>
					<?php
					$makeUnitsPublic=getSettingByScope($connection2, "Free Learning", "publicUnits" ) ; 
					if ($makeUnitsPublic=="Y") {
						?>
						<tr>
							<td> 
								<b><?php print _("Shared Publically") ?> * </b><br/>
								<span style="font-size: 90%"><i><?php print _("Share this unit via the public listing of units? Useful for building MOOCS.") ; ?></i></span>
							</td>
							<td class="right">
								<input  <?php if ($row["sharedPublic"]=="Y") { print "checked" ; } ?> type="radio" name="sharedPublic" value="Y" /> <?php print _('Yes') ?>
								<input  <?php if ($row["sharedPublic"]=="N") { print "checked" ; } ?> type="radio" name="sharedPublic" value="N" /> <?php print _('No') ?>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td> 
							<b><?php print _('Active') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="active" id="active" style="width: 302px">
								<option <?php if ($row["active"]=="Y") { print "selected" ; } ?> value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["active"]=="N") { print "selected" ; } ?> value="N"><?php print _('No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
					<td style='width: 275px'> 
						<b><?php print _('Prerequisite Units') ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
					</td>
					<td class="right">
						<select name="prerequisites[]" id="prerequisites[]" multiple style="width: 302px; height: 150px">
							<?php
							try {
								$dataSelect=array("freeLearningUnitID"=>$freeLearningUnitID); 
								$sqlSelect="SELECT * FROM freeLearningUnit WHERE active='Y' AND NOT freeLearningUnitID=:freeLearningUnitID ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if (is_numeric(strpos($row["freeLearningUnitIDPrerequisiteList"],str_pad($rowSelect['freeLearningUnitID'], 10, "0", STR_PAD_LEFT)))) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $rowSelect["freeLearningUnitID"] . "'>" . $rowSelect["name"] . " ( " . $rowSelect["difficulty"] . ")</option>" ;
							}
							?>
							</optgroup>
						</select>
					</td>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Outcomes') ?></h3>
						</td>
					</tr>
					<?php 
					$type="outcome" ; 
					$allowOutcomeEditing=getSettingByScope($connection2, "Planner", "allowOutcomeEditing") ;
					$categories=array() ;
					$categoryCount=0 ;
					?> 
					<style>
						#<?php print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
						#<?php print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
						div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
						html>body #<?php print $type ?> li { min-height: 58px; line-height: 1.2em; }
						.<?php print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
						.<?php print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
					</style>
					<script>
						$(function() {
							$( "#<?php print $type ?>" ).sortable({
								placeholder: "<?php print $type ?>-ui-state-highlight",
								axis: 'y'
							});
						});
					</script>
					<tr>
						<td colspan=2> 
							<p><?php print _('Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.') ?></p>
							<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
								<?php
								$i=1 ;
								$usedArrayFill="" ;
								try {
									$dataBlocks=array("freeLearningUnitID"=>$freeLearningUnitID);  
									$sqlBlocks="SELECT freeLearningUnitOutcome.*, scope, name, category FROM freeLearningUnitOutcome JOIN gibbonOutcome ON (freeLearningUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y' ORDER BY sequenceNumber" ;
									$resultBlocks=$connection2->prepare($sqlBlocks);
									$resultBlocks->execute($dataBlocks);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultBlocks->rowCount()<1) {
									print "<div id='outcomeOuter0'>" ;
										print "<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>" . _('Outcomes listed here...') . "</div>" ;
									print "</div>" ;
								}
								else {
									while ($rowBlocks=$resultBlocks->fetch()) {
										makeBlockOutcome($guid, $i, "outcome", $rowBlocks["gibbonOutcomeID"],  $rowBlocks["name"],  $rowBlocks["category"], $rowBlocks["content"],"",TRUE, $allowOutcomeEditing) ;
										$usedArrayFill.="\"" . $rowBlocks["gibbonOutcomeID"] . "\"," ;
										$i++ ;
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
													var outcomeCount=<?php print $i ?>;
												</script>
												<select class='all' id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
													<option class='all' value='0'><?php print _('Choose an outcome to add it to this unit') ?></option>
													<?php
													$currentCategory="" ;
													$lastCategory="" ;
													$switchContents="" ;
													try {
														$dataSelect=array();  
														$sqlSelect="SELECT * FROM gibbonOutcome WHERE active='Y' AND scope='School' ORDER BY category, name" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													print "<optgroup label='--" . _('SCHOOL OUTCOMES') . "--'>" ;
													while ($rowSelect=$resultSelect->fetch()) {
														$currentCategory=$rowSelect["category"] ;
														if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
															print "<optgroup label='--" . $currentCategory . "--'>" ;
															print "<option class='$currentCategory' value='0'>" . _('Choose an outcome to add it to this unit') . "</option>" ;
															$categories[$categoryCount]=$currentCategory ;
															$categoryCount++ ;
														}
														print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
														$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
														$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
														$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . urlencode($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
														$switchContents.="outcomeCount++ ;" ;
														$switchContents.="$('#newOutcome').val('0');" ;
														$switchContents.="break;" ;
														$lastCategory=$rowSelect["category"] ;
													}
												
													$currentCategory="" ;
													$lastCategory="" ;
													$currentLA="" ;
													$lastLA="" ;
													try {
														$countClause=0 ;
														$departments=explode(",", $row["gibbonDepartmentIDList"]) ;
														$dataSelect=array(); 
														$sqlSelect="" ;
														foreach ($departments as $department) {
															$dataSelect["clause" . $countClause]=$department ;
															$sqlSelect.="(SELECT gibbonOutcome.*, gibbonDepartment.name AS learningArea FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:clause" . $countClause . ") UNION " ;
															$countClause++ ;
														}
														$resultSelect=$connection2->prepare(substr($sqlSelect,0,-6) . "ORDER BY learningArea, category, name");
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													while ($rowSelect=$resultSelect->fetch()) {
														$currentCategory=$rowSelect["category"] ;
														$currentLA=$rowSelect["learningArea"] ;
														if (($currentLA!=$lastLA) AND $currentLA!="") {
															print "<optgroup label='--" . strToUpper($currentLA) . " " . _('OUTCOMES') . "--'>" ;
														}
														if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
															print "<optgroup label='--" . $currentCategory . "--'>" ;
															print "<option class='$currentCategory' value='0'>" . _('Choose an outcome to add it to this unit') . "</option>" ;
															$categories[$categoryCount]=$currentCategory ;
															$categoryCount++ ;
														}
														print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
														$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
														$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
														$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . urlencode($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
														$switchContents.="outcomeCount++ ;" ;
														$switchContents.="$('#newOutcome').val('0');" ;
														$switchContents.="break;" ;
														$lastCategory=$rowSelect["category"] ;
														$lastLA=$rowSelect["learningArea"] ;
													}
												
													?>
												</select><br/>
												<?php
												if (count($categories)>0) {
													?>
													<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
														<option value='all'><?php print _('View All') ?></option>
														<?php
														$categories=array_unique($categories) ;
														$categories=msort($categories) ;
														foreach ($categories AS $category) {
															print "<option value='$category'>$category</option>" ;
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
													var <?php print $type ?>Used=new Array(<?php print substr($usedArrayFill,0,-1) ?>);
													var <?php print $type ?>UsedCount=<?php print $type ?>Used.length ;
													
													function outcomeDisplayElements(number) {
														$("#<?php print $type ?>Outer0").css("display", "none") ;
														if (<?php print $type ?>Used.indexOf(number)<0) {
															<?php print $type ?>Used[<?php print $type ?>UsedCount]=number ;
															<?php print $type ?>UsedCount++ ;
															switch(number) {
																<?php print $switchContents ?>
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
							<h3><?php print _('Unit Outline') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<p><?php print _('The contents of this field are viewable only to those with full access to the Planner (usually teachers and administrators, but not students and parents), whereas the downloadable version (below) is available to more users (usually parents).') ?></p>
							<?php print getEditor($guid,  TRUE, "outline", $row["outline"], 40, true, false, false) ?>
						</td>
					</tr>
					
					
					<tr class='break'>
						<td colspan=2>
							<h3><?php print _('Smart Blocks') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<p>
								<?php print _('Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller chunks. As well as predefined fields to fill, Smart Blocks provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.') ?>
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
									$dataBlocks=array("freeLearningUnitID"=>$freeLearningUnitID); 
									$sqlBlocks="SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber" ;
									$resultBlocks=$connection2->prepare($sqlBlocks);
									$resultBlocks->execute($dataBlocks);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								$i=1 ;
								while ($rowBlocks=$resultBlocks->fetch()) {
									makeBlock($guid, $connection2, $i, "masterEdit", $rowBlocks["title"], $rowBlocks["type"], $rowBlocks["length"], $rowBlocks["contents"], "N", $rowBlocks["freeLearningUnitBlockID"], "", $rowBlocks["teachersNotes"], TRUE) ;
									$i++ ;
								}
								?>
							</div>
							<div style='width: 100%; padding: 0px 0px 0px 0px'>
								<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
									<table class='blank' cellspacing='0' style='width: 100%'>
										<tr>
											<td style='width: 50%'>
												<script type="text/javascript">
													var count=<?php print ($resultBlocks->rowCount()+1) ?> ;
													$(document).ready(function(){
														$("#new").click(function(){
															$("#sortable").append('<div id=\'blockOuter' + count + '\'><img style=\'margin: 10px 0 5px 0\' src=\'<?php print $_SESSION[$guid]["absoluteURL"] ?>/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');
															$("#blockOuter" + count).load("<?php print $_SESSION[$guid]["absoluteURL"] ?>/modules/Planner/units_add_blockAjax.php","id=" + count + "&mode=masterEdit") ;
															count++ ;
														 });
													});
												</script>
												<div id='new' style='cursor: default; float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; color: #999; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px'><?php print _('Click to create a new block') ?></div><br/>
											</td>
										</tr>
									</table>
								</div>
							</div>
						</td>
					</tr>
									
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
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