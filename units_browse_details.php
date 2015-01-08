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
				if ($highestAction=="Browse Units_all") {
					$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
					$sql="SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID" ; 
				}
				else if ($highestAction=="Browse Units_prerequisites") {
					$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
					$sql="SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID" ; 
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

					print "<div id='tabs' style='margin: 20px 0'>" ;
						//Tab links
						print "<ul>" ;
							print "<li><a href='#tabs1'>" . _('Unit Overview') . "</a></li>" ;
							print "<li><a href='#tabs2'>" . _('Content') . "</a></li>" ;
							print "<li><a href='#tabs3'>" . _('Resources') . "</a></li>" ;
							print "<li><a href='#tabs4'>" . _('Outcomes') . "</a></li>" ;
						print "</ul>" ;
				
						//Tabs
						print "<div id='tabs1'>" ;
							print "<h3>" ;
								print "Blurb" ;
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