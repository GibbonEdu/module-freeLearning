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
$schoolType=getSettingByScope($connection2, "Free Learning", "schoolType" ) ;

$canEdit=isActionAccessible($guid, $connection2, "/modules/Free Learning/units_browse_details_approval.php") ;

if (!(isActionAccessible($guid, $connection2, "/modules/Free Learning/showcase.php")==TRUE OR ($publicUnits=="Y" AND isset($_SESSION[$guid]["username"])==FALSE))) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
		if ($publicUnits=="Y") {
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > </div><div class='trailEnd'>" . _('Free Learning Showcase') . "</div>" ;
		}
		else {
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Free Learning Showcase') . "</div>" ;
		}
	print "</div>" ;
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	//Spit out exemplar work
	try {
		$dataWork=array();  
		$sqlWork="SELECT freeLearningUnit.*, freeLearningUnitStudent.*, surname, preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE active='Y' AND exemplarWork='Y' ORDER BY timestampCompleteApproved DESC" ;
		$resultWork=$connection2->prepare($sqlWork);
		$resultWork->execute($dataWork);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	$sqlPage=$sqlWork . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
		
	if ($resultWork->rowCount()<1) {
		print "<div class='error'>" ;
			print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($resultWork->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $resultWork->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "") ;
		}
	
		while ($rowWork=$resultWork->fetch()) {
			print "<h3 style='margin-bottom: 5px'>" ;
				print $rowWork["name"] . "<span style='font-size: 75%; text-transform: none'> by " . formatName("", $rowWork["preferredName"], $rowWork["surname"], "Student", false) . "</span>" ;
			print "</h3>" ;
			print "<p style='font-style: italic; margin-top 0; margin-bottom: 5px; font-size: 10.5px'>" ;
				 print _("Shared on") . " " . dateConvertBack($guid, $rowWork["timestampCompleteApproved"]) ;
			print "</p>" ;
			if ($canEdit) {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitID=" . $rowWork["freeLearningUnitID"] . "&freeLearningUnitStudentID=" . $rowWork["freeLearningUnitStudentID"] . "&sidebar=true'>" . _('Edit') . "<img style='margin: 0 0 -4px 3px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>" ;
				print "</div>" ;
			}
			print "<table style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='text-align: center; vertical-align: top; width: 160px; border-right: none'>" ;
						if ($rowWork["exemplarWorkThumb"]!="") {
							print "<img style='width: 150px; height: 150px; margin-bottom: 5px' class='user' src='" . $rowWork["exemplarWorkThumb"] . "'/>" ;
							if ($rowWork["exemplarWorkLicense"]!="") {
								print "<span style='font-size: 85%; font-style: italic'>" . $rowWork["exemplarWorkLicense"] . "</span>" ;
							}
						}
						else {
							print "<img style='height: 150px; width: 150px; opacity: 1.0' class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_240_square.jpg'/><br/>" ;
						}
					print "</td>" ;
					print "<td style='vertical-align: top; border-left: none'>" ;
						//DISPLAY WORK.
						print "<p>" ;
							if ($rowWork["evidenceType"]=="File") { //It's a file
								print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["evidenceLocation"] . "'>" . _('Click to View Work') . "</a>" ;
							}
							else { //It's a link
								print "<a target='_blank' href='" . $rowWork["evidenceLocation"] . "'>" . _('Click to View Work') . "</a>" ;
							}
						print "</p>" ;
						print "<p>" ;
							if ($rowWork["commentStudent"]!="") {
								print "<b><u>" . _("Student Comment") . "</u></b><br/><br/>" ;
								print nl2br($rowWork["commentStudent"]) . "<br/>" ;
							}
							if ($rowWork["commentApproval"]!="") {
								if ($rowWork["commentStudent"]!="") {
									print "<br/>" ;
								}
								print "<b><u>" . _("Teacher Comment") . "</u></b>" ;
								print $rowWork["commentApproval"] . "<br/>" ;
							}
						print "</p>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
		}
		if ($resultWork->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $resultWork->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "") ;
		}	
	}	
}	
?>