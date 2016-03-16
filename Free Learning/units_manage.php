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

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Units') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="success0") {
				$deleteReturnMessage=_("Your request was completed successfully.") ;		
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
		
		$gibbonDepartmentID=NULL ;
		if (isset($_GET["gibbonDepartmentID"])) {
			$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
		}	
		$difficulty=NULL ;
		if (isset($_GET["difficulty"])) {
			$difficulty=$_GET["difficulty"] ;
		}	
		$name=NULL ;
		if (isset($_GET["name"])) {
			$name=$_GET["name"] ;
		}
		
		print "<h3>" ;
			print _("Filter") ;
		print "</h3>" ;
		print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_manage.php'>" ;
			print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
				?>
				<tr>
					<td> 
						<b><?php print _('Learning Area') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<select name="gibbonDepartmentID" id="gibbonDepartmentID" style="width: 302px">
							<option value=""></option>
							<?php
							$learningAreas=getLearningAreas($connection2, $guid) ;
							for ($i=0; $i<count($learningAreas); $i=$i+2) {
								if ($gibbonDepartmentID==$learningAreas[$i]) {
									print "<option selected value='" . $learningAreas[$i] . "'>" . _($learningAreas[($i+1)]) . "</option>" ;
								}
								else {
									print "<option value='" . $learningAreas[$i] . "'>" . _($learningAreas[($i+1)]) . "</option>" ;
								}
							}
							?>			
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Difficulty') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						$difficulties=getSettingByScope($connection2, "Free Learning", "difficultyOptions") ;
						print "<select name='difficulty' id='difficulty' style='width: 302px'>" ;
							print "<option value=''></option>" ;
							$difficultiesList=explode(",", $difficulties) ;
							foreach ($difficultiesList AS $difficultyOption) {
								$selected="" ;
								if ($difficulty==$difficultyOption) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $difficultyOption . "'>" . $difficultyOption . "</option>" ;
							}
						print "</select>" ;
						?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Name') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						print "<input name='name' value='" . $name . "' type='text' style='width: 300px'/>" ; 
						?>
					</td>
				</tr>
				<?php
			
				print "<tr>" ;
					print "<td class='right' colspan=2>" ;
						print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_manage.php'>" . _('Clear Filters') . "</a> " ;
						print "<input type='submit' value='" . _('Go') . "'>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
		print "</form>" ;
			
		//Fetch units
		$difficulties=getSettingByScope($connection2, "Free Learning", "difficultyOptions") ;
		$difficultyOrder="" ;
		if ($difficulties!=FALSE) {
			$difficultyOrder="FIELD(difficulty" ;
			$difficulties=explode(",", $difficulties) ;
			foreach ($difficulties AS $difficultyOption) {
				$difficultyOrder.=",'" . $difficultyOption . "'" ;
			}
			$difficultyOrder.="), " ;
		}
		try {
			$data=array() ;
			$sqlWhere="AND " ;
			if ($gibbonDepartmentID!="") {
				$data["gibbonDepartmentID"]=$gibbonDepartmentID ;
				$sqlWhere.="gibbonDepartmentIDList LIKE concat('%', :gibbonDepartmentID, '%') AND " ; 
			}
			if ($difficulty!="") {
				$data["difficulty"]=$difficulty ;
				$sqlWhere.="difficulty=:difficulty AND " ; 
			}
			if ($name!="") {
				$data["name"]=$name ;
				$sqlWhere.="freeLearningUnit.name LIKE concat('%', :name, '%') AND " ; 
			}
			if ($sqlWhere=="AND ") {
				$sqlWhere="" ;
			}
			else {
				$sqlWhere=substr($sqlWhere,0,-5) ;
			}
			if ($highestAction=="Manage Units_all") {
				$sql="SELECT * FROM freeLearningUnit WHERE true $sqlWhere ORDER BY $difficultyOrder name" ;
			}
			else if ($highestAction=="Manage Units_learningAreas") {
				$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"]; 
				$sql="SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')  $sqlWhere ORDER BY $difficultyOrder name" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		$learningAreas=getLearningAreaArray($connection2) ;
		
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_manage_add.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
		print "</div>" ;
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print _("There are no records to display.") ;
			print "</div>" ;
		}
		else {
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print _("Name") ;
					print "</th>" ;
					print "<th>" ;
						print _("Difficulty") ;
					print "</th>" ;
					print "<th>" ;
						print _("Learning Areas") ;
					print "</th>" ;
					print "<th>" ;
						print _("Active") ;
					print "</th>" ;
					print "<th style='width: 100px'>" ;
						print _("Actions") ;
					print "</th>" ;
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				while ($row=$result->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
											
					if ($row["active"]=="N") {
						$rowNum="error" ;
					}
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print $row["name"] ;
						print "</td>" ;
						print "<td>" ;
							print $row["difficulty"] ;
						print "</td>" ;
						print "<td style='max-width: 270px'>" ;
							if (is_null($row["gibbonDepartmentIDList"])==FALSE) {
								$departments=explode(",", $row["gibbonDepartmentIDList"]) ;
								foreach ($departments AS $department) {
									if (isset($learningAreas[$department])) {
										print $learningAreas[$department] . "<br/>" ;
									}
								}
							}
							else {
								print "<i>" . _('None') . "</i>" ;
							}
						print "</td>" ;
						print "<td>" ;
							print ynExpander($guid, $row["active"]) ;
						print "</td>" ;
						print "<td>" ;
							if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_browse.php")) {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details.php&sidebar=true&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
							}
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_manage_edit.php&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_manage_delete.php&freeLearningUnitID=" . $row["freeLearningUnitID"] . "&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
						print "</td>" ;
					print "</tr>" ;
					
					$count++ ;
				}
			print "</table>" ;
		}
	}
}		
?>