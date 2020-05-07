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

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details_approval.php', $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        //Get params
        $freeLearningUnitStudentID = '';
        if (isset($_GET['freeLearningUnitStudentID'])) {
            $freeLearningUnitStudentID = $_GET['freeLearningUnitStudentID'];
        }
        $freeLearningUnitID = '';
        if (isset($_GET['freeLearningUnitID'])) {
            $freeLearningUnitID = $_GET['freeLearningUnitID'];
        }
        $canManage = false;
        if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
            $canManage = true;
        }
        $showInactive = 'N';
        if ($canManage and isset($_GET['showInactive'])) {
            $showInactive = $_GET['showInactive'];
        }
        $gibbonDepartmentID = '';
        if (isset($_GET['gibbonDepartmentID'])) {
            $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
        }
        $difficulty = '';
        if (isset($_GET['difficulty'])) {
            $difficulty = $_GET['difficulty'];
        }
        $name = '';
        if (isset($_GET['name'])) {
            $name = $_GET['name'];
        }
        $view = '';
        if (isset($_GET['view'])) {
            $view = $_GET['view'];
        }
        $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
        if ($canManage) {
            if (isset($_GET['gibbonPersonID'])) {
                $gibbonPersonID = $_GET['gibbonPersonID'];
            }
        }

        $urlParams = compact('freeLearningUnitStudentID', 'freeLearningUnitID', 'showInactive', 'gibbonDepartmentID', 'difficulty', 'name', 'view', 'gibbonPersonID');

        $page->breadcrumbs
             ->add(__m('Browse Units'), 'units_browse.php', $urlParams);

        $urlParams["sidebar"] = "true";
        $page->breadcrumbs->add(__m('Unit Details'), 'units_browse_details.php', $urlParams)
             ->add(__m('Approval'));

        if ($freeLearningUnitID == '' or $freeLearningUnitStudentID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                $sql = 'SELECT freeLearningUnit.*, freeLearningUnitStudent.*, surname, preferredName FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();

                $proceed = false;
                //Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s) or if canManage is true
                $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php', 'Manage Units_all');
                if ($manageAll == true) {
                    $proceed = true;
                }
                else if ($row['enrolmentMethod'] == 'schoolMentor' && $row['gibbonPersonIDSchoolMentor'] == $_SESSION[$guid]['gibbonPersonID']) {
                    $proceed = true;
                } else {
                    $learningAreas = getLearningAreas($connection2, $guid, true);
                    if ($learningAreas != '') {
                        for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                $proceed = true;
                            }
                        }
                    }
                }

                //Check to see if class is in one teacher teachers
                if ($row['enrolmentMethod'] == 'class') { //Is teacher of this class?
                    try {
                        $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $row['gibbonCourseClassID']);
                        $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'";
                        $resultClasses = $connection2->prepare($sqlClasses);
                        $resultClasses->execute($dataClasses);
                    } catch (PDOException $e) {}
                    if ($resultClasses->rowCount() > 0) {
                        $proceed = true;
                    }
                }

                if ($proceed == false) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php')) {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a>";
                        echo '</div>';
                    }

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Unit Name', 'Free Learning').'</span><br/>';
                    echo $row['name'];
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Departments', 'Free Learning').'</span><br/>';
                    $learningAreas = getLearningAreas($connection2, $guid);
                    if ($learningAreas == '') {
                        echo '<i>'.__($guid, 'No Learning Areas available.', 'Free Learning').'</i>';
                    } else {
                        for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                echo __($guid, $learningAreas[($i + 1)]).'<br/>';
                            }
                        }
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Authors', 'Free Learning').'</span><br/>';
                    $authors = getAuthorsArray($connection2, $freeLearningUnitID);
                    foreach ($authors as $author) {
                        echo $author[1].'<br/>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    echo '<h4>';
                    echo __($guid, 'Unit Complete Approval', 'Free Learning');
                    echo '</h4>';
                    echo '<p>';
                    echo __($guid, 'Use the table below to indicate student completion, based on the evidence shown on the previous page. Leave the student a comment in way of feedback.', 'Free Learning');
                    echo '</p>';

                    $collaborativeAssessment = getSettingByScope($connection2, 'Free Learning', 'collaborativeAssessment');
                    if ($collaborativeAssessment == 'Y' AND  !empty($row['collaborationKey'])) {
                        echo "<div class='message'>";
                        echo __m('Collaborative Assessment is enabled: you will be giving feedback to all members of this group in one go.');
                        echo '</div>';
                    }

                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_browse_details_approvalProcess.php?address='.$_GET['q']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view" ?>"  enctype="multipart/form-data">
						<table class='smallIntBorder' cellspacing='0' style="width: 100%">
							<tr>
								<td>
									<b><?php echo __($guid, 'Student') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php echo __($guid, 'This value cannot be changed.') ?></i></span>
								</td>
								<td class="right">
									<?php echo "<input readonly value='".formatName('', $row['preferredName'], $row['surname'], 'Student', false)."' type='text' style='width: 300px'>";
                    				?>
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Status', 'Free Learning') ?> *</b><br/>
								</td>
								<td class="right">
									<select style="width: 302px" name="status" id="status">
										<option <?php if ($row['status'] == 'Complete - Approved') { echo 'selected'; } ?> value='Complete - Approved'>Complete - Approved</option>
										<option <?php if ($row['status'] == 'Evidence Not Yet Approved') { echo 'selected'; } ?> value='Evidence Not Yet Approved'>Evidence Not Yet Approved</option>
									</select>
								</td>
							</tr>
							<script type="text/javascript">
								/* Subbmission type control */
								$(document).ready(function(){
									<?php
                                    if ($row['status'] == 'Evidence Not Yet Approved') {
                                        ?>
										$("#exemplarRow").css("display","none");
										$(".exemplarDrop").css("display","none");
										file.disable() ;
										<?php

                                    } elseif ($row['exemplarWork'] == 'N') {
                                        ?>
										$(".exemplarDrop").css("display","none");
										file.disable() ;
										<?php

                                    }
                    				?>

									$("#status").click(function(){
										if ($('#status').val()=="Evidence Not Yet Approved" ) {
											$("#exemplarRow").css("display","none");
											$(".exemplarDrop").css("display","none");
											file.disable() ;
										} else {
											$("#exemplarRow").slideDown("fast", $("#exemplarRow").css("display","table-row"));
											if ($('#exemplarWork').val()=="Y" ) {
												$(".exemplarDrop").slideDown("fast", $(".exemplarDrop").css("display","table-row"));
												file.enable() ;
											}
										}
									}) ;

									$("#exemplarWork").click(function(){
										if ($('#exemplarWork').val()=="N" ) {
											$(".exemplarDrop").css("display","none");
											file.disable() ;
										} else {
											$(".exemplarDrop").slideDown("fast", $(".exemplarDrop").css("display","table-row"));
											file.enable() ;
										}
									 });
								});
							</script>

							<tr id="exemplarRow">
								<td>
									<b><?php echo __($guid, 'Exemplar Work', 'Free Learning'); ?> *</b><br/>
									<span style="font-size: 90%"><i><?php echo __($guid, 'Work and comments will be made viewable to other users.', 'Free Learning'); ?></i></span>
								</td>
								<td class="right">
									<select name="exemplarWork" id="exemplarWork" style="width: 302px">
										<?php
                                        echo '<option ';
										if ($row['exemplarWork'] == 'N') {
											echo ' selected ';
										}
										echo "value='N'>".ynExpander($guid, 'N').'</option>';
										echo '<option ';
										if ($row['exemplarWork'] == 'Y') {
											echo ' selected ';
										}
										echo "value='Y'>".ynExpander($guid, 'Y').'</option>';
										?>
									</select>
								</td>
							</tr>
							<tr class="exemplarDrop">
								<td>
									<b><?php echo __($guid, 'Exemplar Work Thumbnail Image', 'Free Learning') ?></b><br/>
									<span style="font-size: 90%"><i>150x150px jpg/png/gif</i><br/></span>
								</td>
								<td class="right">
									<?php
                                    if ($row['exemplarWorkThumb'] != '') {
                                        echo __($guid, 'Current attachment:', 'Free Learning')." <a href='".$row['exemplarWorkThumb']."'>".$row['exemplarWorkThumb'].'</a><br/><br/>';
                                    }
                    						?>
									<input type="file" name="file" id="file"><br/><br/>
									<?php
                                    echo getMaxUpload($guid);

                                    //Get list of acceptable file extensions
                                    try {
                                        $dataExt = array();
                                        $sqlExt = 'SELECT * FROM gibbonFileExtension';
                                        $resultExt = $connection2->prepare($sqlExt);
                                        $resultExt->execute($dataExt);
                                    } catch (PDOException $e) {
                                    }
									$ext = "'.png','.jpeg','.jpg','.gif'";
									?>
									<script type="text/javascript">
										var file=new LiveValidation('file');
										file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "<?php echo __($guid, 'Illegal file type!') ?>", partialMatch: true, caseSensitive: false } );
									</script>
								</td>
							</tr>
							<tr class="exemplarDrop">
								<td>
									<b><?php echo __($guid, 'Exemplar Work Thumbnail Image Credit', 'Free Learning') ?></b><br/>
									<span style="font-size: 90%"><i><?php echo __($guid, 'Credit and license for image used above.', 'Free Learning'); ?></i></span>
								</td>
								<td class="right">
									<input name="exemplarWorkLicense" id="exemplarWorkLicense" maxlength=255 value="<?php echo htmlPrep($row['exemplarWorkLicense']) ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr class="exemplarDrop">
								<td>
									<b><?php echo __($guid, 'Exemplar Work Embed', 'Free Learning') ?></b><br/>
									<span style="font-size: 90%"><i><?php echo __($guid, 'Include embed code, otherwise link to work will be used.', 'Free Learning'); ?></i></span>
								</td>
								<td class="right">
									<input name="exemplarWorkEmbed" id="exemplarWorkEmbed" maxlength=255 value="<?php echo htmlPrep($row['exemplarWorkEmbed']) ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Submission', 'Free Learning') ?> *</b><br/>
								</td>
								<td class="right">
									<?php
                                    if ($row['evidenceLocation'] != '') {
                                        if ($row['evidenceType'] == 'Link') {
                                            echo "<a target='_blank' href='".$row['evidenceLocation']."'>".__($guid, 'View Submission', 'Free Learning').'</a>';
                                        } else {
                                            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['evidenceLocation']."'>".__($guid, 'View Submission', 'Free Learning').'</a>';
                                        }
                                    }
                    				?>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<b><?php echo __($guid, 'Student Comment', 'Free Learning') ?> *</b><br/>
									<p>
										<?php
                                            echo $row['commentStudent'];
                    					?>
									</p>
								</td>
							</tr>

							<tr>
								<td colspan=2>
									<b><?php echo __($guid, 'Teacher Comment', 'Free Learning') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php echo __($guid, 'Leave a comment on the student\'s progress.', 'Free Learning') ?></i></span>
									<?php echo getEditor($guid,  true, 'commentApproval', $row['commentApproval'], 15, true, true) ?>
								</td>
							</tr>
							<tr>
								<td class="right" colspan=2>
									<input type="hidden" name="freeLearningUnitStudentID" value="<?php echo $row['freeLearningUnitStudentID'] ?>">
									<input type="hidden" name="freeLearningUnitID" value="<?php echo $freeLearningUnitID ?>">
									<input type="submit" id="submit" value="Submit">
								</td>
							</tr>
							<tr>
								<td class="right" colspan=2>
									<span style="font-size: 90%"><i>* <?php echo __($guid, 'denotes a required field'); ?></i></span>
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
