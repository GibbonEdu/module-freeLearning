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

$freeLearningUnitID=$_GET["freeLearningUnitID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
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
		if (empty($_POST)) {
			$URL.="&updateReturn=fail5" ;
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
			$sharedPublic=NULL ;
			if (isset($_POST["sharedPublic"])) {
				$sharedPublic=$_POST["sharedPublic"] ;
			}
			$active=$_POST["active"] ;
			$gibbonYearGroupIDMinimum=NULL ;
			if ($_POST["gibbonYearGroupIDMinimum"]!="") {
				$gibbonYearGroupIDMinimum=$_POST["gibbonYearGroupIDMinimum"] ;
			}
			$grouping="" ;
			if (isset($_POST["individual"])) {
				if ($_POST["individual"]=="on") {
					$grouping.="individual," ;
				}
			}
			if (isset($_POST["pairs"])) {
				if ($_POST["pairs"]=="on") {
					$grouping.="pairs," ;
				}
			}
			if (isset($_POST["threes"])) {
				if ($_POST["threes"]=="on") {
					$grouping.="threes," ;
				}
			}
			if (isset($_POST["fours"])) {
				if ($_POST["fours"]=="on") {
					$grouping.="fours," ;
				}
			}
			if (isset($_POST["fives"])) {
				if ($_POST["fives"]=="on") {
					$grouping.="fives," ;
				}
			}
			if (substr($grouping, -1)==",") {
				$grouping=substr($grouping, 0, -1) ;
			}
			$freeLearningUnitIDPrerequisiteList=NULL ;
			if (isset($_POST["prerequisites"])) {
				$prerequisites=$_POST["prerequisites"] ;
				foreach ($prerequisites AS $prerequisite) {
					$freeLearningUnitIDPrerequisiteList.=$prerequisite . "," ;
				}	
				$freeLearningUnitIDPrerequisiteList=substr($freeLearningUnitIDPrerequisiteList,0,-1) ;
			}
			$outline=$_POST["outline"] ;
			
			if ($name=="" OR $difficulty=="" OR $active=="") {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				$partialFail=FALSE ;
				
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
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}

				if ($result->rowCount()!=1) {
					//Fail 4
					$URL.="&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					$row=$result->fetch() ;
					
					//Move attached file, if there is one
					$time=time() ;
					if ($_FILES['file']["tmp_name"]!="") {
						//Check for folder in uploads based on today's date
						$path=$_SESSION[$guid]["absolutePath"] ;
						if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
							mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
						}
						$unique=FALSE;
						$count=0 ;
						while ($unique==FALSE AND $count<100) {
							$suffix=randomPassword(16) ;
							$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
							if (!(file_exists($path . "/" . $attachment))) {
								$unique=TRUE ;
							}
							$count++ ;
						}
					
						if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment))) {
							//Fail 5
							$URL.="&updateReturn=fail5" ;
							header("Location: {$URL}");
						}
					}
					else {
						$attachment=$row["logo"] ;
					}
				
					//Write to database
					try {
						$data=array("name"=>$name, "logo"=>$attachment, "difficulty"=>$difficulty, "blurb"=>$blurb, "license"=>$license, "sharedPublic"=>$sharedPublic, "active"=>$active, "gibbonYearGroupIDMinimum"=>$gibbonYearGroupIDMinimum, "grouping"=>$grouping, "gibbonDepartmentIDList"=>$gibbonDepartmentIDList, "freeLearningUnitIDPrerequisiteList"=>$freeLearningUnitIDPrerequisiteList, "outline"=>$outline, "freeLearningUnitID"=>$freeLearningUnitID); 
						$sql="UPDATE freeLearningUnit SET name=:name, logo=:logo, difficulty=:difficulty, blurb=:blurb, license=:license, sharedPublic=:sharedPublic, active=:active, gibbonYearGroupIDMinimum=:gibbonYearGroupIDMinimum, grouping=:grouping, gibbonDepartmentIDList=:gibbonDepartmentIDList, freeLearningUnitIDPrerequisiteList=:freeLearningUnitIDPrerequisiteList, outline=:outline WHERE freeLearningUnitID=:freeLearningUnitID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Write author to database
					try {
						$data=array("freeLearningUnitID"=>$freeLearningUnitID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT * FROM freeLearningUnitAuthor WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPersonID=:gibbonPersonID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
					if ($result->rowCount()<1) {
						try {
							$data=array("freeLearningUnitID"=>$freeLearningUnitID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sql="INSERT INTO freeLearningUnitAuthor SET freeLearningUnitID=:freeLearningUnitID, gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
					}
					
					//Delete all outcomes
					try {
						$dataDelete=array("freeLearningUnitID"=>$freeLearningUnitID);  
						$sqlDelete="DELETE FROM freeLearningUnitOutcome WHERE freeLearningUnitID=:freeLearningUnitID" ;
						$resultDelete=$connection2->prepare($sqlDelete);
						$resultDelete->execute($dataDelete);  
					}
					catch(PDOException $e) { 
						//Fail2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					//Insert outcomes
					$count=0 ;
					if (isset($_POST["outcomeorder"])) {
						if (count($_POST["outcomeorder"])>0) {
							foreach ($_POST["outcomeorder"] AS $outcome) {
								if ($_POST["outcomegibbonOutcomeID$outcome"]!="") {
									try {
										$dataInsert=array("freeLearningUnitID"=>$freeLearningUnitID, "gibbonOutcomeID"=>$_POST["outcomegibbonOutcomeID$outcome"], "content"=>$_POST["outcomecontents$outcome"], "count"=>$count);  
										$sqlInsert="INSERT INTO freeLearningUnitOutcome SET freeLearningUnitID=:freeLearningUnitID, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count" ;
										$resultInsert=$connection2->prepare($sqlInsert);
										$resultInsert->execute($dataInsert);
									}
									catch(PDOException $e) {
										print $e ;
										$partialFail=true ;
									}
								}
								$count++ ;
							}	
						}
					}
					
					//Update blocks
					$order="" ;
					if (isset($_POST["order"])) {
						$order=$_POST["order"] ;
					}
					$sequenceNumber=0 ;
					$dataRemove=array() ;
					$whereRemove="" ;
					if (count($order)<0) {
						//Fail 3
						$URL.="&addReturn=fail3" ;
						header("Location: {$URL}");
					}
					else {
						if (is_array($order)) {
							foreach ($order as $i) {
								$title="";
								if ($_POST["title$i"]!="Block $i") {
									$title=$_POST["title$i"] ;
								}
								$type2="";
								if ($_POST["type$i"]!="type (e.g. discussion, outcome)") {
									$type2=$_POST["type$i"];
								}
								$length="";
								if ($_POST["length$i"]!="length (min)") {
									$length=$_POST["length$i"];
								}
								$contents=$_POST["contents$i"];
								$teachersNotes=$_POST["teachersNotes$i"];
								$freeLearningUnitBlockID=@$_POST["freeLearningUnitBlockID$i"];
								
								if ($freeLearningUnitBlockID!="") {
									try {
										$dataBlock=array("freeLearningUnitID"=>$freeLearningUnitID, "title"=>$title, "type"=>$type2, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "sequenceNumber"=>$sequenceNumber, "freeLearningUnitBlockID"=>$freeLearningUnitBlockID); 
										$sqlBlock="UPDATE freeLearningUnitBlock SET freeLearningUnitID=:freeLearningUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber WHERE freeLearningUnitBlockID=:freeLearningUnitBlockID" ;
										$resultBlock=$connection2->prepare($sqlBlock);
										$resultBlock->execute($dataBlock);
									}
									catch(PDOException $e) { 
										$partialFail=TRUE ;
									}
									$dataRemove["freeLearningUnitBlockID$sequenceNumber"]=$freeLearningUnitBlockID ;
									$whereRemove.="AND NOT freeLearningUnitBlockID=:freeLearningUnitBlockID$sequenceNumber " ;
								}
								else {
									try {
										$dataBlock=array("freeLearningUnitID"=>$freeLearningUnitID, "title"=>$title, "type"=>$type2, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotes, "sequenceNumber"=>$sequenceNumber); 
										$sqlBlock="INSERT INTO freeLearningUnitBlock SET freeLearningUnitID=:freeLearningUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber" ;
										$resultBlock=$connection2->prepare($sqlBlock);
										$resultBlock->execute($dataBlock);
									}
									catch(PDOException $e) {
										print $e->getMessage() ; 
										$partialFail=TRUE ;
									}
									$dataRemove["freeLearningUnitBlockID$sequenceNumber"]=$connection2->lastInsertId() ;
									$whereRemove.="AND NOT freeLearningUnitBlockID=:freeLearningUnitBlockID$sequenceNumber " ;
								}
								
								$sequenceNumber++ ;
							}
						}
					}
					
					//Remove orphaned blocks
					if ($whereRemove!="(") {
						try {
							$dataRemove["freeLearningUnitID"]=$freeLearningUnitID ; 
							$sqlRemove="DELETE FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID $whereRemove" ;
							$resultRemove=$connection2->prepare($sqlRemove);
							$resultRemove->execute($dataRemove);
						}
						catch(PDOException $e) { 
							print $e->getMessage() ;
							$partialFail=TRUE ;
						}
					}
					

					if ($partialFail) {
						//Fail 6
						$URL.="&updateReturn=fail6" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URL.="&updateReturn=success0" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>