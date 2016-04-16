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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/units_browse_details.php&freeLearningUnitID=" . $_POST["freeLearningUnitID"] . "&sidebar=true&tab=1" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_browse_details.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		if (empty($_POST)) {
			//Fail 6
			$URL.="&updateReturn=fail6" ;
			header("Location: {$URL}");
		}
		else {
			$schoolType=getSettingByScope($connection2, "Free Learning", "schoolType" ) ;

			$freeLearningUnitID=$_POST["freeLearningUnitID"] ;
			$freeLearningUnitStudentID=$_POST["freeLearningUnitStudentID"] ;
		
			if ($freeLearningUnitID=="" OR $freeLearningUnitStudentID=="") {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				try {
					$data=array("freeLearningUnitID"=>$freeLearningUnitID, "freeLearningUnitStudentID"=>$freeLearningUnitStudentID); 
					$sql="SELECT * FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID AND (status='Current' OR status='Evidence Not Approved')" ; 
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
	
				if ($result->rowCount()!=1) {
					//Fail 2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				else {
					//Proceed!
					$row=$result->fetch() ;
					$name=$row["name"] ;
					
					//Get Inputs
					$status='Complete - Pending' ;
					$commentStudent=$_POST["commentStudent"] ;
					$type=$_POST["type"] ;
					$link=$_POST["link"] ;
					$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
						
					//Validation
					if ($commentStudent=="" OR $type=="" OR ($_FILES['file']["name"]=="" AND $link=="")) {
						//Fail 3
						$URL.="&updateReturn=fail3" ;
						header("Location: {$URL}");
					}
					else {
						$partialFail=FALSE ;
						if ($type=="Link") {
							if (substr($link, 0, 7)!="http://" AND substr($link, 0, 8)!="https://" ) {
								$partialFail=TRUE ;	
							}
							else {
								$location=$link ;
							}
						}
						if ($type=="File") {
							//Check extension to see if allow
							try {
								@$extension=end(explode(".", $_FILES['file']["name"]));
								$dataExt=array("extension"=>$extension); 
								$sqlExt="SELECT * FROM gibbonFileExtension WHERE extension=:extension";
								$resultExt=$connection2->prepare($sqlExt);
								$resultExt->execute($dataExt);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
							
							if ($resultExt->rowCount()!=1) {
								$partialFail=TRUE ;
							}
							else {
								//Attempt file upload
								$time=time() ;
								if ($_FILES['file']["tmp_name"]!="") {
									//Check for folder in uploads based on today's date
									$path=$_SESSION[$guid]["absolutePath"] ; ;
									if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
										mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
									}
									$unique=FALSE;
									$count=0 ;
									while ($unique==FALSE AND $count<100) {
										$suffix=randomPassword(16) ;
										$location="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $_SESSION[$guid]["username"] . "_" . preg_replace("/[^a-zA-Z0-9]/", "", $row["name"]) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
										if (!(file_exists($path . "/" . $location))) {
											$unique=TRUE ;
										}
										$count++ ;
									}
									
									if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $location))) {
										//Fail 5
										$URL.="&addReturn=fail3" ;
										header("Location: {$URL}");
									}
								}
								else {
									$partialFail=TRUE ;
								}
							}
						}
						
						//Deal with partial fail
						if ($partialFail==TRUE) {
							//Fail 6
							$URL.="&updateReturn=fail6" ;
							header("Location: {$URL}");
						}
						else {
							if ($schoolType=="Online") {
								//Write to database
								try {
									$data=array("commentStudent"=>$commentStudent, "evidenceType"=>$type, "evidenceLocation"=>$location, "timestampCompletePending"=>date("Y-m-d H:i:s"), "freeLearningUnitStudentID"=>$freeLearningUnitStudentID); 
									$sql="UPDATE freeLearningUnitStudent SET status='Complete - Approved', commentStudent=:commentStudent, evidenceType=:evidenceType, evidenceLocation=:evidenceLocation, timestampCompletePending=:timestampCompletePending WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									//Fail 2
									$URL.="&updateReturn=fail2" ;
									header("Location: {$URL}");
									exit ;
								}
							}
							else {
								//Write to database
								try {
									$data=array("status"=>$status, "commentStudent"=>$commentStudent, "evidenceType"=>$type, "evidenceLocation"=>$location, "timestampCompletePending"=>date("Y-m-d H:i:s"), "freeLearningUnitStudentID"=>$freeLearningUnitStudentID); 
									$sql="UPDATE freeLearningUnitStudent SET status=:status, commentStudent=:commentStudent, evidenceType=:evidenceType, evidenceLocation=:evidenceLocation, timestampCompletePending=:timestampCompletePending WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									//Fail 2
									$URL.="&updateReturn=fail2" ;
									header("Location: {$URL}");
									exit ;
								}
							
								//Attempt to notify teacher(s) of class
								try {
									$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
									$sql="SELECT gibbonPersonID FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { }
							
								$text=sprintf(__($guid, 'A student has requested unit completion approval and feedback (%1$s).'), $name) ;
								$actionLink="/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&sidebar=true&tab=1" ;
								while ($row=$result->fetch()) {
									setNotification($connection2, $guid, $row["gibbonPersonID"], $text, "Free Learning", $actionLink) ;
								}
							}
							
							//Success 0
							$URL.="&updateReturn=success0" ;
							header("Location: {$URL}");
						}
					}
				}
			}
		}
	}
}
?>