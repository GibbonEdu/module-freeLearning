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

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_browse_details_approval.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, "/modules/Free Learning/units_browse_details_approval.php", $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Get params
		$freeLearningUnitStudentID="" ;
		if (isset($_GET["freeLearningUnitStudentID"])) {
			$freeLearningUnitStudentID=$_GET["freeLearningUnitStudentID"] ;
		}
		$freeLearningUnitID="" ;
		if (isset($_GET["freeLearningUnitID"])) {
			$freeLearningUnitID=$_GET["freeLearningUnitID"] ;
		}
		$gibbonDepartmentID="" ;
		if (isset($_GET["gibbonDepartmentID"])) {
			$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
		}
		$difficulty="" ;
		if (isset($_GET["difficulty"])) {
			$difficulty=$_GET["difficulty"] ;
		}
		$name="" ;
		if (isset($_GET["name"])) {
			$name=$_GET["name"] ;
		}
		
		print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name'>" . _('Browse Units') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_browse_details.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&freeLearningUnitID=$freeLearningUnitID&sidebar=true&tab=1'>" . _('Unit Details') . "</a> > </div><div class='trailEnd'>" . _('Approval') . "</div>" ;
		print "</div>" ;
		
		if ($freeLearningUnitID=="" OR $freeLearningUnitStudentID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				$data=array("freeLearningUnitID"=>$freeLearningUnitID, "freeLearningUnitStudentID"=>$freeLearningUnitStudentID); 
				$sql="SELECT * FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
	
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				
				$proceed=FALSE ;
				//Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s)
				$learningAreas=getLearningAreas($connection2, $guid, TRUE) ;
				if ($learningAreas!="") {
					for ($i=0; $i<count($learningAreas); $i=$i+2) {
						if (is_numeric(strpos($row["gibbonDepartmentIDList"], $learningAreas[$i]))) {
							$proceed=TRUE ;
						}
					}
				}
				
				if ($proceed==FALSE) {
					print "<div class='error'>" ;
						print _("The selected record does not exist, or you do not have access to it.") ;
					print "</div>" ;
				}
				else {
					//Let's go!
					if (isActionAccessible($guid, $connection2, "/modules/Free Learning/units_manage.php")) {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID'>" . _('Edit') . "<img style='margin: 0 0 -4px 3px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>" ;
						print "</div>" ;
					}
					
					print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Unit Name') . "</span><br/>" ;
								print $row["name"] ;
							print "</td>" ;
							print "<td style='width: 34%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Departments') . "</span><br/>" ;
								$learningAreas=getLearningAreas($connection2, $guid) ;
								if ($learningAreas=="") {
									print "<i>" . _('No Learning Areas available.') . "</i>" ;
								}
								else {
									for ($i=0; $i<count($learningAreas); $i=$i+2) {
										if (is_numeric(strpos($row["gibbonDepartmentIDList"], $learningAreas[$i]))) {
											print _($learningAreas[($i+1)]) . "<br/>" ;
										}
									}
								}
							print "</td>" ;
							print "<td style='width: 34%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>" . _('Authors') . "</span><br/>" ;
								$authors=getAuthorsArray($connection2, $freeLearningUnitID) ;
								foreach ($authors AS $author) {
									print $author[1] . "<br/>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
					
					print "<h4>" ;
						print _('Unit Complete Approval') ;
					print "</h4>" ;
					print "<p>" ;
						print _('Use the table below to indicate student completion, based on the evidence shown on the previous page. Leave the student a comment in way of feedback.') ;
					print "</p>" ;
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_browse_details_approvalProcess.php?address=" . $_GET["q"] ?>"  enctype="multipart/form-data">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
							<tr>
								<td> 
									<b><?php print _('Status') ?> *</b><br/>
								</td>
								<td class="right">
									<select style="width: 302px" name="status" id="status">
										<option <?php if ($row["status"]=="Complete - Approved") { print "selected" ; } ?> value='Complete - Approved'>Complete - Approved</option>
										<option <?php if ($row["status"]=="Evidence Not Approved") { print "selected" ; } ?> value='Evidence Not Approved'>Evidence Not Approved</option>
									</select>
								</td>
							</tr>
							<script type="text/javascript">
								/* Subbmission type control */
								$(document).ready(function(){
									<?php
									if ($row["status"]=="Evidence Not Approved") {
										?>
										$("#exemplarRow").css("display","none");
										$("#fileRow").css("display","none");
										$("#linkRow").css("display","none");
										file.disable() ;
										<?php
									}
									else if ($row["exemplarWork"]=="N") {
										?>
										$("#fileRow").css("display","none");
										$("#linkRow").css("display","none");
										file.disable() ;
										<?php
									}
									?>
									
									$("#status").click(function(){
										if ($('#status option:selected').val()=="Evidence Not Approved" ) {
											$("#exemplarRow").css("display","none"); 
											$("#fileRow").css("display","none"); 
											$("#linkRow").css("display","none"); 
											file.disable() ;
										} else {
											$("#exemplarRow").slideDown("fast", $("#exemplarRow").css("display","table-row")); 
											if ($('#exemplarWork option:selected').val()=="Y" ) {
												$("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row")); 
												$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row")); 
												file.enable() ;
											}
										}
									}) ;
								
									$("#exemplarWork").click(function(){
										if ($('#exemplarWork option:selected').val()=="N" ) {
											$("#fileRow").css("display","none"); 
											$("#linkRow").css("display","none"); 
											file.disable() ;
										} else {
											$("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row")); 
											$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row")); 
											file.enable() ;
										}
									 });
								});
							</script>
						
							<tr id="exemplarRow">
								<td> 
									<b><?php print _("Exmplar Work") ; ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _("Work and comments will be made viewable to other users.") ; ?></i></span>
								</td>
								<td class="right">
									<select name="exemplarWork" id="exemplarWork" style="width: 302px">
										<?php
										print "<option " ;
										if ($row["exemplarWork"]=="N") {
											print " selected " ;
										}
										print "value='N'>" . ynExpander('N') . "</option>" ;
										print "<option " ;
										if ($row["exemplarWork"]=="Y") {
											print " selected " ;
										}
										print "value='Y'>" . ynExpander('Y') . "</option>" ;
										?>				
									</select>
								</td>
							</tr>
							<tr id="fileRow">
								<td> 
									<b><?php print _('Exemplar Work Thumbnail Image') ?></b><br/>
									<span style="font-size: 90%"><i>150x150px jpg/png/gif</i><br/></span>
								</td>
								<td class="right">
									<?php
									if ($row["exemplarWorkThumb"]!="") {
										print _("Current attachment:") . " <a href='" . $row["exemplarWorkThumb"] . "'>" . $row["exemplarWorkThumb"] . "</a><br/><br/>" ;
									}
									?>
									<input type="file" name="file" id="file"><br/><br/>
									<?php
									print getMaxUpload() ;
								
									//Get list of acceptable file extensions
									try {
										$dataExt=array(); 
										$sqlExt="SELECT * FROM gibbonFileExtension" ;
										$resultExt=$connection2->prepare($sqlExt);
										$resultExt->execute($dataExt);
									}
									catch(PDOException $e) { }
									$ext="'.png','.jpeg','.jpg','.gif'" ;
									?>
									<script type="text/javascript">
										var file=new LiveValidation('file');
										file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "<?php print _('Illegal file type!') ?>", partialMatch: true, caseSensitive: false } );
									</script>
								</td>
							</tr>
							<tr id="linkRow">
								<td> 
									<b><?php print _('Exemplar Work Thumbnail Image Credit') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _("Credit and license for image used above.") ; ?></i></span>
								</td>
								<td class="right">
									<input name="exemplarWorkLicense" id="exemplarWorkLicense" maxlength=255 value="<?php print $row["exemplarWorkLicense"] ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Submission') ?> *</b><br/>
								</td>
								<td class="right">
									<?php
									if ($row["evidenceLocation"]!="") {
										if ($row["evidenceType"]=="Link") {
											print "<a target='_blank' href='" . $row["evidenceLocation"] . "'>" . _('View Submission') . "</a>" ;
										}
										else {
											print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["evidenceLocation"] . "'>" . _('View Submission') . "</a>" ;
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<b><?php print _('Student Comment') ?> *</b><br/>
									<p>
										<?php
											print $row["commentStudent"] ;
										?>
									</p>
								</td>
							</tr>
							
							<tr>
								<td colspan=2> 
									<b><?php print _('Teacher Comment') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('Leave a comment on the student\'s progress.') ?></i></span>
									<?php print getEditor($guid,  TRUE, "commentApproval", $row["commentApproval"], 15, TRUE, TRUE ) ?>
								</td>
							</tr>
							<tr>
								<td class="right" colspan=2>
									<input type="hidden" name="freeLearningUnitStudentID" value="<?php print $row["freeLearningUnitStudentID"] ?>">
									<input type="hidden" name="freeLearningUnitID" value="<?php print $freeLearningUnitID ?>">
									<input type="submit" id="submit" value="Submit">
								</td>
							</tr>
							<tr>
								<td class="right" colspan=2>
									<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
								</td>
							</tr>
						</table>
					</form>
					<?php
				}
			}
		}
	} 
}		
?>