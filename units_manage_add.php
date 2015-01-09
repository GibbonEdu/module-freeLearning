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

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage_add.php")==FALSE) {
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
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_manage.php'>" . _('Manage Units') . "</a> > </div><div class='trailEnd'>" . _('Add Unit') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail4") {
				$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail5") {
				$addReturnMessage=_("Your request failed due to an attachment error.") ;	
			}
			else if ($addReturn=="fail6") {
				$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=_("Your request was completed successfully. You can now add another record if you wish.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
			
		
		?>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_manage_addProcess.php?address=" . $_GET["q"] ?>"  enctype="multipart/form-data">
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
						<input name="name" id="name" maxlength=40 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var name2=new LiveValidation('name');
							name2.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Logo') ?></b><br/>
						<span style="font-size: 90%"><i>125x125px jpg/png/gif</i></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file"><br/><br/>
						<?php
						print getMaxUpload() ;
						$ext="'.png','.jpeg','.jpg','.gif'" ;
						?>
					
						<script type="text/javascript">
							var file=new LiveValidation('file');
							file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
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
									?>
									<option value="<?php print trim($difficulties[$i]) ?>"><?php print trim($difficulties[$i]) ?></option>
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
						<textarea name='blurb' id='blurb' rows=5 style='width: 300px'></textarea>
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
								print _($learningAreas[($i+1)]) . " <input type='checkbox' name='gibbonDepartmentIDCheck" . ($i)/2 . "'><br/>" ; 
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
							<option value=""></option>
							<option value="Copyright"><?php print _('Copyright') ?></option>
							<option value="Creative Commons BY"><?php print _('Creative Commons BY') ?></option>
							<option value="Creative Commons BY-SA"><?php print _('Creative Commons BY-SA') ?></option>
							<option value="Creative Commons BY-SA-NC"><?php print _('Creative Commons BY-SA-NC') ?></option>
							<option value="Public Domain"><?php print _('Public Domain') ?></option>
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
							<input type="radio" name="sharedPublic" value="Y" /> <?php print _('Yes') ?>
							<input checked type="radio" name="sharedPublic" value="N" /> <?php print _('No') ?>
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
							<option value="Y"><?php print _('Yes') ?></option>
							<option value="N"><?php print _('No') ?></option>
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
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM freeLearningUnit WHERE active='Y' ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["freeLearningUnitID"] . "'>" . $rowSelect["name"] . " ( " . $rowSelect["difficulty"] . ")</option>" ;
							}
							?>
							</optgroup>
						</select>
					</td>
				</tr>
				
				<tr class='break'>
					<td colspan=2> 
						<h3><?php print _('Outcomes') ?></h3>
					</td>
				</tr>
				<tr>
					<td colspan=2> 
						<div class='warning'>
							<?php print _('Outcomes can only be set after the new unit has been saved once. Click submit below, and when you land on the edit page, you will be able to manage outcomes.') ?>
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
						<?php $unitOutline=getSettingByScope($connection2, "Free Learning", "unitOutlineTemplate" ) ?>
						<p><?php print _('The contents of this field are viewable to all users, SO AVOID CONFIDENTIAL OR SENSITIVE DATA!') ?></p>
						<?php print getEditor($guid,  TRUE, "outline", $unitOutline, 40, true, false, false) ?>
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
							for ($i=1; $i<=5; $i++) {
								makeBlock($guid, $connection2, $i) ;
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
														$("#sortable").append('<div id=\'blockOuter' + count + '\'><img style=\'margin: 10px 0 5px 0\' src=\'<?php print $_SESSION[$guid]["absoluteURL"] ?>/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');
														$("#blockOuter" + count).load("<?php print $_SESSION[$guid]["absoluteURL"] ?>/modules/Free%20Learning/units_manage_add_blockAjax.php","id=" + count) ;
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
						<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}
?>