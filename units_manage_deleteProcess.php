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

$freeLearningUnitID=$_POST["freeLearningUnitID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/units_manage_delete.php&freeLearningUnitID=$freeLearningUnitID" ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/units_manage.php&" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage_delete.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Check existence of specified unit
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
			//Fail 2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 4
			$URL.="&deleteReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
				$sql="DELETE FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			try {
				$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
				$sql="DELETE FROM freeLearningUnitOutcome WHERE freeLearningUnitID=:freeLearningUnitID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			try {
				$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
				$sql="DELETE FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			//Success 0
			$URLDelete=$URLDelete . "&deleteReturn=success0" ;
			header("Location: {$URLDelete}");
		}
	}
}
?>