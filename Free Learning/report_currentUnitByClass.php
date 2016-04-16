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

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_currentUnitByClass.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Current Unit By Class') . "</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print __($guid, "Choose Class") ;
	print "</h2>" ;
	
	$gibbonCourseClassID=NULL ;
	if (isset($_GET["gibbonCourseClassID"])) {
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	}
	$sort=NULL ;
	if (isset($_GET["sort"])) {
		$sort=$_GET["sort"] ;
	}
	
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Class') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonCourseClassID">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort as class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonCourseClassID==$rowSelect["gibbonCourseClassID"]) {
								print "<option selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php print __($guid, 'Sort By') ?></b><br/>
				</td>
				<td class="right">
					<select name="sort" style="width: 300px">
						<option value="unit" <?php if($sort == 'unit'){echo("selected");}?>>Unit</option>
						<option value="student" <?php if($sort == 'student'){echo("selected");}?>>Student</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_currentUnitByClass.php">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonCourseClassID!="") {
		print "<h2>" ;
		print __($guid, "Report Data") ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort as class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			print "<p style='margin-bottom: 0px'><b>" . __($guid, 'Class') . "</b>: " . $row["course"] . "." . $row["class"] . "</p>" ;
		
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				if ($sort=="student") {
					$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnit.freeLearningUnitID, freeLearningUnit.name, timestampJoined, collaborationKey, freeLearningUnitStudent.status FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role='Student') LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Complete - Pending' OR freeLearningUnitStudent.status='Evidence Not Approved')) LEFT JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName, freeLearningUnit.name" ;
				}
				else {
					$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnit.freeLearningUnitID, freeLearningUnit.name, timestampJoined, collaborationKey, freeLearningUnitStudent.status FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role='Student') LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Complete - Pending' OR freeLearningUnitStudent.status='Evidence Not Approved')) LEFT JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID ORDER BY freeLearningUnit.name, collaborationKey, surname, preferredName" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		
			print "<div class='linkTop'>" ;
			print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_students_byRollGroup_print.php&gibbonCourseClassID=$gibbonCourseClassID'>" .  __($guid, 'Print') . "<img style='margin-left: 5px' title='" . __($guid, 'Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
			print "</div>" ;
	
			print "<table class='mini' cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Number") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Student") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Group") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Unit") ;
						print "<span style='font-size: 85%; font-style: italic'>" . __($guid, "Status") . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Date Started") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Days Since Started") ;
					print "</th>" ;
				print "</tr>" ;
			
				$count=0;
				$rowNum="odd" ;
				$group=0 ;	
				$collaborationKeys=array() ;
				while ($row=$result->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;
				
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print $count ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "'>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</a>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["collaborationKey"]!="") {
								if (isset($collaborationKeys[$row["collaborationKey"]])==FALSE) {
									$group++ ;
									$collaborationKeys[$row["collaborationKey"]]=$group ;
								}
								print $collaborationKeys[$row["collaborationKey"]] ;
							}
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=&difficulty=&name='>" . $row["name"] . "</a>" ;
							print "<br/><span style='font-size: 85%; font-style: italic'>" . $row["status"] . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["timestampJoined"]!="") {
								print dateConvertBack($guid, substr($row["timestampJoined"], 0, 10)) ;
							}
						print "</td>" ;
						print "<td>" ;
							if ($row["timestampJoined"]!="") {
								print round((time()-strtotime($row["timestampJoined"]))/(60*60*24)) ;
							}
						print "</td>" ;
						
					print "</tr>" ;
				}
				if ($count==0) {
					print "<tr class=$rowNum>" ;
						print "<td colspan=3>" ;
							print __($guid, "There are no records to display.") ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;

		}
	}
}
?>