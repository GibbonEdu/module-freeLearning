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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');
$schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == true or ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false))) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
        $highestAction = 'Browse Units_all';
    } else {
        $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    }
    if ($highestAction == false) { echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Get params
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

        echo "<div class='trail'>";
        if ($publicUnits == 'Y') {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'>".__($guid, 'Browse Units')."</a> > </div><div class='trailEnd'>".__($guid, 'Unit Details').'</div>';
        } else {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'>".__($guid, 'Browse Units')."</a> > </div><div class='trailEnd'>".__($guid, 'Unit Details').'</div>';
        }
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        if ($freeLearningUnitID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
                    $data = array('freeLearningUnitID' => $freeLearningUnitID);
                    $sql = "SELECT * FROM freeLearningUnit WHERE sharedPublic='Y' AND gibbonYearGroupIDMinimum IS NULL AND active='Y' AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC";
                } else {
                    if ($highestAction == 'Browse Units_all' or $schoolType == 'Online') {
                        $data = array('freeLearningUnitID' => $freeLearningUnitID);
                        $sql = 'SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
                    } elseif ($highestAction == 'Browse Units_prerequisites') {
                        if ($schoolType == 'Physical') {
                            $data['freeLearningUnitID'] = $freeLearningUnitID;
                            $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                            $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                            $sql = "SELECT freeLearningUnit.*, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2 FROM freeLearningUnit LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID) WHERE active='Y' AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber) AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC";
                        } else {
                            $data['freeLearningUnitID'] = $freeLearningUnitID;
                            $sql = "SELECT freeLearningUnit.* FROM freeLearningUnit WHERE active='Y' AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC";
                        }
                    }
                }
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
                if ($gibbonDepartmentID != '' or $difficulty != '' or $name != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }

                $proceed = false;
                if ($highestAction == 'Browse Units_all' or $schoolType == 'Online') {
                    $proceed = true;
                } elseif ($highestAction == 'Browse Units_prerequisites') {
                    if ($row['freeLearningUnitIDPrerequisiteList'] == null or $row['freeLearningUnitIDPrerequisiteList'] == '') {
                        $proceed = true;
                    } else {
                        $prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
                        $prerquisitesMet = prerquisitesMet($connection2, $_SESSION[$guid]['gibbonPersonID'], $prerequisitesActive);
                        if ($prerquisitesMet) {
                            $proceed = true;
                        }
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
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a>";
                        echo '</div>';
                    }

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 50%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Unit Name').'</span><br/>';
                    echo '<i>'.$row['name'].'</i>';
                    echo '</td>';
                    echo "<td style='width: 50%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Time').'</span><br/>';
                    $timing = null;
                    $blocks = getBlocksArray($connection2, $freeLearningUnitID);
                    if ($blocks != false) {
                        foreach ($blocks as $block) {
                            if ($block[0] == $row['freeLearningUnitID']) {
                                if (is_numeric($block[2])) {
                                    $timing += $block[2];
                                }
                            }
                        }
                    }
                    if (is_null($timing)) {
                        echo '<i>'.__($guid, 'NA').'</i>';
                    } else {
                        echo '<i>'.$timing.'</i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 135%!important; vertical-align: top; text-align: right' rowspan=4>";
                    if ($row['logo'] == null) {
                        echo "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_125.jpg'/><br/>";
                    } else {
                        echo "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$row['logo']."'/><br/>";
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Difficulty').'</span><br/>';
                    echo '<i>'.$row['difficulty'].'<i>';
                    echo '</td>';
                    echo "<td style='padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Prerequisites').'</span><br/>';
                    $prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
                    if ($prerequisitesActive != false) {
                        $prerequisites = explode(',', $prerequisitesActive);
                        $units = getUnitsArray($connection2);
                        foreach ($prerequisites as $prerequisite) {
                            echo '<i>'.$units[$prerequisite][0].'</i><br/>';
                        }
                    } else {
                        echo '<i>'.__($guid, 'None').'<br/></i>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Departments').'</span><br/>';
                    $learningAreas = getLearningAreas($connection2, $guid);
                    if ($learningAreas == '') {
                        echo '<i>'.__($guid, 'No Learning Areas available.').'</i>';
                    } else {
                        for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                echo '<i>'.__($guid, $learningAreas[($i + 1)]).'</i><br/>';
                            }
                        }
                    }
                    echo '</td>';
                    echo "<td style='vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Authors').'</span><br/>';
                    $authors = getAuthorsArray($connection2, $freeLearningUnitID);
                    foreach ($authors as $author) {
                        if ($author[3] == '') {
                            echo '<i>'.$author[1].'</i><br/>';
                        } else {
                            echo "<i><a target='_blank' href='".$author[3]."'>".$author[1].'</a></i><br/>';
                        }
                    }
                    echo '</td>';
                    echo '</tr>';
                    if ($schoolType == 'Physical') {
                        echo '<tr>';
                        echo "<td style='vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Groupings').'</span><br/>';
                        if ($row['grouping'] != '') {
                            $groupings = explode(',', $row['grouping']);
                            foreach ($groupings as $grouping) {
                                echo ucwords($grouping).'<br/>';
                            }
                        }
                        echo '</td>';
                        echo "<td style='vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    //Work out if we should show enrolment, and what type of enrolment it should be (e.g. self or class)
                    $enrolment = false;
                    $enrolmentType = null;
                    if (isset($_SESSION[$guid]['username'])) {
                        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                        if ($roleCategory == 'Student') {
                            $enrolment = true;
                            $enrolmentType = 'student';
                        } elseif ($roleCategory == 'Staff') {
                            $enrolment = true;
                            $enrolmentType = 'staffView';

                            //Check to see if we can set enrolmentType to "staffEdit" based on access to Manage Units_all
                            $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php', 'Manage Units_all');
                            if ($manageAll == true) {
                                $enrolmentType = 'staffEdit';
                            } else {
                                //Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s)
                                $learningAreas = getLearningAreas($connection2, $guid, true);
                                if ($learningAreas != '') {
                                    for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                                        if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                            $enrolmentType = 'staffEdit';
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $defaultTab = 2;
                    if (!$enrolment) {
                        $defaultTab = 1;
                    }
                    if (isset($_GET['tab'])) {
                        $defaultTab = $_GET['tab'];
                    }
                    ?>
					<script type='text/javascript'>
						$(function() {
							$( "#tabs" ).tabs({
								active: <?php echo $defaultTab ?>,
								ajaxOptions: {
									error: function( xhr, status, index, anchor ) {
										$( anchor.hash ).html(
											"Couldn't load this tab." );
									}
								}
							});
						});
					</script>

					<?php
                    echo "<div id='tabs' style='margin: 20px 0'>";
                    //Tab links
                    echo '<ul>';
                    echo "<li><a href='#tabs0'>".__($guid, 'Unit Overview').'</a></li>';
                    if ($enrolment) {
                        echo "<li><a href='#tabs1'>".__($guid, 'Enrolment').'</a></li>';
                    }
                    echo "<li><a href='#tabs2'>".__($guid, 'Content').'</a></li>';
                    echo "<li><a href='#tabs3'>".__($guid, 'Resources').'</a></li>';
                    echo "<li><a href='#tabs4'>".__($guid, 'Outcomes').'</a></li>';
                    echo "<li><a href='#tabs5'>".__($guid, 'Exemplar Work').'</a></li>';
                    echo '</ul>';

					//Tabs
					echo "<div id='tabs0'>";
                    echo '<h3>';
                    echo __($guid, 'Blurb');
                    echo '</h3>';
                    echo '<p>';
                    echo $row['blurb'];
                    echo '</p>';
                    if ($row['license'] != '') {
                        echo '<h4>';
                        echo __($guid, 'License');
                        echo '</h4>';
                        echo '<p>';
                        echo __($guid, 'This work is shared under the following license:').' '.$row['license'];
                        echo '</p>';
                    }
                    if ($row['outline'] != '') {
                        echo '<h3>';
                        echo 'Outline';
                        echo '</h3>';
                        echo '<p>';
                        echo $row['outline'];
                        echo '</p>';
                    }
                    echo '</div>';
                    if ($enrolment) {
                        echo "<div id='tabs1'>";
                        if (isset($_GET['updateReturn'])) {
                            $updateReturn = $_GET['updateReturn'];
                        } else {
                            $updateReturn = '';
                        }
                        $updateReturnMessage = '';
                        $class = 'error';
                        if (!($updateReturn == '')) {
                            if ($updateReturn == 'fail0') {
                                $updateReturnMessage = __($guid, 'Your request failed because you do not have access to this action.');
                            } elseif ($updateReturn == 'fail2') {
                                $updateReturnMessage = __($guid, 'Your request failed due to a database error.');
                            } elseif ($updateReturn == 'fail3') {
                                $updateReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
                            } elseif ($updateReturn == 'fail5') {
                                $updateReturnMessage = __($guid, 'Your request was successful, but some data was not properly saved.');
                            } elseif ($updateReturn == 'fail6') {
                                $updateReturnMessage = __($guid, 'Your request failed due to an attachment error.');
                            } elseif ($updateReturn == 'success0') {
                                $updateReturnMessage = __($guid, 'Your request was completed successfully.');
                                $class = 'success';
                            }
                            echo "<div class='$class'>";
                            echo $updateReturnMessage;
                            echo '</div>';
                        }

                        echo '<h3>';
                        echo __($guid, 'Enrolment');
                        echo '</h3>';

                        if ($enrolmentType == 'staffView' or $enrolmentType == 'staffEdit') { //STAFF ENROLMENT
                             echo '<p>';
                            echo __($guid, 'Below you can view the students currently enroled in this unit, including both those who are working on it, and those who are awaiting approval.');
                            echo '</p>';

                            if ($enrolmentType == 'staffEdit') {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_browse_details_enrolMultiple.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive'>".__($guid, 'Add Multiple')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add Multiple')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
                                echo '</div>';
                            }

							//List students whose status is Current or Complete - Pending
							try {
								if ($schoolType == 'Physical') {
									$dataClass = array('freeLearningUnitID' => $row['freeLearningUnitID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
									$sqlClass = "SELECT gibbonPersonID, surname, preferredName, freeLearningUnitStudent.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM freeLearningUnitStudent INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL OR dateEnd>='".date('Y-m-d')."') AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Approved','Current','Complete - Approved','Exempt'), surname, preferredName";
								} else {
									$dataClass = array('freeLearningUnitID' => $row['freeLearningUnitID']);
									$sqlClass = "SELECT gibbonPersonID, surname, preferredName, freeLearningUnitStudent.* FROM freeLearningUnitStudent INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL OR dateEnd>='".date('Y-m-d')."') ORDER BY FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Approved','Current','Complete - Approved','Exempt'), surname, preferredName";
								}
								$resultClass = $connection2->prepare($sqlClass);
								$resultClass->execute($dataClass);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
                            $count = 0;
                            $rowNum = 'odd';
                            if ($resultClass->rowCount() < 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            } else {
                                ?>
								<table cellspacing='0' style="width: 100%">
									<tr class='head'>
										<th>
											<?php echo __($guid, 'Student') ?><br/>
										</th>
										<?php
										if ($schoolType == 'Physical') {
											?>
											<th>
												<?php echo __($guid, 'Class') ?><br/>
											</th>
											<?php

										}
                                		?>
										<th>
											<?php echo __($guid, 'Status') ?><br/>
										</th>
										<th>
											<?php echo __($guid, 'View') ?><br/>
										</th>
										<th>
											<?php echo __($guid, 'Action') ?><br/>
										</th>
									</tr>
									<?php
									while ($rowClass = $resultClass->fetch()) {
										if ($count % 2 == 0) {
											$rowNum = 'even';
										} else {
											$rowNum = 'odd';
										}
										++$count;

										echo "<tr class=$rowNum>";
										?>
											<td>
												<?php echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClass['gibbonPersonID']."'>".formatName('', $rowClass['preferredName'], $rowClass['surname'], 'Student', true).'</a>' ?><br/>
											</td>
											<?php
											if ($schoolType == 'Physical') {
												echo '<td>';
												if ($rowClass['course'] != '' and $rowClass['class'] != '') {
													echo $rowClass['course'].'.'.$rowClass['class'];
												} else {
													echo '<i>'.__($guid, 'NA').'</i>';
												}
												echo '</td>';
											}
											?>
											<td>
												<?php echo $rowClass['status'] ?><br/>
											</td>
											<td>
												<?php
												if ($rowClass['evidenceLocation'] != '') {
													if ($rowClass['evidenceType'] == 'Link') {
														echo "<a target='_blank' href='".$rowClass['evidenceLocation']."'>".__($guid, 'View').'</>';
													} else {
														echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowClass['evidenceLocation']."'>".__($guid, 'View').'</>';
													}
												}
										?>
											</td>
											<td>
												<?php
												if ($enrolmentType == 'staffEdit') {
													if ($rowClass['status'] == 'Complete - Pending' or $rowClass['status'] == 'Complete - Approved' or $rowClass['status'] == 'Evidence Not Approved') {
														echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitStudentID='.$rowClass['freeLearningUnitStudentID'].'&freeLearningUnitID='.$rowClass['freeLearningUnitID']."&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
													}
													echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details_delete.php&freeLearningUnitStudentID='.$rowClass['freeLearningUnitStudentID'].'&freeLearningUnitID='.$rowClass['freeLearningUnitID']."&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
												}
                                                if ($rowClass['commentStudent'] != '' or $rowClass['commentApproval'] != '') {
                                                    echo "<script type='text/javascript'>";
                                                    echo '$(document).ready(function(){';
                                                    echo '$(".comment-'.$rowClass['freeLearningUnitStudentID'].'").hide();';
                                                    echo '$(".show_hide-'.$rowClass['freeLearningUnitStudentID'].'").fadeIn(1000);';
                                                    echo '$(".show_hide-'.$rowClass['freeLearningUnitStudentID'].'").click(function(){';
                                                    echo '$(".comment-'.$rowClass['freeLearningUnitStudentID'].'").fadeToggle(1000);';
                                                    echo '});';
                                                    echo '});';
                                                    echo '</script>';
                                                    echo "<a title='".__($guid, 'Show Comment')."' class='show_hide-".$rowClass['freeLearningUnitStudentID']."' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                                                }
                                                ?>
											</td>
										</tr>
										<?php
										if ($rowClass['commentStudent'] != '' or $rowClass['commentApproval'] != '') {
											echo "<tr class='comment-".$rowClass['freeLearningUnitStudentID']."' id='comment-".$rowClass['freeLearningUnitStudentID']."'>";
											echo '<td colspan=5>';
											if ($rowClass['commentStudent'] != '') {
												echo '<b>'.__($guid, 'Student Comment').'</b><br/>';
												echo nl2br($rowClass['commentStudent']).'<br/>';
											}
											if ($rowClass['commentApproval'] != '') {
												if ($rowClass['commentStudent'] != '') {
													echo '<br/>';
												}
												echo '<b>'.__($guid, 'Teacher Comment').'</b><br/>';
												echo nl2br($rowClass['commentApproval']).'<br/>';
											}
											echo '</td>';
											echo '</tr>';
										}
									}
                                ?>
								</table>
								<?php
                            }
                        }
                        if ($enrolmentType == 'student') { //STUDENT ENROLMENT
							if ($schoolType == 'Physical') {
								echo '<p>';
								echo __($guid, 'You can be enroled in one unit for each of your classes at any one time. Use the information to manage your enrolment for this unit.');
								echo '</p>';
							}

							//Check enrolment status
							$enrolCheckFail = false;
                            try {
                                $dataEnrol = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlEnrol = 'SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID';
                                $resultEnrol = $connection2->prepare($sqlEnrol);
                                $resultEnrol->execute($dataEnrol);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                                $enrolCheckFail = true;
                            }

                            if ($enrolCheckFail == false) {
                                if ($resultEnrol->rowCount() == 1) { //Already enroled, deal with different statuses
                                    $rowEnrol = $resultEnrol->fetch();
                                    if ($rowEnrol['status'] == 'Current' or $rowEnrol['status'] == 'Evidence Not Approved') { //Currently enroled, allow to set status to complete and submit feedback...or previously submitted evidence not accepted
                                        echo '<h4>';
                                        echo __($guid, 'Currently Enroled');
                                        echo '</h4>';
                                        if ($schoolType == 'Physical') {
                                            if ($rowEnrol['status'] == 'Current') {
                                                echo '<p>';
                                                echo sprintf(__($guid, 'You are currently enroled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your teacher will be notified, and will approve your unit completion in due course.'), $row['name']);
                                                echo '</p>';
                                            } elseif ($rowEnrol['status'] == 'Evidence Not Approved') {
                                                echo "<div class='warning'>";
                                                echo __($guid, 'Your evidence has not been approved. Please read the feedback below, adjust your evidence, and submit again:').'<br/><br/>';
                                                echo '<b>'.$rowEnrol['commentApproval'].'</b>';
                                                echo '</div>';
                                            }
                                        } else {
                                            if ($rowEnrol['status'] == 'Current') {
                                                echo '<p>';
                                                echo sprintf(__($guid, 'You are currently enroled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your unit completion will be automatically approved, and you can move onto the next unit.'), $row['name']);
                                                echo '</p>';
                                            }
                                        }

                                        ?>
										<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_browse_details_completePendingProcess.php?address='.$_GET['q'] ?>" enctype="multipart/form-data">
											<table class='smallIntBorder' cellspacing='0' style="width: 100%">
												<tr>
													<td>
														<b><?php echo __($guid, 'Status') ?> *</b><br/>
														<span style="font-size: 90%"><i><?php echo __($guid, 'This value cannot be changed.') ?></i></span>
													</td>
													<td class="right">
														<input readonly style='width: 300px' type='text' value='Complete - Pending' />
													</td>
												</tr>
												<tr>
													<td>
														<b><?php echo __($guid, 'Comment') ?> *</b><br/>
														<span style="font-size: 90%"><i>
															<?php
															echo __($guid, 'Leave a brief reflective comment on this unit<br/>and what you learned.');
															if ($rowEnrol['status'] == 'Evidence Not Approved') {
																echo '<br/><br/>'.__($guid, 'Your previous comment is shown here, for you to edit.');
															}
															?>
														</i></span>
													</td>
													<td class="right">
														<script type='text/javascript'>
															$(document).ready(function(){
																autosize($('textarea'));
															});
														</script>
														<textarea name="commentStudent" id="commentStudent" rows=8 style="width: 300px"><?php
														if ($rowEnrol['status'] == 'Evidence Not Approved') {
															echo $rowEnrol['commentStudent'];
														}
														?></textarea>
														<script type="text/javascript">
															var commentStudent=new LiveValidation('commentStudent');
															commentStudent.add(Validate.Presence);
														</script>
													</td>
												</tr>
												<tr>
													<td>
														<b><?php echo __($guid, 'Type') ?> *</b><br/>
													</td>
													<td class="right">
														<input checked type="radio" id="type" name="type" class="type" value="Link" /> Link
														<input type="radio" id="type" name="type" class="type" value="File" /> File
													</td>
												</tr>
												<script type="text/javascript">
													/* Subbmission type control */
													$(document).ready(function(){
														$("#fileRow").css("display","none");
														$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row"));

														$(".type").click(function(){
															if ($('input[name=type]:checked').val()=="Link" ) {
																$("#fileRow").css("display","none");
																$("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row"));
															} else {
																$("#linkRow").css("display","none");
																$("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row"));
															}
														 });
													});
												</script>

												<tr id="fileRow">
													<td>
														<b><?php echo __($guid, 'Submit File') ?> *</b><br/>
													</td>
													<td class="right">
														<input type="file" name="file" id="file"><br/><br/>
														<?php
														echo getMaxUpload();

														//Get list of acceptable file extensions
														try {
															$dataExt = array();
															$sqlExt = 'SELECT * FROM gibbonFileExtension';
															$resultExt = $connection2->prepare($sqlExt);
															$resultExt->execute($dataExt);
														} catch (PDOException $e) {
														}
														$ext = '';
														while ($rowExt = $resultExt->fetch()) {
															$ext = $ext."'.".$rowExt['extension']."',";
														}
														?>

														<script type="text/javascript">
															var file=new LiveValidation('file');
															file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
														</script>
													</td>
												</tr>
												<tr id="linkRow">
													<td>
														<b><?php echo __($guid, 'Submit Link') ?> *</b><br/>
													</td>
													<td class="right">
														<input name="link" id="link" maxlength=255 value="" type="text" style="width: 300px">
														<script type="text/javascript">
															var link=new LiveValidation('link');
															link.add( Validate.Inclusion, { within: ['http://', 'https://'], failureMessage: "Address must start with http:// or https://", partialMatch: true } );
														</script>
													</td>
												</tr>
												<tr>
													<td class="right" colspan=2>
														<input type="hidden" name="freeLearningUnitStudentID" value="<?php echo $rowEnrol['freeLearningUnitStudentID'] ?>">
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

                                    } elseif ($rowEnrol['status'] == 'Complete - Pending') { //Waiting for teacher feedback
                                        echo '<h4>';
                                        echo __($guid, 'Complete - Pending Approval');
                                        echo '</h4>';
                                        echo '<p>';
                                        echo __($guid, 'Your evidence, shown below, has been submitted to your teacher(s) for approval. This screen will show a teacher comment, once approval has been given.');
                                        echo '</p>';
                                        ?>
										<table class='smallIntBorder' cellspacing='0' style="width: 100%">
											<tr>
												<td>
													<b><?php echo __($guid, 'Status') ?></b><br/>
												</td>
												<td class="right">
													<input readonly style='width: 300px' type='text' value='Complete - Pending' />
												</td>
											</tr>
											<tr>
												<td>
													<b><?php echo __($guid, 'Evidence Type') ?></b><br/>
												</td>
												<td class="right">
													<input readonly style='width: 300px' type='text' value='<?php echo $rowEnrol['evidenceType'] ?>' />
												</td>
											</tr>
											<tr>
												<td>
													<b><?php echo __($guid, 'Evidence') ?></b><br/>
												</td>
												<td class="right">
													<div style='width: 300px; float: right; text-align: left; font-size: 115%; height: 24px; padding-top: 5px'>
														<?php
														if ($rowEnrol['evidenceType'] == 'Link') {
															echo "<a target='_blank' href='".$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
														} else {
															echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
														}
                                        				?>
													</div>
												</td>
											</tr>
										</table>
										<?php
										echo '<h4>';
                                        echo __($guid, 'Student Comment');
                                        echo '</h4>';
                                        echo '<p>';
                                        echo $rowEnrol['commentStudent'];
                                        echo '</p>';
                                    } elseif ($rowEnrol['status'] == 'Complete - Approved') { //Complete, show status and feedback from teacher.
										if ($schoolType == 'Physical') {
											echo '<h4>';
											echo __($guid, 'Complete - Approved');
											echo '</h4>';
											echo '<p>';
											echo __($guid, 'Congralutations! Your evidence, shown below, has been accepted and approved by your teacher(s), and so you have successfully completed this unit. Please look below for your teacher\'s comment.');
											echo '</p>';
										} else {
											echo '<h4>';
											echo __($guid, 'Complete');
											echo '</h4>';
											echo '<p>';
											echo __($guid, 'Congralutations! You have submitted your evidence, shown below, and so the unit is complete. Feel free to move on to another unit.');
											echo '</p>';
										}
                                        ?>
										<table class='smallIntBorder' cellspacing='0' style="width: 100%">
											<tr>
												<td>
													<b><?php echo __($guid, 'Status') ?></b><br/>
												</td>
												<td class="right">
													<input readonly style='width: 300px' type='text' value='Complete - Approved' />
												</td>
											</tr>
											<tr>
												<td>
													<b><?php echo __($guid, 'Evidence Type') ?></b><br/>
												</td>
												<td class="right">
													<input readonly style='width: 300px' type='text' value='<?php echo $rowEnrol['evidenceType'] ?>' />
												</td>
											</tr>
											<tr>
												<td>
													<b><?php echo __($guid, 'Evidence') ?></b><br/>
												</td>
												<td class="right">
													<div style='width: 300px; float: right; text-align: left; font-size: 115%; height: 24px; padding-top: 5px'>
														<?php
														if ($rowEnrol['evidenceType'] == 'Link') {
															echo "<a target='_blank' href='".$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
														} else {
															echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
														}
                                        				?>
													</div>
												</td>
											</tr>
										</table>
										<?php
										if ($schoolType == 'Physical') {
											echo '<h4>';
											echo __($guid, 'Teacher Comment');
											echo '</h4>';
											echo '<p>';
											echo $rowEnrol['commentApproval'];
											echo '</p>';
										}

                                        echo '<h4>';
                                        echo __($guid, 'Student Comment');
                                        echo '</h4>';
                                        echo '<p>';
                                        echo $rowEnrol['commentStudent'];
                                        echo '</p>';
                                    } elseif ($rowEnrol['status'] == 'Exempt') { //Exempt, let student know
                                        echo '<h4>';
                                        echo __($guid, 'Exempt');
                                        echo '</h4>';
                                        echo '<p>';
                                        echo __($guid, 'You are exempt from completing this unit, which means you get the status of completion, without needing to submit any evidence.');
                                        echo '</p>';
                                    }
                                } else { //Not enroled, give a chance to enrol
                                    echo '<h4>';
                                    echo __($guid, 'Enrol Now');
                                    echo '</h4>';
                                    ?>
									<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_browse_details_enrolProcess.php?address='.$_GET['q'] ?>">
										<table class='smallIntBorder' cellspacing='0' style="width: 100%">
											<?php
											if ($schoolType == 'Physical') {
												?>
												<tr>
													<td>
														<b><?php echo __($guid, 'Class') ?> *</b><br/>
														<span style="font-size: 90%"><i><?php echo __($guid, 'Which class are you enroling for?') ?></i></span>
													</td>
													<td class="right">
														<?php
														try {
															$dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
															$sqlClasses = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' ORDER BY course, class";
															$resultClasses = $connection2->prepare($sqlClasses);
															$resultClasses->execute($dataClasses);
														} catch (PDOException $e) {
														}
                                                        ?>
														<select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
															<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
															<?php
															while ($rowClasses = $resultClasses->fetch()) {
																echo "<option value='".$rowClasses['gibbonCourseClassID']."'>".$rowClasses['course'].'.'.$rowClasses['class'].'</option>';
															}
                                                        	?>
														</select>
														<script type="text/javascript">
															var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
															gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
														 </script>
													</td>
												</tr>
												<tr>
													<td style='width: 275px'>
														<b><?php echo __($guid, 'Grouping') ?> *</b><br/>
														<span style="font-size: 90%"><i><?php echo __($guid, 'How do you want to study this unit?') ?></i></span>
													</td>
													<td class="right">
														<select name="grouping" id="grouping" style="width: 302px">
															<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
															<?php
															$group = false;
															$extraSlots = 0;
															if (strpos($row['grouping'], 'Individual') !== false) {
																echo '<option value="Individual">Individual</option>';
															}
															if (strpos($row['grouping'], 'Pairs') !== false) {
																echo '<option value="Pairs">Pair</option>';
																$group = true;
																$extraSlots = 1;
															}
															if (strpos($row['grouping'], 'Threes') !== false) {
																echo '<option value="Threes">Three</option>';
																$group = true;
																$extraSlots = 2;
															}
															if (strpos($row['grouping'], 'Fours') !== false) {
																echo '<option value="Fours">Four</option>';
																$group = true;
																$extraSlots = 3;
															}
															if (strpos($row['grouping'], 'Fives') !== false) {
																echo '<option value="Fives">Five</option>';
																$group = true;
																$extraSlots = 4;
															}
															?>
														</select>
														<script type="text/javascript">
															var grouping=new LiveValidation('grouping');
															grouping.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
														 </script>
													</td>
												</tr>
												<?php
												if ($group) {
													//Get array of students
													$students = array();
													$studentCount = 0;
													try {
														$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
														$sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
														$resultSelect = $connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													} catch (PDOException $e) {
													}
													while ($rowSelect = $resultSelect->fetch()) {
														$students[$studentCount] = "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['rollGroup'].')</option>';
														++$studentCount;
													}

													//Controls for lists
													?>
													<script type='text/javascript'>
														$(document).ready(function(){
														$('tr.collaborator').css('display','none');
														<?php
														for ($i = 1; $i <= $extraSlots; ++$i) {
															echo 'collaborator'.$i.'.disable();';
														}
														?>
														$('#grouping').change(function(){
															if ($('select#grouping option:selected').val()=='Individual') {
																$('#trCollaborator1').css('display','none');
																collaborator1.disable() ;
																$('#trCollaborator2').css('display','none');
																collaborator2.disable() ;
																$('#trCollaborator3').css('display','none');
																collaborator3.disable() ;
																$('#trCollaborator4').css('display','none');
																collaborator4.disable() ;
															}
															else if ($('select#grouping option:selected').val()=='Pairs') {
																$('#trCollaborator1').css('display','table-row');
																collaborator1.enable() ;
																$('#trCollaborator2').css('display','none');
																collaborator2.disable() ;
																$('#trCollaborator3').css('display','none');
																collaborator3.disable() ;
																$('#trCollaborator4').css('display','none');
																collaborator4.disable() ;
															}
															else if ($('select#grouping option:selected').val()=='Threes') {
																$('#trCollaborator1').css('display','table-row');
																collaborator1.enable() ;
																$('#trCollaborator2').css('display','table-row');
																collaborator2.enable() ;
																$('#trCollaborator3').css('display','none');
																collaborator3.disable() ;
																$('#trCollaborator4').css('display','none');
																collaborator4.disable() ;
															}
															else if ($('select#grouping option:selected').val()=='Fours') {
																$('#trCollaborator1').css('display','table-row');
																collaborator1.enable() ;
																$('#trCollaborator2').css('display','table-row');
																collaborator2.enable() ;
																$('#trCollaborator3').css('display','table-row');
																collaborator3.enable() ;
																$('#trCollaborator4').css('display','none');
																collaborator4.disable() ;
															}
															else if ($('select#grouping option:selected').val()=='Fives') {
																$('#trCollaborator1').css('display','table-row');
																collaborator1.enable() ;
																$('#trCollaborator2').css('display','table-row');
																collaborator2.enable() ;
																$('#trCollaborator3').css('display','table-row');
																collaborator3.enable() ;
																$('#trCollaborator4').css('display','table-row');
																collaborator4.enable() ;
															}
															});
														});
													</script>

													<?php
													//Output select lists
													for ($i = 1; $i <= $extraSlots; ++$i) {
														?>
														<tr class='collaborator' id='<?php echo "trCollaborator$i" ?>'>
															<td style='width: 275px'>
																<b><?php echo sprintf(__($guid, 'Collaborator %1$s'), $i) ?> *</b><br/>
															</td>
															<td class="right">
																<select name="collaborators[]" id="collaborator<?php echo $i ?>" style="width: 302px">
																	<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
																	<?php
																	foreach ($students as $student) {
																		echo $student;
																	}
																	?>
																</select>
																<script type="text/javascript">
																	var collaborator<?php echo $i ?>=new LiveValidation('collaborator<?php echo $i ?>');
																	collaborator<?php echo $i ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
																 </script>
															</td>
														</tr>
														<?php
													}
												}
											}
											?>
											<tr>
												<td class="right" colspan=2>
													<input type="hidden" name="freeLearningUnitID" value="<?php echo $freeLearningUnitID ?>">
													<input type="submit" id="submit" value="Enrol Now">
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
                        echo '</div>';
                    }
                    echo "<div id='tabs2'>";
                    try {
                        $dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
                        $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';
                        $resultBlocks = $connection2->prepare($sqlBlocks);
                        $resultBlocks->execute($dataBlocks);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    $resourceContents = '';
                    $roleCategory = null;
                    if (isset($_SESSION[$guid]['gibbonRoleIDCurrent'])) {
                        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                    }

                    if ($resultBlocks->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        while ($rowBlocks = $resultBlocks->fetch()) {
                            if ($rowBlocks['title'] != '' or $rowBlocks['type'] != '' or $rowBlocks['length'] != '') {
                                echo "<div style='min-height: 35px'>";
                                echo "<div style='padding-left: 3px; width: 100%; float: left;'>";
                                if ($rowBlocks['title'] != '') {
                                    echo "<h3 style='padding-bottom: 2px'>";
                                    echo $rowBlocks['title'].'<br/>';
                                    echo "<div style='font-weight: normal; font-size: 75%; text-transform: none; margin-top: 5px'>";
                                    if ($rowBlocks['type'] != '') {
                                        echo $rowBlocks['type'];
                                        if ($rowBlocks['length'] != '') {
                                            echo ' | ';
                                        }
                                    }
                                    if ($rowBlocks['length'] != '') {
                                        echo $rowBlocks['length'].' min';
                                    }
                                    echo '</div>';
                                    echo '</h3>';
                                }
                                echo '</div>';

                                echo '</div>';
                            }
                            if ($rowBlocks['contents'] != '') {
                                echo "<div style='padding: 15px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'>".$rowBlocks['contents'].'</div>';
                                $resourceContents .= $rowBlocks['contents'];
                            }
                            if (isset($_SESSION[$guid]['username'])) {
                                if ($roleCategory == 'Staff') {
                                    if ($rowBlocks['teachersNotes'] != '') {
                                        echo "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>".__($guid, "Teacher's Notes").':</b></p> '.$rowBlocks['teachersNotes'].'</div>';
                                        $resourceContents .= $rowBlocks['teachersNotes'];
                                    }
                                }
                            }
                        }
                    }
                    echo '</div>';
                    echo "<div id='tabs3'>";
					//Resources
					$noReosurces = true;

					//Links
					$links = '';
                    $linksArray = array();
                    $linksCount = 0;
                    $dom = new DOMDocument();
                    @$dom->loadHTML($resourceContents);
                    foreach ($dom->getElementsByTagName('a') as $node) {
                        if ($node->nodeValue != '') {
                            $linksArray[$linksCount] = "<li><a href='".$node->getAttribute('href')."'>".$node->nodeValue.'</a></li>';
                            ++$linksCount;
                        }
                    }

                    $linksArray = array_unique($linksArray);
                    natcasesort($linksArray);

                    foreach ($linksArray as $link) {
                        $links .= $link;
                    }

                    if ($links != '') {
                        echo '<h2>';
                        echo 'Links';
                        echo '</h2>';
                        echo '<ul>';
                        echo $links;
                        echo '</ul>';
                        $noReosurces = false;
                    }

					//Images
					$images = '';
                    $imagesArray = array();
                    $imagesCount = 0;
                    $dom2 = new DOMDocument();
                    @$dom2->loadHTML($resourceContents);
                    foreach ($dom2->getElementsByTagName('img') as $node) {
                        if ($node->getAttribute('src') != '') {
                            $imagesArray[$imagesCount] = "<img class='resource' style='margin: 10px 0; max-width: 560px' src='".$node->getAttribute('src')."'/><br/>";
                            ++$imagesCount;
                        }
                    }

                    $imagesArray = array_unique($imagesArray);
                    natcasesort($imagesArray);

                    foreach ($imagesArray as $image) {
                        $images .= $image;
                    }

                    if ($images != '') {
                        echo '<h2>';
                        echo 'Images';
                        echo '</h2>';
                        echo $images;
                        $noReosurces = false;
                    }

					//Embeds
					$embeds = '';
                    $embedsArray = array();
                    $embedsCount = 0;
                    $dom2 = new DOMDocument();
                    @$dom2->loadHTML($resourceContents);
                    foreach ($dom2->getElementsByTagName('iframe') as $node) {
                        if ($node->getAttribute('src') != '') {
                            $embedsArray[$embedsCount] = "<iframe style='max-width: 560px' width='".$node->getAttribute('width')."' height='".$node->getAttribute('height')."' src='".$node->getAttribute('src')."' frameborder='".$node->getAttribute('frameborder')."'></iframe>";
                            ++$embedsCount;
                        }
                    }

                    $embedsArray = array_unique($embedsArray);
                    natcasesort($embedsArray);

                    foreach ($embedsArray as $embed) {
                        $embeds .= $embed.'<br/><br/>';
                    }

                    if ($embeds != '') {
                        echo '<h2>';
                        echo 'Embeds';
                        echo '</h2>';
                        echo $embeds;
                        $noReosurces = false;
                    }

					//No resources!
					if ($noReosurces) {
						echo "<div class='error'>";
						echo __($guid, 'There are no records to display.');
						echo '</div>';
					}
                    echo '</div>';
                    echo "<div id='tabs4'>";
						//Spit out outcomes
						try {
							$dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
							$sqlBlocks = "SELECT freeLearningUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM freeLearningUnitOutcome JOIN gibbonOutcome ON (freeLearningUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y' ORDER BY sequenceNumber";
							$resultBlocks = $connection2->prepare($sqlBlocks);
							$resultBlocks->execute($dataBlocks);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}
                    if ($resultBlocks->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Scope');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Category');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Name');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Year Groups');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Actions');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($rowBlocks = $resultBlocks->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }

							//COLOR ROW BY STATUS!
							echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo '<b>'.$rowBlocks['scope'].'</b><br/>';
                            if ($rowBlocks['scope'] == 'Learning Area' and @$rowBlocks['gibbonDepartmentID'] != '') {
                                try {
                                    $dataLearningArea = array('gibbonDepartmentID' => $rowBlocks['gibbonDepartmentID']);
                                    $sqlLearningArea = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
                                    $resultLearningArea = $connection2->prepare($sqlLearningArea);
                                    $resultLearningArea->execute($dataLearningArea);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultLearningArea->rowCount() == 1) {
                                    $rowLearningAreas = $resultLearningArea->fetch();
                                    echo "<span style='font-size: 75%; font-style: italic'>".$rowLearningAreas['name'].'</span>';
                                }
                            }
                            echo '</td>';
                            echo '<td>';
                            echo '<b>'.$rowBlocks['category'].'</b><br/>';
                            echo '</td>';
                            echo '<td>';
                            echo '<b>'.$rowBlocks['nameShort'].'</b><br/>';
                            echo "<span style='font-size: 75%; font-style: italic'>".$rowBlocks['name'].'</span>';
                            echo '</td>';
                            echo '<td>';
                            echo getYearGroupsFromIDList($guid, $connection2, $rowBlocks['gibbonYearGroupIDList']);
                            echo '</td>';
                            echo '<td>';
                            echo "<script type='text/javascript'>";
                            echo '$(document).ready(function(){';
                            echo "\$(\".description-$count\").hide();";
                            echo "\$(\".show_hide-$count\").fadeIn(1000);";
                            echo "\$(\".show_hide-$count\").click(function(){";
                            echo "\$(\".description-$count\").fadeToggle(1000);";
                            echo '});';
                            echo '});';
                            echo '</script>';
                            if ($rowBlocks['content'] != '') {
                                echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                            }
                            echo '</td>';
                            echo '</tr>';
                            if ($rowBlocks['content'] != '') {
                                echo "<tr class='description-$count' id='description-$count'>";
                                echo '<td colspan=6>';
                                echo $rowBlocks['content'];
                                echo '</td>';
                                echo '</tr>';
                            }
                            echo '</tr>';

                            ++$count;
                        }
                        echo '</table>';
                    }

                    echo '</div>';
                    echo "<div id='tabs5'>";
						//Spit out exemplar work
						try {
							$dataWork = array('freeLearningUnitID' => $freeLearningUnitID);
							$sqlWork = "SELECT freeLearningUnitStudent.*, surname, preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) WHERE freeLearningUnitID=:freeLearningUnitID AND exemplarWork='Y' ORDER BY timestampCompleteApproved DESC";
							$resultWork = $connection2->prepare($sqlWork);
							$resultWork->execute($dataWork);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}
                    if ($resultWork->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        while ($rowWork = $resultWork->fetch()) {
                            $students = '';
                            if ($rowWork['grouping'] == 'Individual') { //Created by a single student
                                        $students = formatName('', $rowWork['preferredName'], $rowWork['surname'], 'Student', false);
                            } else { //Created by a group of students
                                        try {
                                            $dataStudents = array('collaborationKey' => $rowWork['collaborationKey']);
                                            $sqlStudents = "SELECT surname, preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE active='Y' AND collaborationKey=:collaborationKey ORDER BY surname, preferredName";
                                            $resultStudents = $connection2->prepare($sqlStudents);
                                            $resultStudents->execute($dataStudents);
                                        } catch (PDOException $e) {
                                        }
                                while ($rowStudents = $resultStudents->fetch()) {
                                    $students .= formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', false).', ';
                                }
                                if ($students != '') {
                                    $students = substr($students, 0, -2);
                                    $students = preg_replace('/,([^,]*)$/', ' & \1', $students);
                                }
                            }

                            echo '<h3>';
                            echo $students." . <span style='font-size: 75%'>".__($guid, 'Shared on').' '.dateConvertBack($guid, $rowWork['timestampCompleteApproved']).'</span>';
                            echo '</h3>';
                                    //DISPLAY WORK.
                                    $extension = strrchr($rowWork['evidenceLocation'], '.');
                            if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) { //Its an image
                                        echo "<p style='text-align: center'>";
                                if ($rowWork['evidenceType'] == 'File') { //It's a file
                                                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'><img style='max-width: 550px' src='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'/></a>";
                                } else { //It's a link
                                                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'><img style='max-width: 550px' src='".$rowWork['evidenceLocation']."'/></a>";
                                }
                                echo '</p>';
                            } else { //Not an image
                                        echo '<p>';
                                if ($rowWork['evidenceType'] == 'File') { //It's a file
                                                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'>".__($guid, 'Click to View Work').'</a>';
                                } else { //It's a link
                                                echo "<a target='_blank' href='".$rowWork['evidenceLocation']."'>".__($guid, 'Click to View Work').'</a>';
                                }
                                echo '</p>';
                            }
                            echo '<p>';
                            if ($rowWork['commentStudent'] != '') {
                                echo '<b><u>'.__($guid, 'Student Comment').'</u></b><br/><br/>';
                                echo nl2br($rowWork['commentStudent']).'<br/>';
                            }
                            if ($rowWork['commentApproval'] != '') {
                                if ($rowWork['commentStudent'] != '') {
                                    echo '<br/>';
                                }
                                echo '<b><u>'.__($guid, 'Teacher Comment').'</u></b>';
                                echo $rowWork['commentApproval'].'<br/>';
                            }
                            echo '</p>';
                        }
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }
        }
    }
}
?>
