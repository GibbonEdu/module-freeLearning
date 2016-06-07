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
        $roleCategory = null ;
    } else {
        $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
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
        $applyAccessControls = 'Y';
        if ($canManage and isset($_GET['applyAccessControls'])) {
            $applyAccessControls = $_GET['applyAccessControls'];
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
        if ($view != 'grid' and $view != 'map') {
            $view = 'list';
        }

        echo "<div class='trail'>";
        if ($publicUnits == 'Y') {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&view=$view'>".__($guid, 'Browse Units')."</a> > </div><div class='trailEnd'>".__($guid, 'Unit Details').'</div>';
        } else {
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&view=$view'>".__($guid, 'Browse Units')."</a> > </div><div class='trailEnd'>".__($guid, 'Unit Details').'</div>';
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
                $unitList = getUnitList($connection2, $guid, $_SESSION[$guid]['gibbonPersonID'], $roleCategory, $highestAction, $schoolType, $gibbonDepartmentID, $difficulty, $name, $showInactive, $applyAccessControls, $publicUnits, $freeLearningUnitID);
                $data = $unitList[0];
                $sql = $unitList[1];
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
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&view=$view'>".__($guid, 'Back to Search Results').'</a>';
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
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&view=$view'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a>";
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

                    $defaultTab = 3;
                    if (!$canManage) {
                        $defaultTab = 2;
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
                    echo "<li><a href='#tabs1'>".__($guid, 'Enrol').'</a></li>';
                    if ($canManage) {
                        echo "<li><a href='#tabs2'>".__($guid, 'Manage Enrolment').'</a></li>';
                    }
                    echo "<li><a href='#tabs3'>".__($guid, 'Content').'</a></li>';
                    echo "<li><a href='#tabs4'>".__($guid, 'Resources').'</a></li>';
                    echo "<li><a href='#tabs5'>".__($guid, 'Outcomes').'</a></li>';
                    echo "<li><a href='#tabs6'>".__($guid, 'Exemplar Work').'</a></li>';
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
                    echo "<div id='tabs1'>";
                        //Enrolment screen spun into separate file for ease of coding
                        include './modules/Free Learning/units_browse_details_enrol.php';
                    echo '</div>';
                    if ($canManage) {
                        echo "<div id='tabs2'>";
                            echo '<p>';
                            echo __($guid, 'Below you can view the students currently enroled in this unit, including both those who are working on it, and those who are awaiting approval.');
                            echo '</p>';

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
                            if ($enrolmentType == 'staffEdit') {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_browse_details_enrolMultiple.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls'>".__($guid, 'Add Multiple')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add Multiple')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
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
                                        <th>
                                            <?php
                                            echo __($guid, 'Status') . '<br/>';
                                            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Enrolment Method').'</span>';
                                            ?>
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
                                            <td>
                                                <?php
                                                echo $rowClass['status'] . '<br/>';
                                                echo "<span style='font-size: 85%; font-style: italic'>".ucfirst(preg_replace('/(\w+)([A-Z])/U', '\\1 \\2', $rowClass['enrolmentMethod'])).'</span>';
                                                ?>
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
                                                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitStudentID='.$rowClass['freeLearningUnitStudentID'].'&freeLearningUnitID='.$rowClass['freeLearningUnitID']."&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&view=$view'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                                    }
                                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details_delete.php&freeLearningUnitStudentID='.$rowClass['freeLearningUnitStudentID'].'&freeLearningUnitID='.$rowClass['freeLearningUnitID']."&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&view=$view'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
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
                        echo "</div>";
                    }
                    echo "<div id='tabs3'>";
                        try {
                            $dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
                            $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';
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
                            $resourceContents = '';
                            while ($rowBlocks = $resultBlocks->fetch()) {
                                echo displayBlockContent($guid, $connection2, $rowBlocks['title'], $rowBlocks['type'], $rowBlocks['length'], $rowBlocks['contents'], $rowBlocks['teachersNotes'], $roleCategory);
                                $resourceContents .= $rowBlocks['contents'];
                            }
                        }
                    echo '</div>';
                    echo "<div id='tabs4'>";
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
                    echo "<div id='tabs5'>";
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
                    echo "<div id='tabs6'>";
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
