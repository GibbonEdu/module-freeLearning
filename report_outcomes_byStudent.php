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

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_outcomes_byStudent.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Outcomes By Student') . "</div>" ;
	print "</div>" ;
	
	$schoolType=getSettingByScope($connection2, "Free Learning", "schoolType" ) ;
	
	print "<h2>" ;
	print _("Choose Student") ;
	print "</h2>" ;

	$gibbonPersonID=NULL ;
	if (isset($_GET["gibbonPersonID"])) {
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
	}
	?>

	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Student') ?> *</b><br/>
				</td>
				<td class="right">
					<?php
						if ($schoolType=="Physical") {
							?>
							<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
								<option></option>
								<optgroup label='--<?php print _('Students by Roll Group') ?>--'>
									<?php
									try {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["gibbonPersonID"]==$gibbonPersonID) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
									}
									?>
								</optgroup>
								<optgroup label='--<?php print _('Students by Name') ?>--'>
									<?php
									try {
										$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["name"]) . ")</option>" ;
									}
									?>
								</optgroup>
							</select>
							<?php
						}
						else {
							?>
							<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
								<option></option>
								<?php
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname, username FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonRole.gibbonRoleID LIKE concat( '%', gibbonPerson.gibbonRoleIDAll, '%' ) AND category='Student') WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["gibbonPersonID"]==$gibbonPersonID) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["username"] . ")</option>" ;
									}
									?>
							</select>
							<?php
						}
						?>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_outcomes_byStudent.php">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

	if ($gibbonPersonID!="") {
		$output="" ;
		print "<h2>" ;
		print _("Report Data") ;
		print "</h2>" ;
	
		//Check the years groups the student has been enroled into
		$proceed=TRUE ;
		$gibbonYearGroupIDWhere='' ;
		if ($schoolType=="Physical") {
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT gibbonRollGroup.name AS rollGroup, gibbonSchoolYear.name AS schoolYear, gibbonStudentEnrolment.gibbonYearGroupID FROM gibbonStudentEnrolment JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }

			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
					print _("Your request failed due to a database error.") ;
				print "</div>" ;
				$proceed=FALSE ; 
			}
			else {
				//Make list of year groups for where
				$gibbonYearGroupIDWhere="(" ;
				while ($row=$result->fetch()) {
					$gibbonYearGroupIDWhere.="gibbonYearGroupIDList LIKE '%" . $row["gibbonYearGroupID"] . "%' OR " ;
				}
				$gibbonYearGroupIDWhere=substr($gibbonYearGroupIDWhere, 0, -4) . ")" ;
			}
		}
			
		if ($proceed==TRUE) {	
			//Create array of each outcome the student has met in Free Learning
			try {
				$dataFreeLearning=array("gibbonPersonIDStudent"=>$gibbonPersonID); 
				$sqlFreeLearning="SELECT gibbonOutcomeID, freeLearningUnit.name FROM freeLearningUnit 
					JOIN freeLearningUnitOutcome ON (freeLearningUnitOutcome.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) 
					JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
					WHERE (status='Complete - Approved' OR status='Exempt') AND gibbonPersonIDStudent=:gibbonPersonIDStudent
					ORDER BY gibbonOutcomeID" ;
				$resultFreeLearning=$connection2->prepare($sqlFreeLearning);
				$resultFreeLearning->execute($dataFreeLearning);
			}
			catch(PDOException $e) { }
			
			$outcomesMet=array() ;
			$outcomesNotMet=array() ;
			$outcomesNotMetCount=0 ;
			while ($rowFreeLearning=$resultFreeLearning->fetch()) {
				if (isset($outcomesMet[$rowFreeLearning["gibbonOutcomeID"]][0])) {
					$outcomesMet[$rowFreeLearning["gibbonOutcomeID"]][0]++ ;
					$outcomesMet[$rowFreeLearning["gibbonOutcomeID"]][1].=", " . $rowFreeLearning["name"] ;
				}
				else {
					$outcomesMet[$rowFreeLearning["gibbonOutcomeID"]][0]=1 ;
					
					$outcomesMet[$rowFreeLearning["gibbonOutcomeID"]][1]=$rowFreeLearning["name"] ;
				}
			}
			
			//Get all school and department outcomes for the students' years in school and store in variable
			$output='' ;
			$output.="<h4>" ;
			$output.=_("Outcome Completion") ;
			$output.="</h4>" ;
			try {
				$dataOutcomes=array("gibbonPersonID"=>$gibbonPersonID); 
				if ($schoolType=="Online") {
					$sqlOutcomes="SELECT gibbonOutcome.*, gibbonDepartment.name AS department FROM gibbonOutcome LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' ORDER BY field(Scope,'School','Learning Area'), category, name" ;
				}
				else {
					$sqlOutcomes="SELECT gibbonOutcome.*, gibbonDepartment.name AS department FROM gibbonOutcome LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE $gibbonYearGroupIDWhere AND active='Y' ORDER BY field(Scope,'School','Learning Area'), category, name" ;
				}
				$resultOutcomes=$connection2->prepare($sqlOutcomes);
				$resultOutcomes->execute($dataOutcomes);
			}
			catch(PDOException $e) { }
			
			if ($resultOutcomes->rowCount()<1) {
				$output.="<div class='error'>" ;
				$output.=_("There are no records to display.") ;
				$output.="</div>" ;
			}
			else {
				$output.="<table cellspacing='0' style='width: 100%'>" ;
					$output.="<tr class='head'>" ;
						$output.="<th>" ;
							$output.=_("Scope") . "<br/>" ;
							$output.="<span style='font-size: 85%; font-style: italic'>" . _("Category") . "</span>" ;
						$output.="</th>" ;
						$output.="<th>" ;
							$output.=_("Name") ;
						$output.="</th>" ;
						$output.="<th>" ;
							$output.=_("Status") ;
						$output.="</th>" ;
					$output.="</tr>" ;
				
					$count=0;
					while ($rowOutcomes=$resultOutcomes->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
					
						//COLOR ROW BY STATUS!
						$output.="<tr class=$rowNum>" ;
							$output.="<td>" ;
								if ($rowOutcomes["scope"]=="School") {
									$output.="School" ;
								}
								else if ($rowOutcomes["department"]!="") {
									$output.=$rowOutcomes["department"] ;
								}
								if ($rowOutcomes["category"]!="") {
									$output.="<br/><span style='font-size: 85%; font-style: italic'>" . $rowOutcomes["category"] . "</span>" ;
								}
							$output.="</td>" ;
							$output.="<td>" ;
								$output.="<b>" . $rowOutcomes["name"] . "</b><br/>" ;
							$output.="</td>" ;
							$output.="<td>" ;
								if (isset($outcomesMet[$rowOutcomes["gibbonOutcomeID"]][0])==FALSE) {
									$output.="<img title='" . _('Outcome not met') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
									$outcomesNotMet[$outcomesNotMetCount]=$rowOutcomes["gibbonOutcomeID"] ;
									$outcomesNotMetCount++ ;
								}
								else {
									$output.="<img title='" . _('Outcome met in units:') . " " . htmlPrep($outcomesMet[$rowOutcomes["gibbonOutcomeID"]][1]) . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> x" . $outcomesMet[$rowOutcomes["gibbonOutcomeID"]][0] ;
								}
							$output.="</td>" ;
						$output.="</tr>" ;
					
						$count++ ;
					}
				$output.="</table>" ;
			}

			//Recommend units based on missing outcomes, year group and prerequisites met
			if (count($outcomesNotMet)>0) {
				$outcomesNotMetWhere="(" ;
				foreach ($outcomesNotMet AS $outcomeNotMet) {
					$outcomesNotMetWhere.="gibbonOutcomeID=$outcomeNotMet OR " ;
				}
				$outcomesNotMetWhere=substr($outcomesNotMetWhere, 0, -4) . ")" ;
				
				try {
					$dataRecommend["gibbonPersonID"]=$gibbonPersonID ;
					$dataRecommend["gibbonPersonID2"]=$gibbonPersonID ;
					$dataRecommend["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
					$sqlRecommend="SELECT freeLearningUnitStudent.status, freeLearningUnit.freeLearningUnitID, freeLearningUnit.*, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2 
						FROM freeLearningUnit 
						JOIN freeLearningUnitOutcome ON (freeLearningUnitOutcome.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
						LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID2) 
						LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID) 
						JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID) 
						JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID) 
						WHERE $outcomesNotMetWhere AND (status IS NULL OR NOT status='Current') AND active='Y' AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber) 
						ORDER BY RAND() LIMIT 0, 3" ; 
					$resultRecommend=$connection2->prepare($sqlRecommend);
					$resultRecommend->execute($dataRecommend);
				}
				catch(PDOException $e) { print $e->getMessage() ; }
				
				if ($resultRecommend->rowCount()>0) {
					$learningAreaArray=getLearningAreaArray($connection2) ;
					$authors=getAuthorsArray($connection2) ;
					$blocks=getBlocksArray($connection2) ;
		
					print "<h4>" ;
					print _("Recommended Units") ;
					print "</h4>" ;
					print "<p>" ;
					print _("The units below (up to a total of 3) are chosen at random from a list of units that have outcomes this student has not met, but can do based on year group.") ;
					print "</p>" ;
					
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th style='width: 150px!important; text-align: center'>" ;
								print _("Name") . "</br>" ;
							print "</th>" ;
							print "<th style='width: 100px!important'>" ;
								print _("Authors") . "<br/>" ;
								print "<span style='font-size: 85%; font-style: italic'>" . _('Learning Areas') . "</span>" ;
							print "</th>" ;
							print "<th style='max-width: 325px!important'>" ;
								print _("Difficulty") . "</br>" ;
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
						while ($rowRecommend=$resultRecommend->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
					
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td style='text-align: center; font-size: 125%'>" ;
									print $rowRecommend["status"] ;
									print "<div style='font-weight: bold; margin-top: 5px; margin-bottom: -6px ;'>" . $rowRecommend["name"] . "</div><br/>" ;
									if ($rowRecommend["logo"]==NULL) {
										print "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_125.jpg'/><br/>" ;
									}
									else {
										print "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='" . $rowRecommend["logo"] . "'/><br/>" ;
									}
								print "</td>" ;
								print "<td>" ;
									foreach ($authors AS $author) {
										if ($author[0]==$rowRecommend["freeLearningUnitID"]) {
											if ($author[3]=="") {
												print $author[1] . "<br/>" ;
											}
											else {
												print "<a target='_blank' href='" . $author[3] . "'>" . $author[1] . "</a><br/>" ;
											}
										}
									}
									if ($rowRecommend["gibbonDepartmentIDList"]!="") {
										print "<span style='font-size: 85%;'>" ;
											$departments=explode(",", $rowRecommend["gibbonDepartmentIDList"]) ;
											foreach ($departments AS $department) {
												print $learningAreaArray[$department] . "<br/>" ;
											}
										print "</span>" ;
									}
								print "</td>" ;
								print "<td>" ;
									print "<b>" . $rowRecommend["difficulty"] . "</b><br/>" ;
								print "</td>" ;
								print "<td>" ;
									$timing=NULL ;
									if ($blocks!=FALSE) {
										foreach ($blocks AS $block) {
											if ($block[0]==$rowRecommend["freeLearningUnitID"]) {
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
									if ($rowRecommend["grouping"]!="") {
										$groupings=explode(",", $rowRecommend["grouping"]) ;
										foreach ($groupings AS $grouping) {
											print ucwords($grouping) . "<br/>" ;
										}
									}
								print "</td>" ;
								print "<td>" ;
									$prerequisitesActive=prerequisitesRemoveInactive($connection2, $rowRecommend["freeLearningUnitIDPrerequisiteList"]) ;
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
								if ($prerequisitesActive!=FALSE) {
									$prerquisitesMet=prerquisitesMet($connection2, $gibbonPersonID, $prerequisitesActive) ;
									if ($prerquisitesMet) {
										print "<span style='font-weight: bold; color: #00cc00'>" . _("OK!") . "</span>" ;
									}
									else {
										print "<span style='font-weight: bold; color: #cc0000'>" . _("Not Met") . "</span>" ;
									}
								}
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $rowRecommend["freeLearningUnitID"] . "&gibbonDepartmentID=&difficulty=&name='><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
				}
			}
			
			//Output table of met outcomes
			print $output ;
		}
	}
}
?>