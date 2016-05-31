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

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_workPendingApproval.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Work Pending Approval') . "</div>" ;
	print "</div>" ;

	print "<p>" ;
		print __($guid, 'This report shows all work that is complete, but pending approval, in all of your classes.') ;
	print "<p>" ;

	//List students whose status is Current or Complete - Pending
	try {
		$dataClass=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
		$sqlClass="SELECT freeLearningUnit.name AS unit, freeLearningUnit.freeLearningUnitID, gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnitStudent.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassPerson.role='Teacher' AND gibbonPerson.status='Full' AND freeLearningUnitStudent.status='Complete - Pending' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL OR dateEnd>='" . date("Y-m-d") . "') AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort, freeLearningUnit.name, surname, preferredName" ;
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
					<?php print __($guid, 'Class') ?><br/>
				</th>
				<th>
					<?php print __($guid, 'Unit') ?><br/>
				</th>
				<th>
					<?php print __($guid, 'Student') ?><br/>
				</th>
				<th>
					<?php print __($guid, 'Status') ?><br/>
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
					<?php
					print "<td>" ;
						if ($rowClass["course"]!="" AND $rowClass["class"]!="") {
							print $rowClass["course"] . "." . $rowClass["class"] ;
						} else {
							print "<i>" . __($guid, 'NA') . "</i>" ;
						}
					print "</td>" ;
					?>
					<td>
						<?php
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=" . $rowClass["freeLearningUnitID"] . "&tab=1&sidebar=true'>" . $rowClass["unit"] . "</a>" ;
						?>
					</td>
					<td>
						<?php print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowClass["gibbonPersonID"] . "'>" . formatName("", $rowClass["preferredName"], $rowClass["surname"], "Student", true) . "</a>" ?><br/>
					</td>
					<td>
						<?php print $rowClass["status"] ?><br/>
					</td>
				<?php
				echo "</tr>" ;
			}
			?>
		</table>
		<?php
	}
}
?>
