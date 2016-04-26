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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/units_browse_details_enrolMultiple.php&freeLearningUnitID=" . $_GET["freeLearningUnitID"] . "&gibbonDepartmentID=" . $_GET["gibbonDepartmentID"] . "&difficulty=" . $_GET["difficulty"] . "&name=" . $_GET["name"] . "&showInactive=" . $_GET["showInactive"] . "&tab=1" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_browse_details.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, "/modules/Free Learning/units_browse_details.php", $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		$freeLearningUnitID="" ;
		if (isset($_GET["freeLearningUnitID"])) {
			$freeLearningUnitID=$_GET["freeLearningUnitID"] ;
		}

		if ($freeLearningUnitID=="") {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			try {
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
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
			}

			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				$row=$result->fetch() ;

				//Proceed!
				if (isset($_POST["gibbonPersonIDMulti"])) {
					$gibbonPersonIDMulti=$_POST["gibbonPersonIDMulti"] ; 
				}
				else {
					$gibbonPersonIDMulti=NULL ; 
				}
				$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ; 
				$status=$_POST["status"] ; 
		
				if (is_null($gibbonPersonIDMulti)==TRUE OR $status=="" OR $gibbonCourseClassID=="") {
					//Fail 3
					$URL.="&addReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					$proceed=FALSE ;
					//Check to see if we can set enrolmentType to "staffEdit" based on access to Manage Units_all
					$manageAll=isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage.php", "Manage Units_all") ;
					if ($manageAll==TRUE) {
						$proceed=TRUE ;
					}
					else {
						//Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s)
						$learningAreas=getLearningAreas($connection2, $guid, TRUE) ;
						if ($learningAreas!="") {
							for ($i=0; $i<count($learningAreas); $i=$i+2) {
								if (is_numeric(strpos($row["gibbonDepartmentIDList"], $learningAreas[$i]))) {
									$proceed=TRUE ;
								}
							}
						}
					}
				
					if ($proceed==FALSE) {
						//Fail 0
						$URL.="&updateReturn=fail0" ;
						header("Location: {$URL}");
					}
					else {
						$partialFail=FALSE ;
		
						foreach ($gibbonPersonIDMulti AS $gibbonPersonID) {
							//Write to database
							try {
								$data=array("gibbonPersonID"=>$gibbonPersonID, "freeLearningUnitID"=>$freeLearningUnitID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "grouping"=>"Individual", "status"=>$status); 
								$sql="INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonID, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonCourseClassID=:gibbonCourseClassID, grouping=:grouping, status=:status" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;	
							}
						}
		
						if ($partialFail==TRUE) {
							//Fail 5
							$URL.="&addReturn=fail5" ;
							header("Location: {$URL}");
						}
						else {
							//Success 0
							$URL.="&addReturn=success0" ;
							header("Location: {$URL}");
						}
					}
				}
			}
		}
	}
}
?>