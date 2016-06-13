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

function getUnitList($connection2, $guid, $gibbonPersonID, $roleCategory, $highestAction, $schoolType, $gibbonDepartmentID, $difficulty, $name, $showInactive, $applyAccessControls, $publicUnits, $freeLearningUnitID = null, $difficulties = null)
{
    $return = array();

    $sql = '';
    $data = array();
    $sqlWhere = 'AND ';
    //Apply filters
    if ($gibbonDepartmentID != '') {
        $data['gibbonDepartmentID'] = $gibbonDepartmentID;
        $sqlWhere .= "gibbonDepartmentIDList LIKE concat('%', :gibbonDepartmentID, '%') AND ";
    }
    if ($difficulty != '') {
        $data['difficulty'] = $difficulty;
        $sqlWhere .= 'difficulty=:difficulty AND ';
    }
    if ($name != '') {
        $data['name'] = $name;
        $sqlWhere .= "freeLearningUnit.name LIKE concat('%', :name, '%') AND ";
    }
    if ($roleCategory != null and $applyAccessControls == 'Y') {
        if ($roleCategory == 'Staff') {
            $sqlWhere .= 'availableStaff=\'Y\' AND ';
        } elseif ($roleCategory == 'Student') {
            $sqlWhere .= 'availableStudents=\'Y\' AND ';
        } elseif ($roleCategory == 'Parent') {
            $sqlWhere .= 'availableParents=\'Y\' AND ';
        }
    }

    //Apply $freeLearningUnitID search
    if ($freeLearningUnitID != null) {
        $data['freeLearningUnitID'] = $freeLearningUnitID;
        $sqlWhere .= 'freeLearningUnit.freeLearningUnitID=:freeLearningUnitID AND ';
    }

    //Tidy up $sqlWhere
    if ($sqlWhere == 'AND ') {
        $sqlWhere = '';
    } else {
        $sqlWhere = substr($sqlWhere, 0, -5);
    }

    //Sort out difficulty order
    $difficultyOrder = '';
    if ($difficulties != null) {
        if ($difficulties != false) {
            $difficultyOrder = 'FIELD(difficulty';
            $difficulties = explode(',', $difficulties);
            foreach ($difficulties as $difficultyOption) {
                $difficultyOrder .= ",'".$difficultyOption."'";
            }
            $difficultyOrder .= '), ';
        }
    }

    //Do it!
    if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
        $sql = "SELECT DISTINCT freeLearningUnit.*, NULL AS status FROM freeLearningUnit WHERE sharedPublic='Y' AND gibbonYearGroupIDMinimum IS NULL AND active='Y' $sqlWhere ORDER BY $difficultyOrder name DESC";
    } else {
        if ($highestAction == 'Browse Units_all') {
            $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
            if ($showInactive == 'Y') {
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE (active='Y' OR active='N') $sqlWhere ORDER BY $difficultyOrder name DESC";
            } else {
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' $sqlWhere ORDER BY $difficultyOrder name DESC";
            }
        } elseif ($highestAction == 'Browse Units_prerequisites') {
            if ($schoolType == 'Physical') {
                if ($roleCategory == 'Student') {
                    $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                    $data['gibbonPersonID2'] = $_SESSION[$guid]['gibbonPersonID'];
                    $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                    $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2 FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID2) LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID) JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID) WHERE active='Y' $sqlWhere AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber) ORDER BY $difficultyOrder name";
                }
                else {
                    $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                    $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' $sqlWhere ORDER BY $difficultyOrder name";
                }
            } else {
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' $sqlWhere ORDER BY $difficultyOrder name";
            }
        }
    }

    $return[0] = $data;
    $return[1] = $sql;
    return $return;

}

function getStudentHistory($connection2, $guid, $gibbonPersonID, $summary = false)
{
    $output = false;

    $schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

    try {
        if ($schoolType == 'Physical') {
            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
        } else {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() != 1) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'The specified record does not exist.');
        $output .= '</div>';
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            if ($schoolType == 'Physical') {
                if ($summary == true) {
                    $sql = "SELECT freeLearningUnit.freeLearningUnitID, freeLearningUnitStudentID, freeLearningUnit.name AS unit, freeLearningUnitStudent.status, gibbonSchoolYear.name AS year, evidenceLocation, evidenceType, commentStudent, commentApproval, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timestampCompleteApproved, timestampJoined FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) JOIN gibbonSchoolYear ON (freeLearningUnitStudent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitStudent.status='Evidence Not Approved' OR freeLearningUnitStudent.status='Current') ORDER BY timestampJoined DESC, year DESC, status, unit LIMIT 0, 8";
                } else {
                    $sql = 'SELECT freeLearningUnit.freeLearningUnitID, freeLearningUnitStudentID, freeLearningUnit.name AS unit, freeLearningUnitStudent.status, gibbonSchoolYear.name AS year, evidenceLocation, evidenceType, commentStudent, commentApproval, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timestampCompleteApproved, timestampJoined FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) JOIN gibbonSchoolYear ON (freeLearningUnitStudent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID ORDER BY timestampJoined DESC, year DESC, status, unit';
                }
            } else {
                if ($summary == true) {
                    $sql = "SELECT freeLearningUnit.freeLearningUnitID, freeLearningUnitStudentID, freeLearningUnit.name AS unit, freeLearningUnitStudent.status, evidenceLocation, evidenceType, commentStudent, commentApproval, timestampCompleteApproved, timestampJoined FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitStudent.status='Evidence Not Approved' OR freeLearningUnitStudent.status='Current') ORDER BY timestampJoined DESC, status, unit LIMIT 0, 8";
                } else {
                    $sql = 'SELECT freeLearningUnit.freeLearningUnitID, freeLearningUnitStudentID, freeLearningUnit.name AS unit, freeLearningUnitStudent.status, evidenceLocation, evidenceType, commentStudent, commentApproval, timestampCompleteApproved, timestampJoined FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID ORDER BY timestampJoined DESC, status, unit';
                }
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $output .= "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            $output .= "<div class='error'>";
            $output .= __($guid, 'There are no records to display.');
            $output .= '</div>';
        } else {
            $output .= "<table cellspacing='0' style='width: 100%'>";
            $output .= "<tr class='head'>";
            if ($schoolType == 'Physical') {
                $output .= '<th>';
                $output .= __($guid, 'School Year').'<br/>';
                $output .= "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Date').'</span>';
                $output .= '</th>';
            }
            $output .= '<th>';
            $output .= __($guid, 'Unit');
            $output .= '</th>';
            if ($schoolType == 'Physical') {
                $output .= '<th>';
                $output .= __($guid, 'Class');
                $output .= '</th>';
            }
            $output .= '<th>';
            $output .= __($guid, 'Status');
            $output .= '</th>';
            $output .= '<th>';
            $output .= __($guid, 'Evidence');
            $output .= '</th>';
            $output .= "<th style='width: 70px!important'>";
            $output .= __($guid, 'Actions');
            $output .= '</th>';
            $output .= '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }

                ++$count;

                    //COLOR ROW BY STATUS!
                    $output .= "<tr class=$rowNum>";
                if ($schoolType == 'Physical') {
                    $output .= '<td>';
                    $output .= $row['year'].'<br/>';
                    if ($row['status'] == 'Complete - Approved') {
                        $output .= "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, substr($row['timestampCompleteApproved'], 0, 10)).'</span>';
                    } else {
                        $output .= "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, substr($row['timestampJoined'], 0, 10)).'</span>';
                    }
                    $output .= '</td>';
                }
                $output .= '<td>';
                $output .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$row['freeLearningUnitID']."&sidebar=true'>".$row['unit'].'</a>';
                $output .= '</td>';
                if ($schoolType == 'Physical') {
                    $output .= '<td>';
                    $output .= $row['course'].'.'.$row['class'];
                    $output .= '</td>';
                }
                $output .= '<td>';
                $output .= $row['status'];
                $output .= '</td>';
                $output .= '<td>';
                if ($row['evidenceLocation'] != '') {
                    if ($row['evidenceType'] == 'Link') {
                        $output .= "<a target='_blank' href='".$row['evidenceLocation']."'>".__($guid, 'View').'</>';
                    } else {
                        $output .= "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['evidenceLocation']."'>".__($guid, 'View').'</>';
                    }
                }
                $output .= '</td>';
                $output .= '<td>';
                if ($row['commentStudent'] != '') {
                    $output .= "<script type='text/javascript'>";
                    $output .= '$(document).ready(function(){';
                    $output .= '$(".comment-'.$row['freeLearningUnitStudentID'].'").hide();';
                    $output .= '$(".show_hide-'.$row['freeLearningUnitStudentID'].'").fadeIn(1000);';
                    $output .= '$(".show_hide-'.$row['freeLearningUnitStudentID'].'").click(function(){';
                    $output .= '$(".comment-'.$row['freeLearningUnitStudentID'].'").fadeToggle(1000);';
                    $output .= '});';
                    $output .= '});';
                    $output .= '</script>';
                    $output .= "<a title='".__($guid, 'Show Comment')."' class='show_hide-".$row['freeLearningUnitStudentID']."' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                }
                $output .= '</td>';
                $output .= '</tr>';
                if ($row['commentStudent'] != '' or $row['commentApproval'] != '') {
                    $output .= "<tr class='comment-".$row['freeLearningUnitStudentID']."' id='comment-".$row['freeLearningUnitStudentID']."'>";
                    $output .= '<td colspan=6>';
                    if ($row['commentStudent'] != '') {
                        $output .= '<b>'.__($guid, 'Student Comment').'</b><br/>';
                        $output .= nl2br($row['commentStudent']).'<br/>';
                    }
                    if ($row['commentApproval'] != '') {
                        if ($row['commentStudent'] != '') {
                            $output .= '<br/>';
                        }
                        $output .= '<b>'.__($guid, 'Teacher Comment').'</b><br/>';
                        $output .= nl2br($row['commentApproval']).'<br/>';
                    }
                    $output .= '</td>';
                    $output .= '</tr>';
                }
            }
            $output .= '</table>';
        }
    }

    return $output;
}

function prerequisitesRemoveInactive($connection2, $prerequisites)
{
    $return = false;

    if ($prerequisites == '') {
        $return = '';
    } else {
        $prerequisites = explode(',', $prerequisites);
        foreach ($prerequisites as $prerequisite) {
            try {
                $data = array('freeLearningUnitID' => $prerequisite);
                $sql = "SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }
            if ($result->rowCount() == 1) {
                $return .= $prerequisite.',';
            }
        }
        if (substr($return, -1) == ',') {
            $return = substr($return, 0, -1);
        }
    }

    return $return;
}

function prerquisitesMet($connection2, $gibbonPersonID, $prerequisites)
{
    $return = false;

    //Get all courses completed
    $complete = array();
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT * FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND (status='Complete - Approved' OR status='Exempt') ORDER BY freeLearningUnitID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        $complete[$row['freeLearningUnitID']] = true;
    }

    //Check prerequisites against courses completed
    if ($prerequisites == '') {
        $return = true;
    } else {
        $prerequisites = explode(',', $prerequisites);
        $prerequisiteCount = count($prerequisites);
        $prerequisiteMet = 0;
        foreach ($prerequisites as $prerequisite) {
            if (isset($complete[$prerequisite])) {
                ++$prerequisiteMet;
            }
        }
        if ($prerequisiteMet == $prerequisiteCount) {
            $return = true;
        }
    }

    return $return;
}

//Option second argument get's blocks only for selected units.
function getBlocksArray($connection2, $freeLearningUnitID = null)
{
    $return = false;
    try {
        if (is_null($freeLearningUnitID)) {
            $data = array();
            $sql = 'SELECT * FROM freeLearningUnitBlock ORDER BY freeLearningUnitID';
        } else {
            $data = array('freeLearningUnitID' => $freeLearningUnitID);
            $sql = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY freeLearningUnitID';
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$row['freeLearningUnitBlockID']][0] = $row['freeLearningUnitID'];
            $return[$row['freeLearningUnitBlockID']][1] = $row['title'];
            $return[$row['freeLearningUnitBlockID']][2] = $row['length'];
        }
    }

    return $return;
}

function getLearningAreaArray($connection2)
{
    $return = false;

    try {
        $data = array();
        $sql = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$row['gibbonDepartmentID']] = $row['name'];
        }
    }

    return $return;
}

//If $freeLearningUnitID is NULL, all units are returned: otherwise, only the specified
function getAuthorsArray($connection2, $freeLearningUnitID = null)
{
    $return = false;

    try {
        if (is_null($freeLearningUnitID)) {
            $data = array();
            $sql = 'SELECT freeLearningUnitAuthorID, freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS gibbonPersonsurname, gibbonPerson.preferredName AS gibbonPersonpreferredName, gibbonPerson.website AS gibbonPersonwebsite, gibbonPerson.gibbonPersonID, freeLearningUnitAuthor.surname AS freeLearningUnitAuthorsurname, freeLearningUnitAuthor.preferredName AS freeLearningUnitAuthorpreferredName, freeLearningUnitAuthor.website AS freeLearningUnitAuthorwebsite FROM freeLearningUnitAuthor LEFT JOIN gibbonPerson ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY gibbonPersonsurname, freeLearningUnitAuthorsurname, gibbonPersonpreferredName, freeLearningUnitAuthorpreferredName';
        } else {
            $data = array('freeLearningUnitID' => $freeLearningUnitID);
            $sql = 'SELECT freeLearningUnitAuthorID, freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS gibbonPersonsurname, gibbonPerson.preferredName AS gibbonPersonpreferredName, gibbonPerson.website AS gibbonPersonwebsite, gibbonPerson.gibbonPersonID, freeLearningUnitAuthor.surname AS freeLearningUnitAuthorsurname, freeLearningUnitAuthor.preferredName AS freeLearningUnitAuthorpreferredName, freeLearningUnitAuthor.website AS freeLearningUnitAuthorwebsite FROM freeLearningUnitAuthor LEFT JOIN gibbonPerson ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY gibbonPersonsurname, freeLearningUnitAuthorsurname, gibbonPersonpreferredName, freeLearningUnitAuthorpreferredName';
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            if ($row['gibbonPersonID'] != null) {
                $return[$row['freeLearningUnitAuthorID']][0] = $row['freeLearningUnitID'];
                $return[$row['freeLearningUnitAuthorID']][1] = formatName('', $row['gibbonPersonpreferredName'], $row['gibbonPersonsurname'], 'Student', false);
                $return[$row['freeLearningUnitAuthorID']][2] = $row['gibbonPersonID'];
                $return[$row['freeLearningUnitAuthorID']][3] = $row['gibbonPersonwebsite'];
            } else {
                $return[$row['freeLearningUnitAuthorID']][0] = $row['freeLearningUnitID'];
                $return[$row['freeLearningUnitAuthorID']][1] = formatName('', $row['freeLearningUnitAuthorpreferredName'], $row['freeLearningUnitAuthorsurname'], 'Student', false);
                $return[$row['freeLearningUnitAuthorID']][2] = $row['gibbonPersonID'];
                $return[$row['freeLearningUnitAuthorID']][3] = $row['freeLearningUnitAuthorwebsite'];
            }
        }
    }

    return $return;
}

function getUnitsArray($connection2)
{
    $return = false;

    try {
        $data = array();
        $sql = "SELECT * FROM freeLearningUnit WHERE active='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$row['freeLearningUnitID']][0] = $row['name'];
        }
    }

    return $return;
}

//Set $limit=TRUE to only return departments that the user has curriculum editing rights in
function getLearningAreas($connection2, $guid, $limit = false)
{
    $output = false;
    try {
        if ($limit == true) {
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE type='Learning Area' AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')  ORDER BY name";
        } else {
            $data = array();
            $sql = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
        while ($row = $result->fetch()) {
            $output .= $row['gibbonDepartmentID'].',';
            $output .= $row['name'].',';
        }
    } catch (PDOException $e) {
    }

    if ($output != false) {
        $output = substr($output, 0, (strlen($output) - 1));
        $output = explode(',', $output);
    }

    return $output;
}

//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
//Mode can be masterAdd, masterEdit, embed
//Outcomes is the result set of a mysql query of all outcomes from the unit the class belongs to
function makeBlock($guid, $connection2, $i, $mode = 'masterAdd', $title = '', $type = '', $length = '', $contents = '', $complete = 'N', $freeLearningUnitBlockID = '', $freeLearningUnitClassBlockID = '', $teachersNotes = '', $outerBlock = true)
{
    if ($outerBlock) { echo "<div id='blockOuter$i' class='blockOuter'>";
    }
    if ($mode != 'embed') {
        ?>
		<style>
			.sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
			.sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 72px; }
			div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 72px; }
			html>body .sortable li { min-height: 58px; line-height: 1.2em; }
			.sortable .ui-state-highlight { margin-bottom: 5px; min-height: 72px; line-height: 1.2em; width: 100%; }
		</style>

		<script type='text/javascript'>
			$(function() {
				$( ".sortable" ).sortable({
					placeholder: "ui-state-highlight"
				});

				$( ".sortable" ).bind( "sortstart", function(event, ui) {
					$("#blockInner<?php echo $i ?>").css("display","none") ;
					$("#block<?php echo $i ?>").css("height","72px") ;
					$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
					tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
					tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
					$(".sortable").sortable( "refresh" ) ;
					$(".sortable").sortable( "refreshPositions" ) ;
				});
			});

		</script>
		<script type='text/javascript'>
			$(document).ready(function(){
				$("#blockInner<?php echo $i ?>").css("display","none");
				$("#block<?php echo $i ?>").css("height","72px")

				//Block contents control
				$('#show<?php echo $i ?>').unbind('click').click(function() {
					if ($("#blockInner<?php echo $i ?>").is(":visible")) {
						$("#blockInner<?php echo $i ?>").css("display","none");
						$("#block<?php echo $i ?>").css("height","72px")
						$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
					} else {
						$("#blockInner<?php echo $i ?>").slideDown("fast", $("#blockInner<?php echo $i ?>").css("display","table-row"));
						$("#block<?php echo $i ?>").css("height","auto")
						$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/minus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, 'teachersNotes<?php echo $i ?>') ;
					}
				});

				<?php if ($mode == 'masterAdd') {
    			?>
					var titleClick<?php echo $i ?>=false ;
					$('#title<?php echo $i ?>').focus(function() {
						if (titleClick<?php echo $i ?>==false) {
							$('#title<?php echo $i ?>').css("color", "#000") ;
							$('#title<?php echo $i ?>').val("") ;
							titleClick<?php echo $i ?>=true ;
						}
					});

					var typeClick<?php echo $i ?>=false ;
					$('#type<?php echo $i ?>').focus(function() {
						if (typeClick<?php echo $i ?>==false) {
							$('#type<?php echo $i ?>').css("color", "#000") ;
							$('#type<?php echo $i ?>').val("") ;
							typeClick<?php echo $i ?>=true ;
						}
					});

					var lengthClick<?php echo $i ?>=false ;
					$('#length<?php echo $i ?>').focus(function() {
						if (lengthClick<?php echo $i ?>==false) {
							$('#length<?php echo $i ?>').css("color", "#000") ;
							$('#length<?php echo $i ?>').val("") ;
							lengthClick<?php echo $i ?>=true ;
						}
					});
				<?php
				}
				?>

				$('#delete<?php echo $i ?>').unbind('click').click(function() {
					if (confirm("<?php echo __($guid, 'Are you sure you want to delete this record?') ?>")) {
						$('#block<?php echo $i ?>').fadeOut(600, function(){ $('#block<?php echo $i ?>').remove(); });
					}
				});
			});
		</script>
		<?php

    }
    ?>
	<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="block<?php echo $i ?>" style='padding: 0px'>
		<table class='blank' cellspacing='0' style='width: 100%'>
			<tr>
				<td style='width: 50%'>
					<input name='order[]' type='hidden' value='<?php echo $i ?>'>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=100 id='title<?php echo $i ?>' name='title<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode == 'masterAdd') { echo 'color: #999;'; } ?> margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php if ($mode == 'masterAdd') { echo sprintf(__($guid, 'Block %1$s'), $i); } else { echo htmlPrep($title); } ?>'><br/>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=50 id='type<?php echo $i ?>' name='type<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode == 'masterAdd') { echo 'color: #999;'; } ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php if ($mode == 'masterAdd') { echo __($guid, 'type (e.g. discussion, outcome)'); } else { echo htmlPrep($type); } ?>'>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=3 id='length<?php echo $i ?>' name='length<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode == 'masterAdd') { echo 'color: #999;'; } ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 95px' value='<?php if ($mode == 'masterAdd') { echo __($guid, 'length (min)'); } else { echo htmlPrep($length); } ?>'>
				</td>
				<td style='text-align: right; width: 50%'>
					<div style='margin-bottom: 5px'>
						<?php
                        echo "<img id='delete$i' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/> "; echo "<div title='".__($guid, 'Show/Hide Details')."' id='show$i' style='margin-right: 3px; margin-top: -1px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\"); background-repeat: no-repeat'></div></br>"; ?>
					</div>
					<input type='hidden' name='freeLearningUnitBlockID<?php echo $i ?>' value='<?php echo $freeLearningUnitBlockID ?>'>
					<input type='hidden' name='freeLearningUnitClassBlockID<?php echo $i ?>' value='<?php echo $freeLearningUnitClassBlockID ?>'>
				</td>
			</tr>
			<tr id="blockInner<?php echo $i ?>">
				<td colspan=2 style='vertical-align: top'>
					<?php
                    if ($mode == 'masterAdd') {
                        $contents = getSettingByScope($connection2, 'Planner', 'smartBlockTemplate');
                    }
    				echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Block Contents').'</div>';
                    //Block Contents
                    if ($mode != 'embed') {
                        echo getEditor($guid, false, "contents$i", $contents, 20, true, false, false, true);
                    } else {
                        echo "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important'><p>$contents</p></div>";
                    }

                    //Teacher's Notes
                    if ($mode != 'embed') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Teacher\'s Notes').'</div>';
                        echo getEditor($guid, false, "teachersNotes$i", $teachersNotes, 20, true, false, false, true);
                    } elseif ($teachersNotes != '') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__($guid, 'Teacher\'s Notes').'</div>';
                        echo "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important; background-color: #F6CECB'><p>$teachersNotes</p></div>";
                    }
    				?>
				</td>
			</tr>
		</table>
	</div>
	<?php
    if ($outerBlock) {
        echo '</div>';
    }
}

//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
function makeBlockOutcome($guid,  $i, $type = '', $gibbonOutcomeID = '', $title = '', $category = '', $contents = '', $id = '', $outerBlock = true, $allowOutcomeEditing = 'Y')
{
    if ($outerBlock) { echo "<div id='".$type."blockOuter$i' class='blockOuter'>";
    }
    ?>
		<script>
			$(function() {
				$( "#<?php echo $type ?>" ).sortable({
					placeholder: "<?php echo $type ?>-ui-state-highlight"
				});

				$( "#<?php echo $type ?>" ).bind( "sortstart", function(event, ui) {
					$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
					$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;
					$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
					tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					$("#<?php echo $type ?>").sortable( "refreshPositions" ) ;
				});

				$( "#<?php echo $type ?>" ).bind( "sortstop", function(event, ui) {
					//This line has been removed to improve performance with long lists
					//tinyMCE.execCommand('mceAddEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;
				});
			});
		</script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
				$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;

				//Block contents control
				$('#<?php echo $type ?>show<?php echo $i ?>').unbind('click').click(function() {
					if ($("#<?php echo $type ?>BlockInner<?php echo $i ?>").is(":visible")) {
						$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
						$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","72px") ;
						$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					} else {
						$("#<?php echo $type ?>BlockInner<?php echo $i ?>").slideDown("fast", $("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","table-row"));
						$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","auto")
						$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/minus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					}
				});

				$('#<?php echo $type ?>delete<?php echo $i ?>').unbind('click').click(function() {
					if (confirm("Are you sure you want to delete this record?")) {
						$('#<?php echo $type ?>blockOuter<?php echo $i ?>').fadeOut(600, function(){ $('#<?php echo $type ?><?php echo $i ?>'); });
						$('#<?php echo $type ?>blockOuter<?php echo $i ?>').remove();
						<?php echo $type ?>Used[<?php echo $type ?>Used.indexOf("<?php echo $gibbonOutcomeID ?>")]="x" ;
					}
				});

			});
		</script>
		<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="<?php echo $type ?>Block<?php echo $i ?>" style='padding: 0px'>
			<table class='blank' cellspacing='0' style='width: 100%'>
				<tr>
					<td style='width: 50%'>
						<input name='<?php echo $type ?>order[]' type='hidden' value='<?php echo $i ?>'>
						<input name='<?php echo $type ?>gibbonOutcomeID<?php echo $i ?>' type='hidden' value='<?php echo $gibbonOutcomeID ?>'>
						<input readonly maxlength=100 id='<?php echo $type ?>title<?php echo $i ?>' name='<?php echo $type ?>title<?php echo $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php echo $title; ?>'><br/>
						<input readonly maxlength=100 id='<?php echo $type ?>category<?php echo $i ?>' name='<?php echo $type ?>category<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php echo $category; ?>'>
						<script type="text/javascript">
							if($('#<?php echo $type ?>category<?php echo $i ?>').val()=="") {
								$('#<?php echo $type ?>category<?php echo $i ?>').css("border","none") ;
							}
						</script>
					</td>
					<td style='text-align: right; width: 50%'>
						<div style='margin-bottom: 25px'>
							<?php
                            echo "<img id='".$type."delete$i' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/> "; echo "<div id='".$type."show$i' title='".__($guid, 'Show/Hide Details')."' style='margin-right: 3px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/plus.png\"); background-repeat: no-repeat'></div>"; ?>
						</div>
						<input type='hidden' name='id<?php echo $i ?>' value='<?php echo $id ?>'>
					</td>
				</tr>
				<tr id="<?php echo $type ?>BlockInner<?php echo $i ?>">
					<td colspan=2 style='vertical-align: top'>
						<?php
                            if ($allowOutcomeEditing == 'Y') {
                                echo getEditor($guid, false, $type.'contents'.$i, $contents, 20, false, false, false, true);
                            } else {
                                echo "<div style='padding: 5px'>$contents</div>";
                                echo "<input type='hidden' name='".$type.'contents'.$i."' value='".htmlPrep($contents)."'/>";
                            }
   						?>
					</td>
				</tr>
			</table>
		</div>
	<?php
    if ($outerBlock) {
        echo '</div>';
    }
}

function displayBlockContent($guid, $connection2, $title, $type, $length, $contents, $teachersNotes, $roleCategory = null)
{
    $return = false;

    if ($title != '' or $type != '' or $length != '') {
        $return .= "<div style='min-height: 35px'>";
        $return .= "<div style='padding-left: 3px; width: 100%; float: left;'>";
        if ($title != '') {
            $return .= "<h4 style='padding-bottom: 2px'>";
            $return .= $title.'<br/>';
            $return .= "<div style='font-weight: normal; font-size: 75%; text-transform: none; margin-top: 5px'>";
            if ($type != '') {
                $return .= $type;
                if ($length != '') {
                    $return .= ' | ';
                }
            }
            if ($length != '') {
                $return .= $length.' min';
            }
            $return .= '</div>';
            $return .= '</h4>';
        }
        $return .= '</div>';

        $return .= '</div>';
    }
    if ($contents != '') {
        $return .= "<div style='margin-top:20px; padding: 15px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'>".$contents.'</div>';
    }
    if (isset($_SESSION[$guid]['username'])) {
        if ($roleCategory == 'Staff') {
            if ($teachersNotes != '') {
                $return .= "<div style='margin-top:20px; background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>".__($guid, "Teacher's Notes").':</b></p> '.$teachersNotes.'</div>';
                $resourceContents .= $teachersNotes;
            }
        }
    }

    return $return;

}

//Does not return errors, just does its best to get the job done
function grantAwards($connection2, $guid, $gibbonPersonID) {
    //Sort out difficulty order
    $difficulties = getSettingByScope($connection2, 'Free Learning', 'difficultyOptions');
    if ($difficulties != false) {
        $difficulties = explode(',', $difficulties);
    }

    //Get list of active awards, including details on those already issued
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT freeLearningBadge.*, gibbonPersonID
            FROM freeLearningBadge
                JOIN badgesBadge ON (freeLearningBadge.badgesBadgeID=badgesBadge.badgesBadgeID)
                LEFT JOIN badgesBadgeStudent ON (badgesBadgeStudent.badgesBadgeID=badgesBadge.badgesBadgeID AND gibbonPersonID=:gibbonPersonID)
            WHERE
                freeLearningBadge.active='Y'
                AND badgesBadge.active='Y'
        ";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) { print $e->getMessage();}

    while ($row = $result->fetch()) {
        if (is_null($row['gibbonPersonID'])) { //Only work on awards not yet given to this person
            $hitsNeeded = 0 ;
            $hitsActually = 0 ;
            //CHECK AWARD CONDITIONS
            if ($row['unitsCompleteTotal'] > 0) { //UNITS COMPLETE TOTAL
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT freeLearningUnitStudentID FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND status='Complete - Approved'";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteTotal']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteThisYear'] > 0) { //UNITS COMPLETE THIS YEAR
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlCount = "SELECT freeLearningUnitStudentID FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status='Complete - Approved'";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteThisYear']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteDepartmentCount'] > 0) { //UNITS COMPLETE DEPARTMENT COUNT
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT DISTINCT gibbonDepartment.name
                        FROM freeLearningUnitStudent
                            JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                            JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE concat( '%', gibbonDepartment.gibbonDepartmentID, '%' ))
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteDepartmentCount']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteIndividual'] > 0) { //UNITS COMPLETE INDIVIDUAL
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT freeLearningUnitStudentID
                        FROM freeLearningUnitStudent
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                            AND grouping='Individual'
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteIndividual']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteGroup'] > 0) { //UNITS COMPLETE GROUP
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT freeLearningUnitStudentID
                        FROM freeLearningUnitStudent
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                            AND NOT grouping='Individual'
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteGroup']) {
                    $hitsActually ++;
                }
            }

            if (!is_null($row['difficultyLevelMaxAchieved']) AND count($difficulties) > 0) { //UNITS COMPLETE GROUP
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT DISTINCT difficulty
                        FROM freeLearningUnitStudent
                            JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                $rowCountAll = $resultCount->fetchAll();
                $minReached = false ;
                $minAchieved = false ;

                foreach ($difficulties AS $difficulty) {
                    if ($difficulty == $row['difficultyLevelMaxAchieved']) {
                        $minReached = true ;
                    }

                    if ($minReached) {
                        foreach ($rowCountAll AS $rowCount) {
                            if ($rowCount['difficulty'] == $row['difficultyLevelMaxAchieved']) {
                                $minAchieved = true;
                            }

                        }
                    }
                }

                if ($minAchieved) {
                    $hitsActually ++;
                }
            }

            //GRANT AWARD
            if ($hitsNeeded > 0 AND $hitsActually == $hitsNeeded) {
                try {
                    $dataGrant = array('badgesBadgeID' => $row['badgesBadgeID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'), 'gibbonPersonID' => $gibbonPersonID, 'comment' => '', 'gibbonPersonIDCreator' => 1);
                    $sqlGrant = 'INSERT INTO badgesBadgeStudent SET badgesBadgeID=:badgesBadgeID, gibbonSchoolYearID=:gibbonSchoolYearID, date=:date, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                    $resultGrant = $connection2->prepare($sqlGrant);
                    $resultGrant->execute($dataGrant);
                } catch (PDOException $e) {}

                //Notify User
                $notificationText = __($guid, 'Someone has granted you a badge.');
                setNotification($connection2, $guid, $gibbonPersonID, $notificationText, 'Badges', "/index.php?q=/modules/Badges/badges_view.php&gibbonPersonID=$gibbonPersonID");
            }
        }

    }
}
?>
