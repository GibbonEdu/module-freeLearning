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

$publicUnits=getSettingByScope($connection2, "Free Learning", "publicUnits" ) ;
if (!(isActionAccessible($guid, $connection2, "/modules/Free Learning/units_browse.php")==TRUE OR ($publicUnits=="Y" AND isset($_SESSION[$guid]["username"])==FALSE))) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	if ($publicUnits=="Y" AND isset($_SESSION[$guid]["username"])==FALSE) {
		$highestAction="Browse Units_all" ;
	}
	else {
		$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	}
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Get params
		$freeLearningUnitID="" ;
		if (isset($_GET["freeLearningUnitID"])) {
			$freeLearningUnitID=$_GET["freeLearningUnitID"] ;
		}
		$gibbonDepartmentID="" ;
		if (isset($_GET["gibbonDepartmentID"])) {
			$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
		}
		$difficulty="" ;
		if (isset($_GET["difficulty"])) {
			$difficulty=$_GET["difficulty"] ;
		}
		$name="" ;
		if (isset($_GET["name"])) {
			$name=$_GET["name"] ;
		}
		
		
		print "<div class='trail'>" ;
		if ($publicUnits=="Y") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'>" . _('Browse Units') . "</a> > </div><div class='trailEnd'>" . _('Unit Details') . "</div>" ;
			}
			else {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'>" . _('Browse Units') . "</a> > </div><div class='trailEnd'>" . _('Unit Details') . "</div>" ;
			}
		print "</div>" ;
		
		if ($freeLearningUnitID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($publicUnits=="Y" AND isset($_SESSION[$guid]["username"])==FALSE) {
						$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
						$sql="SELECT * FROM freeLearningUnit WHERE sharedPublic='Y' AND gibbonYearGroupIDMinimum IS NULL AND active='Y' AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC" ; 
				}
				else {
					if ($highestAction=="Browse Units_all") {
						$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
						$sql="SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID" ; 
					}
					else if ($highestAction=="Browse Units_prerequisites") {
						$data["freeLearningUnitID"]=$freeLearningUnitID; 
						$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
						$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
						$sql="SELECT freeLearningUnit.*, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2 FROM freeLearningUnit LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID) WHERE active='Y' AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber) AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC" ; 
					}
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
	
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				
				$proceed=FALSE ;
				if ($highestAction=="Browse Units_all") {
					$proceed=TRUE ;
				}
				else if ($highestAction=="Browse Units_prerequisites") {
					if ($row["freeLearningUnitIDPrerequisiteList"]==NULL OR $row["freeLearningUnitIDPrerequisiteList"]=="") {
						$proceed=TRUE ;
					}
					else {
						$prerequisitesActive=prerquisitesRemoveInactive($connection2, $row["freeLearningUnitIDPrerequisiteList"]) ;
						$prerquisitesMet=prerquisitesMet($connection2, $_SESSION[$guid]["gibbonPersonID"], $prerequisitesActive) ;
						if ($prerquisitesMet) {
							$proceed=TRUE ;
						}	
					}
				}
				
				if ($proceed==FALSE) {
					print "<div class='error'>" ;
						print _("The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {
					//Let's go!
					if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage.php")) {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID'>" . _('Edit') . "<img style='margin: 0 0 -4px 3px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>" ;
						print "</div>" ;
					}
					
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Unit Name') . "</span><br/>" ;
								print $row["name"] ;
							print "</td>" ;
							print "<td style='width: 34%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Departments') . "</span><br/>" ;
								$learningAreas=getLearningAreas($connection2, $guid) ;
								if ($learningAreas=="") {
									print "<i>" . _('No Learning Areas available.') . "</i>" ;
								}
								else {
									for ($i=0; $i<count($learningAreas); $i=$i+2) {
										if (is_numeric(strpos($row["gibbonDepartmentIDList"], $learningAreas[$i]))) {
											print _($learningAreas[($i+1)]) . "<br/>" ;
										}
									}
								}
							print "</td>" ;
							print "<td style='width: 34%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Authors') . "</span><br/>" ;
								$authors=getAuthorsArray($connection2, $freeLearningUnitID) ;
								foreach ($authors AS $author) {
									print $author[1] . "<br/>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
				
					?>
					<script type='text/javascript'>
						$(function() {
							$( "#tabs" ).tabs({
								ajaxOptions: {
									error: function( xhr, status, index, anchor ) {
										$( anchor.hash ).html(
											"Couldn't load this tab." );
									}
								}
							});
						});
					</script>
				
					<?php
					//Work out if we should show enrolment, and what type of enrolment it should be (e.g. self or class)
					$enrolment=FALSE ;
					$enrolmentType=NULL ;
					if (isset($_SESSION[$guid]["username"])) {
						$roleCategory=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
						if ($roleCategory=="Student") {
							$enrolment=TRUE ;
							$enrolmentType="student" ;
						}
						else if ($roleCategory=="Staff") {
							$enrolment=TRUE ;
							$enrolmentType="staff" ;
							//What do you need to have to manage enrolment...any categories?
						}
					}
				

					print "<div id='tabs' style='margin: 20px 0'>" ;
						//Tab links
						print "<ul>" ;
							if ($enrolment) {
								print "<li><a href='#tabs0'>" . _('Enrolment') . "</a></li>" ;
							}
							print "<li><a href='#tabs1'>" . _('Unit Overview') . "</a></li>" ;
							print "<li><a href='#tabs2'>" . _('Content') . "</a></li>" ;
							print "<li><a href='#tabs3'>" . _('Resources') . "</a></li>" ;
							print "<li><a href='#tabs4'>" . _('Outcomes') . "</a></li>" ;
						print "</ul>" ;
				
						//Tabs
						if ($enrolment) {
							print "<div id='tabs0'>" ;
								if ($enrolmentType=="student") { //STUDENT ENROLMENT
									if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
										$updateReturnMessage="" ;
										$class="error" ;
										if (!($updateReturn=="")) {
											if ($updateReturn=="fail0") {
												$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
											}
											else if ($updateReturn=="fail2") {
												$updateReturnMessage=_("Your request failed due to a database error.") ;	
											}
											else if ($updateReturn=="fail3") {
												$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
											}
											else if ($updateReturn=="fail5") {
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
									
									print "<h3>" ;
										print _("Enrolment") ;
									print "</h3>" ;
									print "<p>" ;
										print _("You can be enroled in one unit for each of your units at any one time. Use the information to manage your enrolment for this unit.") ;
									print "</p>" ;
									
									//Check enrolment status
									$enrolCheckFail=FALSE ;
									try {
										$dataEnrol=array("freeLearningUnitID"=>$freeLearningUnitID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]) ;
										$sqlEnrol="SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID" ; 
										$resultEnrol=$connection2->prepare($sqlEnrol);
										$resultEnrol->execute($dataEnrol);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										$enrolCheckFail=TRUE ;
									}
									
									if ($enrolCheckFail==FALSE) {
										if ($resultEnrol->rowCount()==1) { //Already enroled, deal with different statuses
											$rowEnrol=$resultEnrol->fetch() ;
											if ($rowEnrol["status"]=="Current") { //Currently enroled, allow to set status to complete and submit feedback
												print "<h5>" ;
													print _("Currently Enroled") ;
												print "</h5>" ;
											}	
											else if ($rowEnrol["status"]=="Complete - Pending") { //Waiting for teacher feedback
												print "<h5>" ;
													print _("Complete - Pending Approval") ;
												print "</h5>" ;
											}	
											else if ($rowEnrol["status"]=="Complete - Approved") { //Complete, show status and feedback from teacher.
												print "<h5>" ;
													print _("Complete!") ;
												print "</h5>" ;
											}	
										}
										else { //Not enroled, give a chance to enrol
											print "<h5>" ;
												print _("Enrol Now") ;
											print "</h5>" ;
											?>
											<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details_enrolProcess.php?address=" . $_GET["q"] ?>">
												<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
													<tr>
														<td> 
															<b><?php print _('Class') ?> *</b><br/>
															<span style="font-size: 90%"><i><?php print _('Which class are you enroling for?') ?></i></span>
														</td>
														<td class="right">
															<?php
															try {
																$dataClasses=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"]); 
																$sqlClasses="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' ORDER BY course, class" ;
																$resultClasses=$connection2->prepare($sqlClasses);
																$resultClasses->execute($dataClasses);
															}
															catch(PDOException $e) { }
															?>
															<select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
																<option value="Please select..."><?php print _('Please select...') ?></option>
																<?php
																while ($rowClasses=$resultClasses->fetch()) {
																	print "<option value='" . $rowClasses["gibbonCourseClassID"] . "'>" . $rowClasses["course"] . "." . $rowClasses["class"] . "</option>" ;
																}
																?>
															</select>
															<script type="text/javascript">
																var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
																gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
															 </script>
														</td>
													</tr>
													<tr>
														<td style='width: 275px'> 
															<b><?php print _('Grouping') ?> *</b><br/>
															<span style="font-size: 90%"><i><?php print _('How do you want to study this unit?') ?></i></span>
														</td>
														<td class="right">
															<select name="grouping" id="grouping" style="width: 302px">
																<option value="Please select..."><?php print _('Please select...') ?></option>
																<?php
																$group=FALSE ;
																$extraSlots=0 ;
																if (strpos($row["grouping"], "Individual")!==FALSE) {
																	print "<option value=\"Individual\">Individual</option>" ;
																}
																if (strpos($row["grouping"], "Pairs")!==FALSE) {
																	print "<option value=\"Pairs\">Pair</option>" ;
																	$group=TRUE ;
																	$extraSlots=1 ;
																}
																if (strpos($row["grouping"], "Threes")!==FALSE) {
																	print "<option value=\"Threes\">Three</option>" ;
																	$group=TRUE ;
																	$extraSlots=2 ;
																}
																if (strpos($row["grouping"], "Fours")!==FALSE) {
																	print "<option value=\"Fours\">Four</option>" ;
																	$group=TRUE ;
																	$extraSlots=3 ;
																}
																if (strpos($row["grouping"], "Fives")!==FALSE) {
																	print "<option value=\"Fives\">Five</option>" ;
																	$group=TRUE ;
																	$extraSlots=4 ;
																}
																?>
															</select>
															<script type="text/javascript">
																var grouping=new LiveValidation('grouping');
																grouping.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
															 </script>
														</td>
													</tr>
													<?php
														if ($group) {
															//Get array of students
															$students=array() ;
															$studentCount=0 ;
															try {
																$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"]); 
																$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
																$resultSelect=$connection2->prepare($sqlSelect);
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { }
															while ($rowSelect=$resultSelect->fetch()) {
																$students[$studentCount]="<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["rollGroup"] . ")</option>" ;
																$studentCount++ ;
															}		
															
															//Controls for lists
															?>
															<script type='text/javascript'>
																$(document).ready(function(){
																	$('tr.collaborator').css('display','none');
																	<?php
																	for ($i=1; $i<=$extraSlots; $i++) {
																		print "collaborator" . $i . ".disable();" ;
																	}
																	?>
																	$('#grouping').change(function(){
																		if ($('select#grouping option:selected').val()=='Individual') {
																			$('#trCollaborator1').css('display','none');
																			collaborator1.disable() ;
																			$('#trCollaborator2').css('display','none');
																			collaborator2.disable() ;
																			$('#trCollaborator3').css('display','none');
																			collaborator3.disable() ;
																			$('#trCollaborator4').css('display','none');
																			collaborator4.disable() ;
																		} 
																		else if ($('select#grouping option:selected').val()=='Pairs') {
																			$('#trCollaborator1').css('display','table-row');
																			collaborator1.enable() ;
																			$('#trCollaborator2').css('display','none');
																			collaborator2.disable() ;
																			$('#trCollaborator3').css('display','none');
																			collaborator3.disable() ;
																			$('#trCollaborator4').css('display','none');
																			collaborator4.disable() ;
																		} 
																		else if ($('select#grouping option:selected').val()=='Threes') {
																			$('#trCollaborator1').css('display','table-row');
																			collaborator1.enable() ;
																			$('#trCollaborator2').css('display','table-row');
																			collaborator2.enable() ;
																			$('#trCollaborator3').css('display','none');
																			collaborator3.disable() ;
																			$('#trCollaborator4').css('display','none');
																			collaborator4.disable() ;
																		} 
																		else if ($('select#grouping option:selected').val()=='Fours') {
																			$('#trCollaborator1').css('display','table-row');
																			collaborator1.enable() ;
																			$('#trCollaborator2').css('display','table-row');
																			collaborator2.enable() ;
																			$('#trCollaborator3').css('display','table-row');
																			collaborator3.enable() ;
																			$('#trCollaborator4').css('display','none');
																			collaborator4.disable() ;
																		} 
																		else if ($('select#grouping option:selected').val()=='Fives') {
																			$('#trCollaborator1').css('display','table-row');
																			collaborator1.enable() ;
																			$('#trCollaborator2').css('display','table-row');
																			collaborator2.enable() ;
																			$('#trCollaborator3').css('display','table-row');
																			collaborator3.enable() ;
																			$('#trCollaborator4').css('display','table-row');
																			collaborator4.enable() ;
																		} 
																	});
																});
															</script>	
															
															<?php
															//Output select lists
															for ($i=1; $i<=$extraSlots; $i++) {
																?>
																<tr class='collaborator' id='<?php print "trCollaborator$i" ?>'>
																	<td style='width: 275px'> 
																		<b><?php print sprintf(_('Collaborator %1$s'), $i) ?> *</b><br/>
																	</td>
																	<td class="right">
																		<select name="collaborators[]" id="collaborator<?php print $i ?>" style="width: 302px">
																			<option value="Please select..."><?php print _('Please select...') ?></option>
																			<?php
																			foreach ($students AS $student) {
																				print $student ;
																			}
																			?>
																		</select>
																		<script type="text/javascript">
																			var collaborator<?php print $i ?>=new LiveValidation('collaborator<?php print $i ?>');
																			collaborator<?php print $i ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
																		 </script>
																	</td>
																</tr>
																<?php
															}
														}
													?>
													<tr>
														<td class="right" colspan=2>
															<input type="hidden" name="freeLearningUnitID" value="<?php print $freeLearningUnitID ?>">
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
								}	
							print "</div>" ;
						}
						print "<div id='tabs1'>" ;
							print "<h3>" ;
								print _("Blurb") ;
							print "</h3>" ;
							print "<p>" ;
								print $row["blurb"] ;
							print "</p>" ;
							if ($row["license"]!="") {
								print "<h4>" ;
									print _("License") ;
								print "</h4>" ;
								print "<p>" ;
									print _("This work is shared under the following license:") . " " . $row["license"] ;
								print "</p>" ;
							}
							if ($row["outline"]!="") {
								print "<h3>" ;
									print "Outline" ;
								print "</h3>" ;
								print "<p>" ;
									print $row["outline"] ;
								print "</p>" ;
							}
						print "</div>" ;
						print "<div id='tabs2'>" ;
							try {
								$dataBlocks=array("freeLearningUnitID"=>$freeLearningUnitID); 
								$sqlBlocks="SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber" ; 
								$resultBlocks=$connection2->prepare($sqlBlocks );
								$resultBlocks->execute($dataBlocks);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
						
							$resourceContents="" ;
				
							if ($resultBlocks->rowCount()<1) {
								print "<div class='error'>" ;
									print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								while ($rowBlocks=$resultBlocks->fetch()) {
									if ($rowBlocks["title"]!="" OR $rowBlocks["type"]!="" OR $rowBlocks["length"]!="") {
										print "<div style='min-height: 35px'>" ;
											print "<div style='padding-left: 3px; width: 100%; float: left;'>" ;
												if ($rowBlocks["title"]!="") {
													print "<h3 style='padding-bottom: 2px'>" ;
														print $rowBlocks["title"] . "<br/>" ;
														print "<div style='font-weight: normal; font-size: 75%; text-transform: none; margin-top: 5px'>" ;
															if ($rowBlocks["type"]!="") {
																print $rowBlocks["type"] ;
																if ($rowBlocks["length"]!="") {
																	print " | " ;
																}
															}
															if ($rowBlocks["length"]!="") {
																print $rowBlocks["length"] . " min" ;
															}
														print "</div>" ;
													print "</h3>" ;
												}
											print "</div>" ;
					
										print "</div>" ;
									}
									if ($rowBlocks["contents"]!="") {
										print "<div style='padding: 15px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'>" . $rowBlocks["contents"] . "</div>" ;
										$resourceContents.=$rowBlocks["contents"] ;
									}
									if ($rowBlocks["teachersNotes"]!="") {
										print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>" . _("Teacher's Notes") . ":</b></p> " . $rowBlocks["teachersNotes"] . "</div>" ;
										$resourceContents.=$rowBlocks["teachersNotes"] ;
									}
								}
							}
						print "</div>" ;
						print "<div id='tabs3'>" ;
							//Resources
							$noReosurces=TRUE ;
						
							//Links
							$links="" ;
							$linksArray=array() ;
							$linksCount=0;
							$dom=new DOMDocument;
							@$dom->loadHTML($resourceContents);
							foreach ($dom->getElementsByTagName('a') as $node) {
								if ($node->nodeValue!="") {
									$linksArray[$linksCount]="<li><a href='" .$node->getAttribute("href") . "'>" . $node->nodeValue . "</a></li>" ;
									$linksCount++ ;
								}
							}
						
							$linksArray=array_unique($linksArray) ;
							natcasesort($linksArray) ;
						
							foreach ($linksArray AS $link) {
								$links.=$link ;
							}
						
							if ($links!="" ) {
								print "<h2>" ;
									print "Links" ;
								print "</h2>" ;
								print "<ul>" ;
									print $links ;
								print "</ul>" ;
								$noReosurces=FALSE ;
							}
						
							//Images
							$images="" ;
							$imagesArray=array() ;
							$imagesCount=0;
							$dom2=new DOMDocument;
							@$dom2->loadHTML($resourceContents);
							foreach ($dom2->getElementsByTagName('img') as $node) {
								if ($node->getAttribute("src")!="") {
									$imagesArray[$imagesCount]="<img class='resource' style='margin: 10px 0; max-width: 560px' src='" . $node->getAttribute("src") . "'/><br/>" ;
									$imagesCount++ ;
								}
							}
						
							$imagesArray=array_unique($imagesArray) ;
							natcasesort($imagesArray) ;
						
							foreach ($imagesArray AS $image) {
								$images.=$image ;
							}
						
							if ($images!="" ) {
								print "<h2>" ;
									print "Images" ;
								print "</h2>" ;
								print $images ;
								$noReosurces=FALSE ;
							}
						
							//Embeds
							$embeds="" ;
							$embedsArray=array() ;
							$embedsCount=0;
							$dom2=new DOMDocument;
							@$dom2->loadHTML($resourceContents);
							foreach ($dom2->getElementsByTagName('iframe') as $node) {
								if ($node->getAttribute("src")!="") {
									$embedsArray[$embedsCount]="<iframe style='max-width: 560px' width='" . $node->getAttribute("width") . "' height='" . $node->getAttribute("height") . "' src='" . $node->getAttribute("src") . "' frameborder='" . $node->getAttribute("frameborder") . "'></iframe>" ;
									$embedsCount++ ;
								}
							}
						
							$embedsArray=array_unique($embedsArray) ;
							natcasesort($embedsArray) ;
						
							foreach ($embedsArray AS $embed) {
								$embeds.=$embed ."<br/><br/>" ;
							}
						
							if ($embeds!="" ) {
								print "<h2>" ;
									print "Embeds" ;
								print "</h2>" ;
								print $embeds ;
								$noReosurces=FALSE ;
							}
						
							//No resources!
							if ($noReosurces) {
								print "<div class='error'>" ;
									print _("There are no records to display.") ;
								print "</div>" ;
							}
						print "</div>" ;
						print "<div id='tabs4'>" ;
							//Spit out outcomes
							try {
								$dataBlocks=array("freeLearningUnitID"=>$freeLearningUnitID);  
								$sqlBlocks="SELECT freeLearningUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM freeLearningUnitOutcome JOIN gibbonOutcome ON (freeLearningUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y' ORDER BY sequenceNumber" ;
								$resultBlocks=$connection2->prepare($sqlBlocks);
								$resultBlocks->execute($dataBlocks);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultBlocks->rowCount()<1) {
								print "<div class='error'>" ;
									print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print _("Scope") ;
										print "</th>" ;
										print "<th>" ;
											print _("Category") ;
										print "</th>" ;
										print "<th>" ;
											print _("Name") ;
										print "</th>" ;
										print "<th>" ;
											print _("Year Groups") ;
										print "</th>" ;
										print "<th>" ;
											print _("Actions") ;
										print "</th>" ;
									print "</tr>" ;
					
									$count=0;
									$rowNum="odd" ;
									while ($rowBlocks=$resultBlocks->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
						
										//COLOR ROW BY STATUS!
										print "<tr class=$rowNum>" ;
											print "<td>" ;
												print "<b>" . $rowBlocks["scope"] . "</b><br/>" ;
												if ($rowBlocks["scope"]=="Learning Area" AND $rowBlocks["gibbonDepartmentID"]!="") {
													try {
														$dataLearningArea=array("gibbonDepartmentID"=>$rowBlocks["gibbonDepartmentID"]); 
														$sqlLearningArea="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
														$resultLearningArea=$connection2->prepare($sqlLearningArea);
														$resultLearningArea->execute($dataLearningArea);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													if ($resultLearningArea->rowCount()==1) {
														$rowLearningAreas=$resultLearningArea->fetch() ;
														print "<span style='font-size: 75%; font-style: italic'>" . $rowLearningAreas["name"] . "</span>" ;
													}
												}
											print "</td>" ;
											print "<td>" ;
												print "<b>" . $rowBlocks["category"] . "</b><br/>" ;
											print "</td>" ;
											print "<td>" ;
												print "<b>" . $rowBlocks["nameShort"] . "</b><br/>" ;
												print "<span style='font-size: 75%; font-style: italic'>" . $rowBlocks["name"] . "</span>" ;
											print "</td>" ;
											print "<td>" ;
												print getYearGroupsFromIDList($connection2, $rowBlocks["gibbonYearGroupIDList"]) ;
											print "</td>" ;
											print "<td>" ;
												print "<script type='text/javascript'>" ;	
													print "$(document).ready(function(){" ;
														print "\$(\".description-$count\").hide();" ;
														print "\$(\".show_hide-$count\").fadeIn(1000);" ;
														print "\$(\".show_hide-$count\").click(function(){" ;
														print "\$(\".description-$count\").fadeToggle(1000);" ;
														print "});" ;
													print "});" ;
												print "</script>" ;
												if ($rowBlocks["content"]!="") {
													print "<a title='" . _('View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
												}
											print "</td>" ;
										print "</tr>" ;
										if ($rowBlocks["content"]!="") {
											print "<tr class='description-$count' id='description-$count'>" ;
												print "<td colspan=6>" ;
													print $rowBlocks["content"] ;
												print "</td>" ;
											print "</tr>" ;
										}
										print "</tr>" ;
						
										$count++ ;
									}
								print "</table>" ;
							}
						
						print "</div>" ;
					print "</div>" ;			
				}
			}
		}
	} 
}		
?>