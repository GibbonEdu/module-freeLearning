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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_manage_add.php" ;
$URLSuccess=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_manage_edit.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		if (!(isset($_POST))) {
			//Fail 5
			$URL.="&addReturn=fail5" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			//Validate Inputs
			$name=$_POST["name"] ;
			$difficulty=$_POST["difficulty"] ;
			$blurb=$_POST["blurb"] ;
			$count=$_POST["count"] ;
			$gibbonDepartmentIDList=NULL ;
			for ($i=0; $i<$count; $i++) {
				if (isset($_POST["gibbonDepartmentIDCheck$i"])) {
					if ($_POST["gibbonDepartmentIDCheck$i"]=="on") {
						$gibbonDepartmentIDList=$gibbonDepartmentIDList . $_POST["gibbonDepartmentID$i"] . "," ;
					}
				}
			}
			$gibbonDepartmentIDList=substr($gibbonDepartmentIDList,0,(strlen($gibbonDepartmentIDList)-1)) ;
			if ($gibbonDepartmentIDList=="") {
				$gibbonDepartmentIDList=NULL ;
			}
			$license=$_POST["license"] ;
			$sharedPublic=$_POST["sharedPublic"] ;
			$active=$_POST["active"] ;
			$outline=$_POST["outline"] ;
			
			if ($name=="" OR $difficulty=="" OR $sharedPublic=="" OR $active=="") {
				//Fail 3
				$URL.="&addReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				$partialFail=FALSE ;
				
				//Lock tables
				try {
					$sql="LOCK TABLES freeLearningUnit WRITE" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}	
					
				//Get next autoincrement
				try {
					$sqlAI="SHOW TABLE STATUS LIKE 'freeLearningUnit'";
					$resultAI=$connection2->query($sqlAI);   
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}		

				$rowAI=$resultAI->fetch();
				$AI=str_pad($rowAI['Auto_increment'], 10, "0", STR_PAD_LEFT) ;
				
				
				//Write to database
				try {
					$data=array("name"=>$name, "difficulty"=>$difficulty, "blurb"=>$blurb, "license"=>$license, "sharedPublic"=>$sharedPublic, "active"=>$active, "gibbonDepartmentIDList"=>$gibbonDepartmentIDList, "outline"=>$outline, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "timestamp"=>date("Y-m-d H:i:s")); 
					$sql="INSERT INTO freeLearningUnit SET name=:name, difficulty=:difficulty, blurb=:blurb, license=:license, sharedPublic=:sharedPublic, active=:active, gibbonDepartmentIDList=:gibbonDepartmentIDList, outline=:outline, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
		
				//Unlock module table
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { }		
				
				if ($partialFail==TRUE) {
					//Fail 6
					$URL.="&addReturn=fail6" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URLSuccess=$URLSuccess . "&addReturn=success0&freeLearningUnitID=$AI" ;
					header("Location: {$URLSuccess}") ;
				}
			}
		}
	}
}
?>