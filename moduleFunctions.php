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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


function getLearningAreaArray($connection2) {
	$return=FALSE ;
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()>0) {
		$return=array() ;
		while ($row=$result->fetch()) {
			$return[$row["gibbonDepartmentID"]]=$row["name"] ;
		}
	}
	
	return $return ;
}

//Set $limite=TRUE to only return departments that the user has curriculum editing rights in
function getLearningAreas($connection2, $guid, $limit=FALSE ) {
	$output=FALSE ;
	try {
		if ($limit==TRUE) {
			$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sql="SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE type='Learning Area' AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')  ORDER BY name" ;
		}
		else {
			$data=array(); 
			$sql="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
		}
		$result=$connection2->prepare($sql);
		$result->execute($data);
		while ($row=$result->fetch()) {
			$output.=$row["gibbonDepartmentID"] . "," ;
			$output.=$row["name"] . "," ;
		}
	}
	catch(PDOException $e) { }		
	
	if ($output!=FALSE) {
		$output=substr($output,0,(strlen($output)-1)) ;
		$output=explode(",", $output) ;
	}
	return $output ;
}

?>
