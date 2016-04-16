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
$schoolType=getSettingByScope($connection2, "Free Learning", "schoolType" ) ;

if (!(isActionAccessible($guid, $connection2, "/modules/Free Learning/units_browse.php")==TRUE OR ($publicUnits=="Y" AND isset($_SESSION[$guid]["username"])==FALSE))) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
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
		print __($guid, "The highest grouped action cannot be determined.") ;
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
		$view="" ;
		if (isset($_GET["view"])) {
			$view=$_GET["view"] ;
		}
		
		print "<div class='trail'>" ;
		if ($publicUnits=="Y") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'>" . __($guid, 'Browse Units') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Unit Details') . "</div>" ;
			}
			else {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'>" . __($guid, 'Browse Units') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Unit Details') . "</div>" ;
			}
		print "</div>" ;
		
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="success0") {
				$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
		
		if ($freeLearningUnitID=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($publicUnits=="Y" AND isset($_SESSION[$guid]["username"])==FALSE) {
						$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
						$sql="SELECT * FROM freeLearningUnit WHERE sharedPublic='Y' AND gibbonYearGroupIDMinimum IS NULL AND active='Y' AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC" ; 
				}
				else {
					if ($highestAction=="Browse Units_all" OR $schoolType=="Online") {
						$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
						$sql="SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID" ; 
					}
					else if ($highestAction=="Browse Units_prerequisites") {
						if ($schoolType=="Physical") {
							$data["freeLearningUnitID"]=$freeLearningUnitID; 
							$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
							$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
							$sql="SELECT freeLearningUnit.*, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2 FROM freeLearningUnit LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID) WHERE active='Y' AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber) AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC" ; 
						}
						else {
							$data["freeLearningUnitID"]=$freeLearningUnitID; 
							$sql="SELECT freeLearningUnit.* FROM freeLearningUnit WHERE active='Y' AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC" ; 
						}
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
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				if ($gibbonDepartmentID!="" OR $difficulty!="" OR $name!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'>" . __($guid, 'Back to Search Results') . "</a>" ;
					print "</div>" ;
				}
				
				$proceed=FALSE ;
				if ($highestAction=="Browse Units_all" OR $schoolType=="Online") {
					$proceed=TRUE ;
				}
				else if ($highestAction=="Browse Units_prerequisites") {
					if ($row["freeLearningUnitIDPrerequisiteList"]==NULL OR $row["freeLearningUnitIDPrerequisiteList"]=="") {
						$proceed=TRUE ;
					}
					else {
						$prerequisitesActive=prerequisitesRemoveInactive($connection2, $row["freeLearningUnitIDPrerequisiteList"]) ;
						$prerquisitesMet=prerquisitesMet($connection2, $_SESSION[$guid]["gibbonPersonID"], $prerequisitesActive) ;
						if ($prerquisitesMet) {
							$proceed=TRUE ;
						}	
					}
				}
				
				if ($proceed==FALSE) {
					print "<div class='error'>" ;
						print __($guid, "The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {
					//Let's go!
					if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage.php")) {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID'>" . __($guid, 'Edit') . "<img style='margin: 0 0 -4px 3px' title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>" ;
						print "</div>" ;
					}
					
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 50%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Unit Name') . "</span><br/>" ;
								print "<i>" . $row["name"] . "</i>" ;
							print "</td>" ;
							print "<td style='width: 50%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Time') . "</span><br/>" ;
								$timing=NULL ;
								$blocks=getBlocksArray($connection2, $freeLearningUnitID) ;
								if ($blocks!=FALSE) {
									foreach ($blocks AS $block) {
										if ($block[0]==$row["freeLearningUnitID"]) {
											if (is_numeric($block[2])) {
												$timing+=$block[2] ;
											}
										}
									}
								}
								if (is_null($timing)) {
									print "<i>" . __($guid, 'NA') . "</i>" ;
								}
								else {
									print "<i>" . $timing . "</i>" ;
								}
							print "</td>" ;
							print "<td style='width: 135%!important; vertical-align: top; text-align: right' rowspan=4>" ;
								if ($row["logo"]==NULL) {
									print "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_125.jpg'/><br/>" ;
								}
								else {
									print "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='" . $row["logo"] . "'/><br/>" ;
								}
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Difficulty') . "</span><br/>" ;
								print "<i>" . $row["difficulty"] . "<i>" ;
							print "</td>" ;
							print "<td style='padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Prerequisites') . "</span><br/>" ;
								$prerequisitesActive=prerequisitesRemoveInactive($connection2, $row["freeLearningUnitIDPrerequisiteList"]) ;
								if ($prerequisitesActive!=FALSE) {
									$prerequisites=explode(",", $prerequisitesActive) ;
									$units=getUnitsArray($connection2) ;
									foreach ($prerequisites AS $prerequisite) {
										print "<i>" . $units[$prerequisite][0] . "</i><br/>" ;
									}
								}
								else {
										print "<i>" . __($guid, 'None') . "<br/></i>" ;
								}
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Departments') . "</span><br/>" ;
								$learningAreas=getLearningAreas($connection2, $guid) ;
								if ($learningAreas=="") {
									print "<i>" . __($guid, 'No Learning Areas available.') . "</i>" ;
								}
								else {
									for ($i=0; $i<count($learningAreas); $i=$i+2) {
										if (is_numeric(strpos($row["gibbonDepartmentIDList"], $learningAreas[$i]))) {
											print "<i>" . __($guid, $learningAreas[($i+1)]) . "</i><br/>" ;
										}
									}
								}
							print "</td>" ;
							print "<td style='vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Authors') . "</span><br/>" ;
								$authors=getAuthorsArray($connection2, $freeLearningUnitID) ;
								foreach ($authors AS $author) {
									if ($author[3]=="") {
										print "<i>" . $author[1] . "</i><br/>" ;
									}
									else {
										print "<i><a target='_blank' href='" . $author[3] . "'>" . $author[1] . "</a></i><br/>" ;
									}
								}
							print "</td>" ;
						print "</tr>" ;
						if ($schoolType=="Physical") {
							print "<tr>" ;
								print "<td style='vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Groupings') . "</span><br/>" ;
									if ($row["grouping"]!="") {
										$groupings=explode(",", $row["grouping"]) ;
										foreach ($groupings AS $grouping) {
											print ucwords($grouping) . "<br/>" ;
										}
									}
								print "</td>" ;
								print "<td style='vertical-align: top'>" ;
								
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					
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
							$enrolmentType="staffView" ;
							
							//Check to see if we can set enrolmentType to "staffEdit" based on access to Manage Units_all
							$manageAll=isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage.php", "Manage Units_all") ;
							if ($manageAll==TRUE) {
								$enrolmentType="staffEdit" ;
							}
							else {
								//Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s)
								$learningAreas=getLearningAreas($connection2, $guid, TRUE) ;
								if ($learningAreas!="") {
									for ($i=0; $i<count($learningAreas); $i=$i+2) {
										if (is_numeric(strpos($row["gibbonDepartmentIDList"], $learningAreas[$i]))) {
											$enrolmentType="staffEdit" ;
										}
									}
								}
							}
						}
					}
					
					$defaultTab=2 ;
					if (!$enrolment) {
						$defaultTab=1 ;
					}
					if (isset($_GET["tab"])) {
						$defaultTab=$_GET["tab"] ;
					}
					?>
					<script type='text/javascript'>
						$(function() {
							$( "#tabs" ).tabs({
								active: <?php print $defaultTab ?>, 
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
					print "<div id='tabs' style='margin: 20px 0'>" ;
						//Tab links
						print "<ul>" ;
							print "<li><a href='#tabs0'>" . __($guid, 'Unit Overview') . "</a></li>" ;
							if ($enrolment) {
								print "<li><a href='#tabs1'>" . __($guid, 'Enrolment') . "</a></li>" ;
							}
							print "<li><a href='#tabs2'>" . __($guid, 'Content') . "</a></li>" ;
							print "<li><a href='#tabs3'>" . __($guid, 'Resources') . "</a></li>" ;
							print "<li><a href='#tabs4'>" . __($guid, 'Outcomes') . "</a></li>" ;
							print "<li><a href='#tabs5'>" . __($guid, 'Exemplar Work') . "</a></li>" ;
						print "</ul>" ;
				
						//Tabs
						print "<div id='tabs0'>" ;
							print "<h3>" ;
								print __($guid, "Blurb") ;
							print "</h3>" ;
							print "<p>" ;
								print $row["blurb"] ;
							print "</p>" ;
							if ($row["license"]!="") {
								print "<h4>" ;
									print __($guid, "License") ;
								print "</h4>" ;
								print "<p>" ;
									print __($guid, "This work is shared under the following license:") . " " . $row["license"] ;
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
						if ($enrolment) {
							print "<div id='tabs1'>" ;
								if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
								$updateReturnMessage="" ;
								$class="error" ;
								if (!($updateReturn=="")) {
									if ($updateReturn=="fail0") {
										$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
									}
									else if ($updateReturn=="fail2") {
										$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
									}
									else if ($updateReturn=="fail3") {
										$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
									}
									else if ($updateReturn=="fail5") {
										$updateReturnMessage=__($guid, "Your request was successful, but some data was not properly saved.") ;	
									}
									else if ($updateReturn=="fail6") {
										$updateReturnMessage=__($guid, "Your request failed due to an attachment error.") ;	
									}
									else if ($updateReturn=="success0") {
										$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
										$class="success" ;
									}
									print "<div class='$class'>" ;
										print $updateReturnMessage;
									print "</div>" ;
								} 
								
								print "<h3>" ;
									print __($guid, "Enrolment") ;
								print "</h3>" ;
									
								if ($enrolmentType=="staffView" OR $enrolmentType=="staffEdit") { //STAFF ENROLMENT
									print "<p>" ;
										print __($guid, "Below you can view the students currently enroled in this unit, including both those who are working on it, and those who are awaiting approval.") ;
									print "</p>" ;
									
									if ($enrolmentType=="staffEdit") {
										print "<div class='linkTop'>" ;
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details_enrolMultiple.php&freeLearningUnitID=$freeLearningUnitID'>" . __($guid, 'Add Multiple') . "<img style='margin: 0 0 -4px 5px' title='" . __($guid, 'Add Multiple') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new_multi.png'/></a>" ;
										print "</div>" ;
									}
									
									//List students whose status is Current or Complete - Pending
									try {
										if ($schoolType=="Physical") {
											$dataClass=array("freeLearningUnitID"=>$row["freeLearningUnitID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
											$sqlClass="SELECT gibbonPersonID, surname, preferredName, freeLearningUnitStudent.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM freeLearningUnitStudent INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL OR dateEnd>='" . date("Y-m-d") . "') AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Approved','Current','Complete - Approved','Exempt'), surname, preferredName" ;
										}
										else {
											$dataClass=array("freeLearningUnitID"=>$row["freeLearningUnitID"]); 
											$sqlClass="SELECT gibbonPersonID, surname, preferredName, freeLearningUnitStudent.* FROM freeLearningUnitStudent INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL OR dateEnd>='" . date("Y-m-d") . "') ORDER BY FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Approved','Current','Complete - Approved','Exempt'), surname, preferredName" ;
										}
										$resultClass=$connection2->prepare($sqlClass);
										$resultClass->execute($dataClass);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									$count=0;
									$rowNum="odd" ;
									if ($resultClass->rowCount()<1) {
										print "<div class='error'>" ;
											print __($guid, "There are no records to display.") ;
										print "</div>" ;
									}
									else {
										?>
										<table cellspacing='0' style="width: 100%">	
											<tr class='head'>
												<th> 
													<?php print __($guid, 'Student') ?><br/>
												</th>
												<?php
												if ($schoolType=="Physical") {
													?>
													<th> 
														<?php print __($guid, 'Class') ?><br/>
													</th>
													<?php
												}
												?>
												<th> 
													<?php print __($guid, 'Status') ?><br/>
												</th>
												<th> 
													<?php print __($guid, 'View') ?><br/>
												</th>
												<th>
													<?php print __($guid, 'Action') ?><br/>
												</th>
											</tr>
											<?php
											while ($rowClass=$resultClass->fetch()) {
												if ($count%2==0) {
													$rowNum="even" ;
												}
												else {
													$rowNum="odd" ;
												}
												$count++ ;
											
												print "<tr class=$rowNum>" ;
												?>
													<td> 
														<?php print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowClass["gibbonPersonID"] . "'>" . formatName("", $rowClass["preferredName"], $rowClass["surname"], "Student", true) . "</a>" ?><br/>
													</td>
													<?php
													if ($schoolType=="Physical") {
														print "<td>" ;
															if ($rowClass["course"]!="" AND $rowClass["class"]!="") {
																print $rowClass["course"] . "." . $rowClass["class"] ;
															}
															else {
																print "<i>" . __($guid, 'NA') . "</i>" ;
															}
														print "</td>" ;
													}
													?>
													<td> 
														<?php print $rowClass["status"] ?><br/>
													</td>	
													<td> 
														<?php
														if ($rowClass["evidenceLocation"]!="") {
															if ($rowClass["evidenceType"]=="Link") {
																print "<a target='_blank' href='" . $rowClass["evidenceLocation"] . "'>" . __($guid, 'View') . "</>" ;
															}
															else {
																print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowClass["evidenceLocation"] . "'>" . __($guid, 'View') . "</>" ;
															}
														}
														?>
													</td>	
													<td>
														<?php 
														if ($enrolmentType=="staffEdit") {
															if ($rowClass["status"]=="Complete - Pending" OR $rowClass["status"]=="Complete - Approved" OR $rowClass["status"]=="Evidence Not Approved") {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitStudentID=" . $rowClass["freeLearningUnitStudentID"] . "&freeLearningUnitID=" . $rowClass["freeLearningUnitID"] . "&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;						
															}
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse_details_delete.php&freeLearningUnitStudentID=" . $rowClass["freeLearningUnitStudentID"] . "&freeLearningUnitID=" . $rowClass["freeLearningUnitID"] . "&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;						
														}
														if ($rowClass["commentStudent"]!="" OR $rowClass["commentApproval"]!="") {
															print "<script type='text/javascript'>" ;	
																print "$(document).ready(function(){" ;
																	print "\$(\".comment-" . $rowClass["freeLearningUnitStudentID"] . "\").hide();" ;
																	print "\$(\".show_hide-" . $rowClass["freeLearningUnitStudentID"] . "\").fadeIn(1000);" ;
																	print "\$(\".show_hide-" . $rowClass["freeLearningUnitStudentID"] . "\").click(function(){" ;
																	print "\$(\".comment-" . $rowClass["freeLearningUnitStudentID"] . "\").fadeToggle(1000);" ;
																	print "});" ;
																print "});" ;
															print "</script>" ;
															print "<a title='" . __($guid, 'Show Comment') . "' class='show_hide-" . $rowClass["freeLearningUnitStudentID"] . "' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . __($guid, 'Show Comment') . "' onclick='return false;' /></a>" ;
														}
														?>
													</td>											
												</tr>
												<?php
												if ($rowClass["commentStudent"]!="" OR $rowClass["commentApproval"]!="") {
													print "<tr class='comment-" . $rowClass["freeLearningUnitStudentID"] . "' id='comment-" . $rowClass["freeLearningUnitStudentID"] . "'>" ;
														print "<td colspan=5>" ;
															if ($rowClass["commentStudent"]!="") {
																print "<b>" . __($guid, "Student Comment") . "</b><br/>" ;
																print nl2br($rowClass["commentStudent"]) . "<br/>" ;
															}
															if ($rowClass["commentApproval"]!="") {
																if ($rowClass["commentStudent"]!="") {
																	print "<br/>" ;
																}
																print "<b>" . __($guid, "Teacher Comment") . "</b><br/>" ;
																print nl2br($rowClass["commentApproval"]) . "<br/>" ;
															}
														print "</td>" ;
													print "</tr>" ;
												}
											}
											?>
										</table>
										<?php
									}
								}
								if ($enrolmentType=="student") { //STUDENT ENROLMENT
									if ($schoolType=="Physical") {
										print "<p>" ;
											print __($guid, "You can be enroled in one unit for each of your classes at any one time. Use the information to manage your enrolment for this unit.") ;
										print "</p>" ;
									}
									
									//Check enrolment status
									$enrolCheckFail=FALSE ;
									try {
										$dataEnrol=array("freeLearningUnitID"=>$freeLearningUnitID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]) ;
										$sqlEnrol="SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID" ; 
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
											if ($rowEnrol["status"]=="Current" OR $rowEnrol["status"]=="Evidence Not Approved") { //Currently enroled, allow to set status to complete and submit feedback...or previously submitted evidence not accepted
												print "<h4>" ;
													print __($guid, "Currently Enroled") ;
												print "</h4>" ;
												if ($schoolType=="Physical") {
													if ($rowEnrol["status"]=="Current") {
														print "<p>" ;
															print sprintf(__($guid, 'You are currently enroled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your teacher will be notified, and will approve your unit completion in due course.'), $row["name"]) ;
														print "</p>" ;
													}
													else if ($rowEnrol["status"]=="Evidence Not Approved") {
														print "<div class='warning'>" ;
															print __($guid, "Your evidence has not been approved. Please read the feedback below, adjust your evidence, and submit again:") . "<br/><br/>" ;
															print "<b>" . $rowEnrol["commentApproval"] . "</b>" ;
														print "</div>" ;
													}
												}
												else {
													if ($rowEnrol["status"]=="Current") {
														print "<p>" ;
															print sprintf(__($guid, 'You are currently enroled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your unit completion will be automatically approved, and you can move onto the next unit.'), $row["name"]) ;
														print "</p>" ;
													}
												}
												
												?>
												<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details_completePendingProcess.php?address=" . $_GET["q"] ?>" enctype="multipart/form-data">
													<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
														<tr>
															<td> 
																<b><?php print __($guid, 'Status') ?> *</b><br/>
																<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
															</td>
															<td class="right">
																<input readonly style='width: 300px' type='text' value='Complete - Pending' />
															</td>
														</tr>
														<tr>
															<td> 
																<b><?php print __($guid, 'Comment') ?> *</b><br/>
																<span style="font-size: 90%"><i>
																	<?php 
																	print __($guid, 'Leave a brief reflective comment on this unit<br/>and what you learned.') ;
																	if ($rowEnrol["status"]=="Evidence Not Approved") {
																		print "<br/><br/>" . __($guid, "Your previous comment is shown here, for you to edit.") ;
																	}
																	?>
																</i></span>
															</td>
															<td class="right">
																<script type='text/javascript'>
																	$(document).ready(function(){
																		$('#commentStudent').autosize();    
																	});
																</script>
																<textarea name="commentStudent" id="commentStudent" rows=8 style="width: 300px"><?php
																if ($rowEnrol["status"]=="Evidence Not Approved") {
																	print $rowEnrol["commentStudent"] ;
																}
																?></textarea>
																<script type="text/javascript">
																	var commentStudent=new LiveValidation('commentStudent');
																	commentStudent.add(Validate.Presence);
																</script>
															</td>
														</tr>
														<tr>
															<td> 
																<b><?php print __($guid, 'Type') ?> *</b><br/>
															</td>
															<td class="right">
																<input checked type="radio" id="type" name="type" class="type" value="Link" /> Link
																<input type="radio" id="type" name="type" class="type" value="File" /> File
															</td>
														</tr>
														<script type="text/javascript">
															/* Subbmission type control */
															$(document).ready(function(){
																$("#fileRow").css("display","none");
																$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row")); 
															
																$(".type").click(function(){
																	if ($('input[name=type]:checked').val()=="Link" ) {
																		$("#fileRow").css("display","none");
																		$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row")); 
																	} else {
																		$("#linkRow").css("display","none");
																		$("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row")); 
																	}
																 });
															});
														</script>
													
														<tr id="fileRow">
															<td> 
																<b><?php print __($guid, 'Submit File') ?> *</b><br/>
															</td>
															<td class="right">
																<input type="file" name="file" id="file"><br/><br/>
																<?php
																print getMaxUpload() ;
															
																//Get list of acceptable file extensions
																try {
																	$dataExt=array(); 
																	$sqlExt="SELECT * FROM gibbonFileExtension" ;
																	$resultExt=$connection2->prepare($sqlExt);
																	$resultExt->execute($dataExt);
																}
																catch(PDOException $e) { }
																$ext="" ;
																while ($rowExt=$resultExt->fetch()) {
																	$ext=$ext . "'." . $rowExt["extension"] . "'," ;
																}
																?>
															
																<script type="text/javascript">
																	var file=new LiveValidation('file');
																	file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
																</script>
															</td>
														</tr>
														<tr id="linkRow">
															<td> 
																<b><?php print __($guid, 'Submit Link') ?> *</b><br/>
															</td>
															<td class="right">
																<input name="link" id="link" maxlength=255 value="" type="text" style="width: 300px">
																<script type="text/javascript">
																	var link=new LiveValidation('link');
																	link.add( Validate.Inclusion, { within: ['http://', 'https://'], failureMessage: "Address must start with http:// or https://", partialMatch: true } );
																</script>
															</td>
														</tr>
														<tr>
															<td class="right" colspan=2>
																<input type="hidden" name="freeLearningUnitStudentID" value="<?php print $rowEnrol["freeLearningUnitStudentID"] ?>">
																<input type="hidden" name="freeLearningUnitID" value="<?php print $freeLearningUnitID ?>">
																<input type="submit" id="submit" value="Submit">
															</td>
														</tr>
														<tr>
															<td class="right" colspan=2>
																<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
															</td>
														</tr>
													</table>
												</form>
												<?php
											}	
											else if ($rowEnrol["status"]=="Complete - Pending") { //Waiting for teacher feedback
												print "<h4>" ;
													print __($guid, "Complete - Pending Approval") ;
												print "</h4>" ;
												print "<p>" ;
													print __($guid, 'Your evidence, shown below, has been submitted to your teacher(s) for approval. This screen will show a teacher comment, once approval has been given.') ;
												print "</p>" ;
												?>
												<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
													<tr>
														<td> 
															<b><?php print __($guid, 'Status') ?></b><br/>
														</td>
														<td class="right">
															<input readonly style='width: 300px' type='text' value='Complete - Pending' />
														</td>
													</tr>
													<tr>
														<td> 
															<b><?php print __($guid, 'Evidence Type') ?></b><br/>
														</td>
														<td class="right">
															<input readonly style='width: 300px' type='text' value='<?php print $rowEnrol["evidenceType"] ?>' />
														</td>
													</tr>
													<tr>
														<td> 
															<b><?php print __($guid, 'Evidence') ?></b><br/>
														</td>
														<td class="right">
															<div style='width: 300px; float: right; text-align: left; font-size: 115%; height: 24px; padding-top: 5px'>
																<?php
																if ($rowEnrol["evidenceType"]=="Link") {
																	print "<a target='_blank' href='" . $rowEnrol["evidenceLocation"] . "'>" . __($guid, 'View') . "</>" ;
																}
																else {
																	print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEnrol["evidenceLocation"] . "'>" . __($guid, 'View') . "</>" ;
																}
																?>
															</div>
														</td>
													</tr>
												</table>
												<?php	
												print "<h4>" ;
													print __($guid, "Student Comment") ;
												print "</h4>" ;
												print "<p>" ;
													print $rowEnrol["commentStudent"] ;
												print "</p>" ;
											}	
											else if ($rowEnrol["status"]=="Complete - Approved") { //Complete, show status and feedback from teacher.
												if ($schoolType=="Physical") {
													print "<h4>" ;
														print __($guid, "Complete - Approved") ;
													print "</h4>" ;
													print "<p>" ;
														print __($guid, 'Congralutations! Your evidence, shown below, has been accepted and approved by your teacher(s), and so you have successfully completed this unit. Please look below for your teacher\'s comment.') ;
													print "</p>" ;
												}
												else {
													print "<h4>" ;
														print __($guid, "Complete") ;
													print "</h4>" ;
													print "<p>" ;
														print __($guid, 'Congralutations! You have submitted your evidence, shown below, and so the unit is complete. Feel free to move on to another unit.') ;
													print "</p>" ;
												}
												?>
												<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
													<tr>
														<td> 
															<b><?php print __($guid, 'Status') ?></b><br/>
														</td>
														<td class="right">
															<input readonly style='width: 300px' type='text' value='Complete - Approved' />
														</td>
													</tr>
													<tr>
														<td> 
															<b><?php print __($guid, 'Evidence Type') ?></b><br/>
														</td>
														<td class="right">
															<input readonly style='width: 300px' type='text' value='<?php print $rowEnrol["evidenceType"] ?>' />
														</td>
													</tr>
													<tr>
														<td> 
															<b><?php print __($guid, 'Evidence') ?></b><br/>
														</td>
														<td class="right">
															<div style='width: 300px; float: right; text-align: left; font-size: 115%; height: 24px; padding-top: 5px'>
																<?php
																if ($rowEnrol["evidenceType"]=="Link") {
																	print "<a target='_blank' href='" . $rowEnrol["evidenceLocation"] . "'>" . __($guid, 'View') . "</>" ;
																}
																else {
																	print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEnrol["evidenceLocation"] . "'>" . __($guid, 'View') . "</>" ;
																}
																?>
															</div>
														</td>
													</tr>
												</table>
												<?php	
												if ($schoolType=="Physical") {
													print "<h4>" ;
														print __($guid, "Teacher Comment") ;
													print "</h4>" ;
													print "<p>" ;
														print $rowEnrol["commentApproval"] ;
													print "</p>" ;
												}
												
												print "<h4>" ;
													print __($guid, "Student Comment") ;
												print "</h4>" ;
												print "<p>" ;
													print $rowEnrol["commentStudent"] ;
												print "</p>" ;
											}	
											else if ($rowEnrol["status"]=="Exempt") { //Exempt, let student know
												print "<h4>" ;
													print __($guid, "Exempt") ;
												print "</h4>" ;
												print "<p>" ;
													print __($guid, 'You are exempt from completing this unit, which means you get the status of completion, without needing to submit any evidence.') ;
												print "</p>" ;
											}
										}
										else { //Not enroled, give a chance to enrol
											print "<h4>" ;
												print __($guid, "Enrol Now") ;
											print "</h4>" ;
											?>
											<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details_enrolProcess.php?address=" . $_GET["q"] ?>">
												<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
													<?php
													if ($schoolType=="Physical") {
														?>
														<tr>
															<td> 
																<b><?php print __($guid, 'Class') ?> *</b><br/>
																<span style="font-size: 90%"><i><?php print __($guid, 'Which class are you enroling for?') ?></i></span>
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
																	<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
																	<?php
																	while ($rowClasses=$resultClasses->fetch()) {
																		print "<option value='" . $rowClasses["gibbonCourseClassID"] . "'>" . $rowClasses["course"] . "." . $rowClasses["class"] . "</option>" ;
																	}
																	?>
																</select>
																<script type="text/javascript">
																	var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
																	gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
																 </script>
															</td>
														</tr>
														<tr>
															<td style='width: 275px'> 
																<b><?php print __($guid, 'Grouping') ?> *</b><br/>
																<span style="font-size: 90%"><i><?php print __($guid, 'How do you want to study this unit?') ?></i></span>
															</td>
															<td class="right">
																<select name="grouping" id="grouping" style="width: 302px">
																	<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
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
																	grouping.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
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
																			<b><?php print sprintf(__($guid, 'Collaborator %1$s'), $i) ?> *</b><br/>
																		</td>
																		<td class="right">
																			<select name="collaborators[]" id="collaborator<?php print $i ?>" style="width: 302px">
																				<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
																				<?php
																				foreach ($students AS $student) {
																					print $student ;
																				}
																				?>
																			</select>
																			<script type="text/javascript">
																				var collaborator<?php print $i ?>=new LiveValidation('collaborator<?php print $i ?>');
																				collaborator<?php print $i ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
																			 </script>
																		</td>
																	</tr>
																	<?php
																}
															}
														}
													?>
													<tr>
														<td class="right" colspan=2>
															<input type="hidden" name="freeLearningUnitID" value="<?php print $freeLearningUnitID ?>">
															<input type="submit" id="submit" value="Enrol Now">
														</td>
													</tr>
													<tr>
														<td class="right" colspan=2>
															<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
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
							$roleCategory=NULL ;
							if (isset($_SESSION[$guid]["gibbonRoleIDCurrent"])) {
								$roleCategory=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
							}
							
							if ($resultBlocks->rowCount()<1) {
								print "<div class='error'>" ;
									print __($guid, "There are no records to display.") ;
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
									if (isset($_SESSION[$guid]["username"])) {
										if ($roleCategory=="Staff") {
											if ($rowBlocks["teachersNotes"]!="") {
												print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>" . __($guid, "Teacher's Notes") . ":</b></p> " . $rowBlocks["teachersNotes"] . "</div>" ;
												$resourceContents.=$rowBlocks["teachersNotes"] ;
											}
										}
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
									print __($guid, "There are no records to display.") ;
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
									print __($guid, "There are no records to display.") ;
								print "</div>" ;
							}
							else {
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print __($guid, "Scope") ;
										print "</th>" ;
										print "<th>" ;
											print __($guid, "Category") ;
										print "</th>" ;
										print "<th>" ;
											print __($guid, "Name") ;
										print "</th>" ;
										print "<th>" ;
											print __($guid, "Year Groups") ;
										print "</th>" ;
										print "<th>" ;
											print __($guid, "Actions") ;
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
												if ($rowBlocks["scope"]=="Learning Area" AND @$rowBlocks["gibbonDepartmentID"]!="") {
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
												print getYearGroupsFromIDList($guid, $connection2, $rowBlocks["gibbonYearGroupIDList"]) ;
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
													print "<a title='" . __($guid, 'View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . __($guid, 'Show Comment') . "' onclick='return false;' /></a>" ;
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
						print "<div id='tabs5'>" ;
							//Spit out exemplar work
							try {
								$dataWork=array("freeLearningUnitID"=>$freeLearningUnitID);  
								$sqlWork="SELECT freeLearningUnitStudent.*, surname, preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) WHERE freeLearningUnitID=:freeLearningUnitID AND exemplarWork='Y' ORDER BY timestampCompleteApproved DESC" ;
								$resultWork=$connection2->prepare($sqlWork);
								$resultWork->execute($dataWork);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultWork->rowCount()<1) {
								print "<div class='error'>" ;
									print __($guid, "There are no records to display.") ;
								print "</div>" ;
							}
							else {
								while ($rowWork=$resultWork->fetch()) {
									$students="" ;
									if ($rowWork["grouping"]=="Individual") { //Created by a single student
										$students=formatName("", $rowWork["preferredName"], $rowWork["surname"], "Student", false) ;
									}
									else { //Created by a group of students
										try{
											$dataStudents=array("collaborationKey"=>$rowWork["collaborationKey"]);  
											$sqlStudents="SELECT surname, preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE active='Y' AND collaborationKey=:collaborationKey ORDER BY surname, preferredName" ;
											$resultStudents=$connection2->prepare($sqlStudents);
											$resultStudents->execute($dataStudents);
										}
										catch(PDOException $e) {  }
										while ($rowStudents=$resultStudents->fetch()) {
											$students.=formatName("", $rowStudents["preferredName"], $rowStudents["surname"], "Student", false) . ", " ;
										} 
										if ($students!="") {
											$students=substr($students, 0, -2) ;
											$students = preg_replace('/,([^,]*)$/', ' & \1', $students);
										}
									}
									
									
									print "<h3>" ;
										print $students . " . <span style='font-size: 75%'>" . __($guid, "Shared on") . " " . dateConvertBack($guid, $rowWork["timestampCompleteApproved"]) . "</span>" ;
									print "</h3>" ;
									//DISPLAY WORK.
									$extension=strrchr($rowWork["evidenceLocation"], ".") ;
									if (strcasecmp($extension, ".gif")==0 OR strcasecmp($extension, ".jpg")==0 OR strcasecmp($extension, ".jpeg")==0 OR strcasecmp($extension, ".png")==0) { //Its an image
										print "<p style='text-align: center'>" ;
											if ($rowWork["evidenceType"]=="File") { //It's a file
												print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["evidenceLocation"] . "'><img style='max-width: 550px' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["evidenceLocation"] . "'/></a>" ;
											}
											else { //It's a link
												print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["evidenceLocation"] . "'><img style='max-width: 550px' src='" . $rowWork["evidenceLocation"] . "'/></a>" ;
											}
										print "</p>" ;
									}
									else { //Not an image
										print "<p>" ;
											if ($rowWork["evidenceType"]=="File") { //It's a file
												print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["evidenceLocation"] . "'>" . __($guid, 'Click to View Work') . "</a>" ;
											}
											else { //It's a link
												print "<a target='_blank' href='" . $rowWork["evidenceLocation"] . "'>" . __($guid, 'Click to View Work') . "</a>" ;
											}
										print "</p>" ;
									}
									print "<p>" ;
										if ($rowWork["commentStudent"]!="") {
											print "<b><u>" . __($guid, "Student Comment") . "</u></b><br/><br/>" ;
											print nl2br($rowWork["commentStudent"]) . "<br/>" ;
										}
										if ($rowWork["commentApproval"]!="") {
											if ($rowWork["commentStudent"]!="") {
												print "<br/>" ;
											}
											print "<b><u>" . __($guid, "Teacher Comment") . "</u></b>" ;
											print $rowWork["commentApproval"] . "<br/>" ;
										}
									print "</p>" ;
								}
							}
						print "</div>" ;
					print "</div>" ;			
				}
			}
		}
	} 
}		
?>