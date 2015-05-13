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
		print "<div class='trail'>" ;
			if ($publicUnits=="Y") {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > </div><div class='trailEnd'>" . _('Browse Units') . "</div>" ;
			}
			else {
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Browse Units') . "</div>" ;
			}
		print "</div>" ;
		
		$gibbonDepartmentID=NULL ;
		if (isset($_GET["gibbonDepartmentID"])) {
			$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
		}	
		$difficulty=NULL ;
		if (isset($_GET["difficulty"])) {
			$difficulty=$_GET["difficulty"] ;
		}	
		$name=NULL ;
		if (isset($_GET["name"])) {
			$name=$_GET["name"] ;
		}
		$view=NULL ;
		if (isset($_GET["view"])) {
			$view=$_GET["view"] ;
		}
		if ($view!="grid") {
			$view="list" ;
		}
		
		$learningAreaArray=getLearningAreaArray($connection2) ;
		$authors=getAuthorsArray($connection2) ;
		$blocks=getBlocksArray($connection2) ;
		
		print "<h3>" ;
			print _("Filter") ;
		print "</h3>" ;
		print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse.php'>" ;
			print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
				?>
				<tr>
					<td> 
						<b><?php print _('Learning Area') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<select name="gibbonDepartmentID" id="gibbonDepartmentID" style="width: 302px">
							<option value=""></option>
							<?php
							$learningAreas=getLearningAreas($connection2, $guid) ;
							for ($i=0; $i<count($learningAreas); $i=$i+2) {
								if ($gibbonDepartmentID==$learningAreas[$i]) {
									print "<option selected value='" . $learningAreas[$i] . "'>" . _($learningAreas[($i+1)]) . "</option>" ;
								}
								else {
									print "<option value='" . $learningAreas[$i] . "'>" . _($learningAreas[($i+1)]) . "</option>" ;
								}
							}
							?>			
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Difficulty') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						$difficulties=getSettingByScope($connection2, "Free Learning", "difficultyOptions") ;
						print "<select name='difficulty' id='difficulty' style='width: 302px'>" ;
							print "<option value=''></option>" ;
							$difficultiesList=explode(",", $difficulties) ;
							foreach ($difficultiesList AS $difficultyOption) {
								$selected="" ;
								if ($difficulty==$difficultyOption) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $difficultyOption . "'>" . $difficultyOption . "</option>" ;
							}
						print "</select>" ;
						?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Name') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						print "<input name='name' value='" . $name . "' type='text' style='width: 300px'/>" ; 
						?>
					</td>
				</tr>
				<?php
			
				print "<tr>" ;
					print "<td class='right' colspan=2>" ;
						print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse.php'>" . _('Clear Filters') . "</a> " ;
						print "<input type='submit' value='" . _('Go') . "'>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
		print "</form>" ;
		
		
		print "<div class='linkTop' style='margin-top: 40px; margin-bottom: -35px'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=list'>" . _('List') . " <img style='margin-bottom: -5px' title='" . _('List') . "' src='./modules/Free Learning/img/iconList.png'/></a> " ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=grid'>" . _('Grid') . " <img style='margin-bottom: -5px' title='" . _('Grid') . "' src='./modules/Free Learning/img/iconGrid.png'/></a> " ;
			$mapLink=getSettingByScope($connection2, "Free Learning", "mapLink") ;
			if ($mapLink!="") {
				print "<a href='$mapLink' target='_blank'>" . _('Map') . " <img style='margin-bottom: -5px' title='" . _('Map') . "' src='./modules/Free Learning/img/iconMap.png'/></a> " ;
			}
		print "</div>" ;
		
		//Set pagination variable
		$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		//Search with filters applied
		try {
			$data=array() ;
			$sqlWhere="AND " ;
			if ($gibbonDepartmentID!="") {
				$data["gibbonDepartmentID"]=$gibbonDepartmentID ;
				$sqlWhere.="gibbonDepartmentIDList LIKE concat('%', :gibbonDepartmentID, '%') AND " ; 
			}
			if ($difficulty!="") {
				$data["difficulty"]=$difficulty ;
				$sqlWhere.="difficulty=:difficulty AND " ; 
			}
			if ($name!="") {
				$data["name"]=$name ;
				$sqlWhere.="freeLearningUnit.name LIKE concat('%', :name, '%') AND " ; 
			}
			if ($sqlWhere=="AND ") {
				$sqlWhere="" ;
			}
			else {
				$sqlWhere=substr($sqlWhere,0,-5) ;
			}
			$difficultyOrder="" ;
			if ($difficulties!=FALSE) {
				$difficultyOrder="FIELD(difficulty" ;
				$difficulties=explode(",", $difficulties) ;
				foreach ($difficulties AS $difficultyOption) {
					$difficultyOrder.=",'" . $difficultyOption . "'" ;
				}
				$difficultyOrder.="), " ;
			}
			if ($publicUnits=="Y" AND isset($_SESSION[$guid]["username"])==FALSE) {
				$sql="SELECT freeLearningUnit.*, NULL AS status FROM freeLearningUnit WHERE sharedPublic='Y' AND gibbonYearGroupIDMinimum IS NULL AND active='Y' $sqlWhere ORDER BY $difficultyOrder name DESC" ; 
			}
			else {
				if ($highestAction=="Browse Units_all") {
					$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
					$sql="SELECT freeLearningUnit.*, NULL AS status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' $sqlWhere ORDER BY $difficultyOrder name DESC" ; 
				}
				else if ($highestAction=="Browse Units_prerequisites") {
					$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
					$data["gibbonPersonID2"]=$_SESSION[$guid]["gibbonPersonID"] ;
					$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
					$sql="SELECT freeLearningUnit.*, freeLearningUnitStudent.status, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2 FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID2) LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID) JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID) WHERE active='Y' $sqlWhere AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber) ORDER BY $difficultyOrder name" ; 
				}
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
		
		print "<h3>" ;
			print _("Units") . " <span style='font-size: 65%; font-style: italics'> x" . $result->rowCount() . "</span>" ;
		print "</h3>" ;
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print _("There are no records to display.") ;
			print "</div>" ;
		}
		else {
			if ($view=="list") {
				if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name") ;
				}
		
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th style='width: 150px!important; text-align: center'>" ;
							print _("Name") . "</br>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _('Status') . "</span>" ;
						print "</th>" ;
						print "<th style='width: 100px!important'>" ;
							print _("Authors") . "<br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _('Learning Areas') . "</span>" ;
						print "</th>" ;
						print "<th style='max-width: 325px!important'>" ;
							print _("Difficulty") . "</br>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _('Blurb') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print _("Length") . "</br>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . _('Minutes') . "</span>" ;
						print "</th>" ;
						print "<th>" ;
							print _("Grouping") . "</br>" ;
						print "</th>" ;
						print "<th>" ;
							print _("Prerequisites") . "</br>" ;
						print "</th>" ;
						print "<th style='min-width: 70px'>" ;
							print _("Actions") ;
						print "</th>" ;
					print "</tr>" ;
				
					$count=0;
					$rowNum="odd" ;
					try {
						$resultPage=$connection2->prepare($sqlPage);
						$resultPage->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}		
					while ($row=$resultPage->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						if ($row["status"]=="Complete - Approved" OR $row["status"]=="Exempt") {
							$rowNum="current" ;
						}
						else if ($row["status"]=="Current" OR $row["status"]=="Complete - Pending") {
							$rowNum="warning" ;
						}
						$count++ ;
					
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td style='text-align: center; font-size: 125%'>" ;
								print "<div style='font-weight: bold; margin-top: 5px; margin-bottom: -6px ;'>" . $row["name"] . "</div><br/>" ;
								if ($row["logo"]==NULL) {
									print "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_125.jpg'/><br/>" ;
								}
								else {
									print "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='" . $row["logo"] . "'/><br/>" ;
								}
								print "<span style='font-size: 85%;'>" ;
									print $row["status"] ;
								print "</span>" ;
							print "</td>" ;
							print "<td>" ;
								foreach ($authors AS $author) {
									if ($author[0]==$row["freeLearningUnitID"]) {
										if ($author[3]=="") {
											print $author[1] . "<br/>" ;
										}
										else {
											print "<a target='_blank' href='" . $author[3] . "'>" . $author[1] . "</a><br/>" ;
										}
									}
								}
								if ($row["gibbonDepartmentIDList"]!="") {
									print "<span style='font-size: 85%;'>" ;
										$departments=explode(",", $row["gibbonDepartmentIDList"]) ;
										foreach ($departments AS $department) {
											print $learningAreaArray[$department] . "<br/>" ;
										}
									print "</span>" ;
								}
							print "</td>" ;
							print "<td>" ;
								print "<b>" . $row["difficulty"] . "</b><br/>" ;
								print "<div style='font-size: 100%; text-align: justify'>" ;
									print $row["blurb"] ;
								print "</div>" ;
							print "</td>" ;
							print "<td>" ;
								$timing=NULL ;
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
									print "<i>" . _('NA') . "</i>" ;
								}
								else {
									print $timing ;
								}
							print "</td>" ;print "<td>" ;
								if ($row["grouping"]!="") {
									$groupings=explode(",", $row["grouping"]) ;
									foreach ($groupings AS $grouping) {
										print ucwords($grouping) . "<br/>" ;
									}
								}
							print "</td>" ;
							print "<td>" ;
								$prerequisitesActive=prerequisitesRemoveInactive($connection2, $row["freeLearningUnitIDPrerequisiteList"]) ;
								if ($prerequisitesActive!=FALSE) {
									$prerequisites=explode(",", $prerequisitesActive) ;
									$units=getUnitsArray($connection2) ;
									foreach ($prerequisites AS $prerequisite) {
										print $units[$prerequisite][0] . "<br/>" ;
									}
								}
								else {
										print "<i>" . _('None') . "<br/></i>" ;
								}
								if ($highestAction=="Browse Units_prerequisites") {
									if ($prerequisitesActive!=FALSE) {
										$prerquisitesMet=prerquisitesMet($connection2, $_SESSION[$guid]["gibbonPersonID"], $prerequisitesActive) ;
										if ($prerquisitesMet) {
											print "<span style='font-weight: bold; color: #00cc00'>" . _("OK!") . "</span>" ;
										}
										else {
											print "<span style='font-weight: bold; color: #cc0000'>" . _("Not Met") . "</span>" ;
										}
									}
								}
							print "</td>" ;
							print "<td>" ;
								if ($highestAction=="Browse Units_all") {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
								}
								else if ($highestAction=="Browse Units_prerequisites") {
									if ($row["freeLearningUnitIDPrerequisiteList"]==NULL OR $row["freeLearningUnitIDPrerequisiteList"]=="") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
									}
									else {
										if ($prerquisitesMet) {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
										}
									}
								}
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
			
				if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name") ;
				}
			}
			else if ($view="grid") {
				print "<table cellspacing='0' style='width: 100%'>" ;
					$count=0;
					$columns=4 ;
					
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
						//Row header if needed
						if ($count%$columns==0) {
							print "<tr class='odd'>" ;
						}
						
						//Cell style
						$cellClass="" ;
						if ($row["status"]=="Complete - Approved" OR $row["status"]=="Exempt") {
							$cellClass="current" ;
						}
						else if ($row["status"]=="Current" OR $row["status"]=="Complete - Pending") {
							$cellClass="warning" ;
						}
						print "<td class='$cellClass' style='vertical-align: top ; text-align: center; font-size: 125%; width: " . (100/$columns) . "%'>" ;
							print "<div style='height: 40px; font-weight: bold; margin-top: 5px; margin-bottom: -6px ;'>" . $row["name"] . "</div><br/>" ;
							$title="Difficulty: " . $row["difficulty"] . "." ;
							$title.=" " . $row["blurb"] ;
							if ($row["logo"]==NULL) {
								print "<img title='" . htmlPrep($title) . "' style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_125.jpg'/><br/>" ;
							}
							else {
								print "<img title='" . htmlPrep($title) . "' style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='" . $row["logo"] . "'/><br/>" ;
							}
							
							//Actions
							$prerequisitesActive=prerequisitesRemoveInactive($connection2, $row["freeLearningUnitIDPrerequisiteList"]) ;
							if ($prerequisitesActive!=FALSE) {
								$prerequisites=explode(",", $prerequisitesActive) ;
								$units=getUnitsArray($connection2) ;

							}
							if ($highestAction=="Browse Units_prerequisites") {
								if ($prerequisitesActive!=FALSE) {
									$prerquisitesMet=prerquisitesMet($connection2, $_SESSION[$guid]["gibbonPersonID"], $prerequisitesActive) ;
								}
							}
							if ($highestAction=="Browse Units_all") {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
							}
							else if ($highestAction=="Browse Units_prerequisites") {
								if ($row["freeLearningUnitIDPrerequisiteList"]==NULL OR $row["freeLearningUnitIDPrerequisiteList"]=="") {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
								}
								else {
									if ($prerquisitesMet) {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
									}
								}
							}
						print "</td>" ;
						
						if ($count%$columns==($columns-1)) {
							print "</tr>" ;
						}
						$count++ ;
					}
		
					for ($i=0;$i<$columns-($count%$columns);$i++) {
						print "<td></td>" ;
					}
	
					if ($count%$columns!=0) {
						print "</tr>" ;
					}
				print "</table>" ;	
			}
		}
	}
}	
?>