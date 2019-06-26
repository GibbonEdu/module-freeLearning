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
$canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php');
$browseAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php', 'Browse Units_all');

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == true or ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false))) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
        $highestAction = 'Browse Units_all';
        $roleCategory = null ;
    } else {
        $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
    }
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Get params
        $freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';

        $showInactive = $canManage && isset($_GET['showInactive'])
            ? $_GET['showInactive']
            : 'N';
        $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';

        $view = $_GET['view'] ?? 'list';
        if ($view != 'grid' and $view != 'map') {
            $view = 'list';
        }

        $gibbonPersonID = ($canManage)
            ? ($_GET['gibbonPersonID'] ?? $_SESSION[$guid]['gibbonPersonID'] ?? null)
            : $_SESSION[$guid]['gibbonPersonID'] ?? null;

        $urlParams = compact('showInactive', 'gibbonDepartmentID', 'difficulty', 'name', 'view', 'gibbonPersonID');

        //Breadcrumbs
        if ($roleCategory == null) {
            $page->breadcrumbs
                ->add(__m('Browse Units'), '/modules/Free Learning/units_browse.php', $urlParams)
                ->add(__m('Unit Details'));
        } else {
            $page->breadcrumbs
                ->add(__m('Browse Units'), 'units_browse.php', $urlParams)
                ->add(__m('Unit Details'));
        }

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        if ($freeLearningUnitID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $unitList = getUnitList($connection2, $guid, $gibbonPersonID, $roleCategory, $highestAction, null, null, null, $showInactive, $publicUnits, $freeLearningUnitID, null);
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
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'>".__($guid, 'Back to Search Results', 'Free Learning').'</a>';
                    echo '</div>';
                }

                $proceed = false;
                if ($highestAction == 'Browse Units_all') {
                    $proceed = true;
                } elseif ($highestAction == 'Browse Units_prerequisites') {
                    if ($row['freeLearningUnitIDPrerequisiteList'] == null or $row['freeLearningUnitIDPrerequisiteList'] == '') {
                        $proceed = true;
                    } else {
                        $prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
                        $prerequisitesMet = prerequisitesMet($connection2, $gibbonPersonID, $prerequisitesActive);
                        if ($prerequisitesMet) {
                            $proceed = true;
                        }
                    }
                }

                if ($proceed == false) {
                    echo "<div class='warning'>";
                    echo __($guid, 'You do not have access to this unit, as you have not yet met the prerequisites for it.', 'Free Learning');
                    echo '</div>';
                } else {
                    //Let's go!
                    if ($canManage || $browseAll) {
                        echo "<div class='linkTop'>";
                        if ($canManage) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a>";
                        }
                        if ($canManage & $browseAll) {
                            echo " | ";
                        }
                        if ($browseAll) {
                            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/modules/Free Learning/units_browse_details_export.php?freeLearningUnitID=$freeLearningUnitID'>".__($guid, 'Export')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'Export')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
                        }
                        echo '</div>';
                    }

                    //Get enrolment Details
                    try {
                        $dataEnrol = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $gibbonPersonID);
                        $sqlEnrol = 'SELECT freeLearningUnitStudent.*, gibbonPerson.surname, gibbonPerson.email, gibbonPerson.preferredName
                            FROM freeLearningUnitStudent
                            LEFT JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDSchoolMentor=gibbonPerson.gibbonPersonID)
                            WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID
                                AND gibbonPersonIDStudent=:gibbonPersonID
                                AND (freeLearningUnitStudent.status=\'Current\' OR freeLearningUnitStudent.status=\'Current - Pending\' OR freeLearningUnitStudent.status=\'Complete - Pending\' OR freeLearningUnitStudent.status=\'Evidence Not Yet Approved\')';
                        $resultEnrol = $connection2->prepare($sqlEnrol);
                        $resultEnrol->execute($dataEnrol);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    $rowEnrol = null;
                    if ($resultEnrol->rowCount()==1) { //ENROL NOW
                        $rowEnrol = $resultEnrol->fetch() ;
                    }

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 50%; vertical-align: middle'>";
                    echo "<span style='font-size: 150%; font-weight: bold'>".$row['name'].'</span><br/>';
                    echo '</td>';
                    echo "<td style='width: 50%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Time', 'Free Learning').'</span><br/>';
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
                        echo '<i>'.__($guid, 'N/A').'</i>';
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
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Difficulty', 'Free Learning').'</span><br/>';
                    echo '<i>'.$row['difficulty'].'<i>';
                    echo '</td>';
                    echo "<td style='padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Prerequisites', 'Free Learning').'</span><br/>';
                    $prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
                    if ($prerequisitesActive != false) {
                        $prerequisites = explode(',', $prerequisitesActive);
                        $units = getUnitsArray($connection2);
                        foreach ($prerequisites as $prerequisite) {
                            echo '<i>'.$units[$prerequisite][0].'</i><br/>';
                        }
                    } else {
                        echo '<i>'.__($guid, 'None', 'Free Learning').'<br/></i>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Departments', 'Free Learning').'</span><br/>';
                    $learningAreas = getLearningAreas($connection2, $guid);
                    if ($learningAreas == '') {
                        echo '<i>'.__($guid, 'No Learning Areas available.', 'Free Learning').'</i>';
                    } else {
                        for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                echo '<i>'.__($guid, $learningAreas[($i + 1)]).'</i><br/>';
                            }
                        }
                    }
                    echo '</td>';
                    echo "<td style='vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Authors', 'Free Learning').'</span><br/>';
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
                    echo '<tr>';
                    echo "<td style='vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Groupings', 'Free Learning').'</span><br/>';
                    if ($row['grouping'] != '') {
                        $groupings = explode(',', $row['grouping']);
                        foreach ($groupings as $grouping) {
                            echo ucwords($grouping).'<br/>';
                        }
                    }
                    echo '</td>';
                    echo "<td style='vertical-align: top'>";
                    if ($rowEnrol != null) {
                        if ($rowEnrol['enrolmentMethod'] == 'schoolMentor' or $rowEnrol['enrolmentMethod'] == 'externalMentor') {
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Mentor Contacts', 'Free Learning').'</span><br/>';
                            if ($rowEnrol['enrolmentMethod'] == 'schoolMentor') {
                                echo "<i>".formatName('', $rowEnrol['preferredName'], $rowEnrol['surname'], 'Student').'</i><br/>';
                                echo "<i><a href='mailto:".$rowEnrol['email']."'>".$rowEnrol['email'].'</a></i><br/>';
                            }
                            else if ($rowEnrol['enrolmentMethod'] == 'externalMentor') {
                                echo "<i>".$rowEnrol['nameExternalMentor'].'</i><br/>';
                                echo "<i><a href='mailto:".$rowEnrol['emailExternalMentor']."'>".$rowEnrol['emailExternalMentor'].'</a></i><br/>';
                            }
                        }
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    $defaultTab = 3;
                    if (!$canManage) {
                        $defaultTab = 2;
                    }
                    if (isset($_GET['tab'])) {
                        $defaultTab = $_GET['tab'];
                    }

                    echo "<div id='tabs' style='margin: 20px 0'>";
                    //Tab links
                    echo '<ul>';
                    echo "<li><a href='#tabs0'>".__($guid, 'Unit Overview', 'Free Learning').'</a></li>';
                    echo "<li><a href='#tabs1'>".__($guid, 'Enrol', 'Free Learning').'</a></li>';
                    if ($canManage) {
                        echo "<li><a href='#tabs2'>".__($guid, 'Manage Enrolment', 'Free Learning').'</a></li>';
                    }
                    echo "<li><a href='#tabs3'>".__($guid, 'Content', 'Free Learning').'</a></li>';
                    echo "<li><a href='#tabs4'>".__($guid, 'Resources', 'Free Learning').'</a></li>';
                    echo "<li><a href='#tabs5'>".__($guid, 'Outcomes', 'Free Learning').'</a></li>';
                    echo "<li><a href='#tabs6'>".__($guid, 'Exemplar Work', 'Free Learning').'</a></li>';
                    echo '</ul>';

                    //Tabs
                    echo "<div id='tabs0'>";
                    echo '<h3>';
                    echo __($guid, 'Blurb', 'Free Learning');
                    echo '</h3>';
                    echo '<p>';
                    echo $row['blurb'];
                    echo '</p>';
                    if ($row['license'] != '') {
                        echo '<h4>';
                        echo __($guid, 'License', 'Free Learning');
                        echo '</h4>';
                        echo '<p>';
                        echo __($guid, 'This work is shared under the following license:', 'Free Learning').' '.$row['license'];
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
                            echo __($guid, 'Below you can view the students currently enroled in this unit, including both those who are working on it, and those who are awaiting approval.', 'Free Learning');
                            echo '</p>';

                            //Check to see if we can set enrolmentType to "staffEdit" based on access to Manage Units_all
                            $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php', 'Manage Units_all');
                            $enrolmentType = '';
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
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_browse_details_enrolMultiple.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'>".__($guid, 'Add Multiple')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Add Multiple')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
                                echo '</div>';
                            }

                            //List students whose status is Current or Complete - Pending
                            try {
                                $dataClass = array('freeLearningUnitID' => $row['freeLearningUnitID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlClass = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.email, gibbonPerson.surname, gibbonPerson.preferredName, freeLearningUnitStudent.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, mentor.surname AS mentorsurname, mentor.preferredName AS mentorpreferredName, gibbonPerson.fields
                                    FROM freeLearningUnitStudent
                                        INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                                        LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                        LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                                        LEFT JOIN gibbonPerson AS mentor ON (freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID)
                                    WHERE freeLearningUnitID=:freeLearningUnitID
                                        AND gibbonPerson.status='Full'
                                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<='".date('Y-m-d')."')
                                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>='".date('Y-m-d')."')
                                        AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID
                                    ORDER BY FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt'), surname, preferredName";
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
                                            echo __($guid, 'Status', 'Free Learning') . '<br/>';
                                            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Enrolment Method', 'Free Learning').'</span>';
                                            ?>
                                        </th>
                                       <th>
                                            <?php echo __($guid, 'Class/Mentor', 'Free Learning') ?><br/>
                                        </th>
                                        <th>
                                            <?php echo __($guid, 'View') ?><br/>
                                        </th>
                                        <th>
                                            <?php echo __($guid, 'Action') ?><br/>
                                        </th>
                                    </tr>
                                    <?php

                                    //Get list of my classes before we start looping, for efficiency's sake
                                    $myClasses = array();
                                    try {
                                        $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' ORDER BY gibbonCourseClassID";
                                        $resultClasses = $connection2->prepare($sqlClasses);
                                        $resultClasses->execute($dataClasses);
                                    } catch (PDOException $e) {}

                                    if ($resultClasses->rowCount() > 0) {
                                        while ($rowClasses = $resultClasses->fetch()) {
                                            array_push($myClasses,$rowClasses['gibbonCourseClassID']);
                                        }
                                    }

                                    //Check for custom field
                                    $customField = getSettingByScope($connection2, 'Free Learning', 'customField');

                                    //Start looping through enrolments
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
                                                <?php
                                                    if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php')) {
                                                        echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowClass['gibbonPersonID']."'>".formatName('', $rowClass['preferredName'], $rowClass['surname'], 'Student', true).'</a><br/>';
                                                    }
                                                    else {
                                                        echo formatName('', $rowClass['preferredName'], $rowClass['surname'], 'Student', true).'<br/>';
                                                        echo "<a href='mailto:".$rowClass['email']."'>".$rowClass['email'].'</a><br/>';
                                                    }
                                                    $fields = unserialize($rowClass['fields']);
                                                    if (!empty($fields[$customField])) {
                                                        $value = $fields[$customField];
                                                        if ($value != '') {
                                                            echo "<span style='font-size: 85%; font-style: italic'>".$value.'</span>';
                                                        }
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                echo $rowClass['status'] . '<br/>';
                                                echo "<span style='font-size: 85%; font-style: italic'>".ucfirst(preg_replace('/(\w+)([A-Z])/U', '\\1 \\2', $rowClass['enrolmentMethod'])).'</span>';
                                                ?>
                                            </td>
                                            <?php
                                            echo '<td>';
                                                if ($rowClass['enrolmentMethod'] == 'class') {
                                                    if ($rowClass['course'] != '' and $rowClass['class'] != '') {
                                                        echo $rowClass['course'].'.'.$rowClass['class'];
                                                    } else {
                                                        echo '<i>'.__($guid, 'N/A').'</i>';
                                                    }
                                                }
                                                else if ($rowClass['enrolmentMethod'] == 'schoolMentor') {
                                                    echo formatName('', $rowClass['mentorpreferredName'], $rowClass['mentorsurname'], 'Student', false);
                                                }
                                                else if ($rowClass['enrolmentMethod'] == 'externalMentor') {
                                                    echo $rowClass['nameExternalMentor'];
                                                }
                                            echo '</td>';
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
                                                //Check to see if we can edit this class's enrolment (e.g. we have $manageAll or this is one of our classes or we are the mentor)
                                                $editEnrolment = false;
                                                if ($manageAll == true) {
                                                    $editEnrolment = true;
                                                }
                                                else {
                                                    if ($rowClass['enrolmentMethod'] == 'class') { //Is teacher of this class?
                                                        foreach ($myClasses AS $class) {
                                                            if ($rowClass['gibbonCourseClassID'] == $class) {
                                                                $editEnrolment = true;
                                                            }
                                                        }
                                                    }
                                                    else if ($rowClass['enrolmentMethod'] == 'schoolMentor' && $rowClass['gibbonPersonIDSchoolMentor'] == $_SESSION[$guid]['gibbonPersonID']) { //Is mentor of this student?
                                                        $editEnrolment = true;
                                                    }
                                                }

                                                //Layout the actions
                                                if ($enrolmentType == 'staffEdit' || $editEnrolment) {
                                                    if ($editEnrolment && ($rowClass['status'] == 'Complete - Pending' or $rowClass['status'] == 'Complete - Approved' or $rowClass['status'] == 'Evidence Not Yet Approved')) {
                                                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitStudentID='.$rowClass['freeLearningUnitStudentID'].'&freeLearningUnitID='.$rowClass['freeLearningUnitID']."&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                                    }
                                                    if ($editEnrolment) {
                                                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details_delete.php&freeLearningUnitStudentID='.$rowClass['freeLearningUnitStudentID'].'&freeLearningUnitID='.$rowClass['freeLearningUnitID']."&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                                                    }
                                                }
                                                if ($editEnrolment && $rowClass['status'] == 'Current - Pending' && $rowClass['enrolmentMethod'] == 'schoolMentor') {
                                                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Free Learning/units_mentorProcess.php?response=Y&freeLearningUnitStudentID=".$rowClass['freeLearningUnitStudentID']."&confirmationKey=".$rowClass['confirmationKey']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'><img title='".__($guid, 'Approve', 'Free Learning')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/><a/> ";
                                                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Free Learning/units_mentorProcess.php?response=N&freeLearningUnitStudentID=".$rowClass['freeLearningUnitStudentID']."&confirmationKey=".$rowClass['confirmationKey']."&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view'><img title='".__($guid, 'Reject', 'Free Learning')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/><a/>";
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
                                                echo '<b>'.__($guid, 'Student Comment', 'Free Learning').'</b><br/>';
                                                echo nl2br($rowClass['commentStudent']).'<br/>';
                                            }
                                            if ($rowClass['commentApproval'] != '') {
                                                if ($rowClass['commentStudent'] != '') {
                                                    echo '<br/>';
                                                }
                                                echo '<b>'.__($guid, 'Teacher Comment', 'Free Learning').'</b><br/>';
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
                        echo __($guid, 'Scope', 'Free Learning');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Category', 'Free Learning');
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
                                echo "<a title='".__($guid, 'View Description', 'Free Learning')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
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
                            $sqlWork = "SELECT freeLearningUnitStudent.*, preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) WHERE freeLearningUnitID=:freeLearningUnitID AND exemplarWork='Y' ORDER BY timestampCompleteApproved DESC";
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
                            $students = $rowWork['preferredName'];
                            } else { //Created by a group of students
                                        try {
                                            $dataStudents = array('collaborationKey' => $rowWork['collaborationKey']);
                                            $sqlStudents = "SELECT preferredName FROM freeLearningUnitStudent JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID) JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE active='Y' AND collaborationKey=:collaborationKey ORDER BY preferredName";
                                            $resultStudents = $connection2->prepare($sqlStudents);
                                            $resultStudents->execute($dataStudents);
                                        } catch (PDOException $e) {
                                        }
                                while ($rowStudents = $resultStudents->fetch()) {
                                    $students .= $rowStudents['preferredName'].', ';
                                }
                                if ($students != '') {
                                    $students = substr($students, 0, -2);
                                    $students = preg_replace('/,([^,]*)$/', ' & \1', $students);
                                }
                            }

                            echo '<h3>';
                            echo $students." . <span style='font-size: 75%'>".__($guid, 'Shared on', 'Free Learning').' '.dateConvertBack($guid, $rowWork['timestampCompleteApproved']).'</span>';
                            echo '</h3>';
                            //DISPLAY WORK.
                            echo '<h4 style=\'margin-top: 0px\'>'.__($guid, 'Student Work', 'Free Learning').'</h4>';
                            if ($rowWork['exemplarWorkEmbed'] =='') { //It's not an embed
                                $extension = strrchr($rowWork['evidenceLocation'], '.');
                                if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) { //Its an image
                                    echo "<p>";
                                    if ($rowWork['evidenceType'] == 'File') { //It's a file
                                        echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'/></a>";
                                    } else { //It's a link
                                        echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$rowWork['evidenceLocation']."'/></a>";
                                    }
                                    echo '</p>';
                                } else { //Not an image
                                    echo '<p class=\'button\'>';
                                    if ($rowWork['evidenceType'] == 'File') { //It's a file
                                        echo "<a class='button'target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['evidenceLocation']."'>".__($guid, 'Click to View Work', 'Free Learning').'</a>';
                                    } else { //It's a link
                                        echo "<a class='button' target='_blank' href='".$rowWork['evidenceLocation']."'>".__($guid, 'Click to View Work', 'Free Learning').'</a>';
                                    }
                                    echo '</p>';
                                }
                            } else {
                                echo '<p>';
                                print $rowWork['exemplarWorkEmbed'] ;
                                echo '</p>';
                            }
                            //DISPLAY STUDENT COMMENT
                            if ($rowWork['commentStudent'] != '') {
                                echo '<h4>'.__($guid, 'Student Comment', 'Free Learning').'</h4>';
                                echo '<p style=\'margin-bottom: 0px\'>';
                                echo nl2br($rowWork['commentStudent']);
                                echo '</p>';
                            }
                            //DISPLAY TEACHER COMMENT
                            if ($rowWork['commentApproval'] != '') {
                                if ($rowWork['commentStudent'] != '') {
                                    echo '<br/>';
                                }
                                echo '<h4>'.__($guid, 'Teacher Comment', 'Free Learning').'</h4>';
                                echo '<p>';
                                echo $rowWork['commentApproval'];
                                echo '</p>';
                            }
                        }
                    }
                    echo '</div>';
                    echo '</div>';

                    echo "<script type='text/javascript'>
                        $( \"#tabs\" ).tabs({
                                active: $defaultTab,
                                ajaxOptions: {
                                    error: function( xhr, status, index, anchor ) {
                                        $( anchor.hash ).html(
                                            \"Couldn't load this tab.\" );
                                    }
                                }
                            });
                    </script>";
                }
            }
        }
    }
}
?>
