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

use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

function getUnitList($connection2, $guid, $gibbonPersonID, $roleCategory, $highestAction, $gibbonDepartmentID = null, $difficulty = null, $name = null, $showInactive = null, $publicUnits = null, $freeLearningUnitID = null, $difficulties = null)
{
    $return = array();

    $sql = '';
    $data = array();
    $sqlWhere = 'AND ';
    //Apply filters
    if ($gibbonDepartmentID != '') {
        if (is_numeric($gibbonDepartmentID)) {
            $data['gibbonDepartmentID'] = $gibbonDepartmentID;
            $sqlWhere .= "gibbonDepartmentIDList LIKE concat('%', :gibbonDepartmentID, '%') AND ";
        }
        else {
            $data['course'] = $gibbonDepartmentID;
            $sqlWhere .= "course=:course AND ";
        }
    }
    if ($difficulty != '') {
        $data['difficulty'] = $difficulty;
        $sqlWhere .= 'difficulty=:difficulty AND ';
    }
    if ($name != '') {
        $data['name'] = $name;
        $sqlWhere .= "freeLearningUnit.name LIKE concat('%', :name, '%') AND ";
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
                $difficultyOrder .= ",'".__m($difficultyOption)."'";
            }
            $difficultyOrder .= '), ';
        }
    }

    //Do it!
    if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
        $sql = "SELECT DISTINCT freeLearningUnit.*, NULL AS status FROM freeLearningUnit WHERE sharedPublic='Y' AND gibbonYearGroupIDMinimum IS NULL AND active='Y' $sqlWhere ORDER BY $difficultyOrder name";
    } else {
        if ($highestAction == 'Browse Units_all') {
            $data['gibbonPersonID'] = $gibbonPersonID;
            if ($showInactive == 'Y') {
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE (active='Y' OR active='N') $sqlWhere ORDER BY $difficultyOrder name";
            } else {
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' $sqlWhere ORDER BY $difficultyOrder name";
            }
        } elseif ($highestAction == 'Browse Units_prerequisites') {
            if ($roleCategory == 'Student') {
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $data['gibbonPersonID2'] = $_SESSION[$guid]['gibbonPersonID'];
                $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2
                FROM freeLearningUnit
                LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID2)
                LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID)
                LEFT JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                LEFT JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID)
                WHERE active='Y' AND availableStudents='Y' $sqlWhere AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber)
                ORDER BY $difficultyOrder name";
            }
            else if ($roleCategory == 'Parent') {
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' AND availableParents='Y' $sqlWhere ORDER BY $difficultyOrder name";
            }
            else if ($roleCategory == 'Staff') {
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' AND availableStaff='Y' $sqlWhere ORDER BY $difficultyOrder name";
            }
            else if ($roleCategory == 'Other') {
                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $sql = "SELECT DISTINCT freeLearningUnit.*, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' AND availableOther='Y' $sqlWhere ORDER BY $difficultyOrder name";
            }
        }
    }

    $return[0] = $data;
    $return[1] = $sql;
    return $return;

}

function getStudentHistory($connection2, $guid, $gibbonPersonID, $summary = false)
{
    global $container, $page, $autoloader;

    // This is a HACK :( whole whole function needs ooified
    $autoloader->addPsr4('Gibbon\\Module\\FreeLearning\\', realpath(__DIR__.'/../../').'/modules/Free Learning/src');

    $unitStudentGateway = $container->get(UnitStudentGateway::class);

    $canBrowse = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php');

    $output = false;

    try {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name
            FROM
                gibbonPerson
                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND (gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID OR gibbonStudentEnrolment.gibbonSchoolYearID IS NULL))
                LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
            WHERE
                status='Full'
                AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."')
                AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."')
                AND gibbonPerson.gibbonPersonID=:gibbonPersonID
            ORDER BY surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $output .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() != 1) {
        $output .= "<div class='error'>";
        $output .= __('The specified record does not exist.');
        $output .= '</div>';
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            if ($summary == true) {
                $sql = "SELECT
                        freeLearningUnit.freeLearningUnitID, freeLearningUnitStudentID, enrolmentMethod, freeLearningUnit.name AS unit, GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea, freeLearningUnit.course AS flCourse, freeLearningUnitStudent.status, gibbonSchoolYear.name AS year, evidenceLocation, evidenceType, commentStudent, commentApproval, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timestampCompleteApproved, timestampJoined
                    FROM
                        freeLearningUnit
                        JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                        LEFT JOIN gibbonSchoolYear ON (freeLearningUnitStudent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                        LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                        LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                        LEFT JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%'))
                    WHERE
                        freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID AND
                        (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitStudent.status='Evidence Not Yet Approved' OR freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Complete - Pending')
                    GROUP BY
                        freeLearningUnitStudent.freeLearningUnitStudentID
                    ORDER BY
                        timestampJoined DESC, year DESC, status, unit
                    LIMIT 0, 8";
            } else {
                $sql = "SELECT
                    freeLearningUnit.freeLearningUnitID, freeLearningUnitStudentID, enrolmentMethod, freeLearningUnit.name AS unit, 'freeLearningUnit.freeLearningUnitID', GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea, freeLearningUnit.course AS flCourse, freeLearningUnitStudent.status, gibbonSchoolYear.name AS year, evidenceLocation, evidenceType, commentStudent, commentApproval, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, timestampCompleteApproved, timestampJoined
                FROM freeLearningUnit
                    JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    LEFT JOIN gibbonSchoolYear ON (freeLearningUnitStudent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    LEFT JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%'))
                WHERE
                    freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID
                GROUP BY
                    freeLearningUnitStudent.freeLearningUnitStudentID
                ORDER BY
                    timestampJoined DESC, year DESC, status, unit";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $output .= "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            $output .= "<div class='warning'>";
            $output .= __('There are no records to display.');
            $output .= '</div>';
        } else {
            $output .= "<table cellspacing='0' style='width: 100%'>";
            $output .= "<tr class='head'>";
            $output .= '<th>';
            $output .= __('School Year').'<br/>';
            $output .= "<span style='font-size: 85%; font-style: italic'>".__('Date').'</span>';
            $output .= '</th>';
            $output .= '<th>';
            $output .= __('Unit').'<br/>';
            $output .= "<span style='font-size: 85%; font-style: italic'>".__m('Learning Area')."/".__m('Course').'</span>';
            $output .= '</th>';
            $output .= '<th>';
                $output .= __m('Enrolment Method').'<br/>';
            $output .= '</th>';
            $output .= '<th>';
            $output .= __('Class');
            $output .= '</th>';
            $output .= '<th>';
            $output .= __('Status');
            $output .= '</th>';
            $output .= '<th>';
            $output .= __m('Evidence');
            $output .= '</th>';
            $output .= "<th style='width: 70px!important'>";
            $output .= __('Actions');
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
                $output .= '<td>';
                $output .= $row['year'].'<br/>';
                if ($row['status'] == 'Complete - Approved') {
                    $output .= "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, substr($row['timestampCompleteApproved'], 0, 10)).'</span>';
                } else {
                    $output .= "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, substr($row['timestampJoined'], 0, 10)).'</span>';
                }
                $output .= '</td>';
                $output .= '<td>';
                if ($canBrowse) {
                    $output .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$row['freeLearningUnitID']."&sidebar=true'>".$row['unit'].'</a><br/>';
                }
                else {
                    $output .= $row['unit']."<br/>";
                }
                $output .= !empty($row['learningArea']) ? '<div class="text-xxs">'.$row['learningArea'].'</div>' : '';
                $output .= !empty($row['flCourse']) && ($row['learningArea'] != $row['flCourse']) ? '<div class="text-xxs">'.$row['flCourse'].'</div>' : '';
                $output .= '</td>';
                $output .= '<td>';
                    $output .= ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $row["enrolmentMethod"])).'<br/>';
                $output .= '</td>';
                $output .= "<td>" ;
                    if ($row["course"]!="" AND $row["class"]!="") {
                        $output .= $row["course"] . "." . $row["class"] ;
                    } else {
                        $output .= "<i>" . __('N/A') . "</i>" ;
                    }
                $output .= "</td>" ;
                $output .= '<td>';
                $output .= $row['status'];
                $output .= '</td>';
                $output .= '<td>';
                if ($row['evidenceLocation'] != '') {
                    if ($row['evidenceType'] == 'Link') {
                        $output .= "<a target='_blank' href='".$row['evidenceLocation']."'>".__('View').'</>';
                    } else {
                        $output .= "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['evidenceLocation']."'>".__('View').'</>';
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
                    $output .= "<a title='".__('Show Comment')."' class='show_hide-".$row['freeLearningUnitStudentID']."' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
                }
                $output .= '</td>';
                $output .= '</tr>';
                if ($row['commentStudent'] != '' or $row['commentApproval'] != '') {

                    $output .= "<tr class='comment-".$row['freeLearningUnitStudentID']."' id='comment-".$row['freeLearningUnitStudentID']."'>";
                    $output .= '<td colspan=7>';

                    $logs = $unitStudentGateway->selectUnitStudentDiscussion($row['freeLearningUnitStudentID'])->fetchAll();

                    $output .= $page->fetchFromTemplate('ui/discussion.twig.html', [
                        'discussion' => $logs
                    ]);

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

//When $strict is false, a Complete - Pending unit counts as complete
function prerequisitesMet($connection2, $gibbonPersonID, $prerequisites, $strict = false)
{
    $return = false;

    //Get all courses completed
    $complete = array();
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        if ($strict) {
            $sql = "SELECT * FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND (status='Complete - Approved' OR status='Exempt') ORDER BY freeLearningUnitID";
        }
        else {
            $sql = "SELECT * FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND (status='Complete - Approved' OR status='Complete - Pending' OR status='Exempt') ORDER BY freeLearningUnitID";
        }
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

//Does not return errors, just does its best to get the job done
function grantBadges($connection2, $guid, $gibbonPersonID) {
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
    } catch (PDOException $e) { }

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
                            AND `grouping`='Individual'
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
                            AND NOT `grouping`='Individual'
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


            if ($row['specificUnitsComplete'] != '') { //SPECIFIC UNIT COMPLETION
                $hitsNeeded ++;

                $units = explode(',', $row['specificUnitsComplete']);
                $sqlCountWhere = ' AND (';
                $dataCount = array();
                foreach ($units AS $unit) {
                    $dataCount['unit'.$unit] = $unit;
                    $sqlCountWhere .= 'freeLearningUnitID=:unit'.$unit.' OR ';
                }
                $sqlCountWhere = substr($sqlCountWhere, 0, -4);
                $sqlCountWhere .= ')';

                try {
                    //Count conditions
                    $dataCount['gibbonPersonID'] = $gibbonPersonID;
                    $sqlCount = "SELECT freeLearningUnitStudentID
                        FROM freeLearningUnitStudent
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                            $sqlCountWhere
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) {}

                if ($resultCount->rowCount() == count($units)) {
                    $hitsActually ++;
                }
            }

            //GRANT AWARD
            if ($hitsNeeded > 0 AND $hitsActually == $hitsNeeded) {
                try {
                    $dataGrant = array('badgesBadgeID' => $row['badgesBadgeID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'), 'gibbonPersonID' => $gibbonPersonID, 'comment' => '', 'gibbonPersonIDCreator' => null);
                    $sqlGrant = 'INSERT INTO badgesBadgeStudent SET badgesBadgeID=:badgesBadgeID, gibbonSchoolYearID=:gibbonSchoolYearID, date=:date, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                    $resultGrant = $connection2->prepare($sqlGrant);
                    $resultGrant->execute($dataGrant);
                } catch (PDOException $e) {}

                //Notify User
                $notificationText = __m('Someone has granted you a badge.');
                setNotification($connection2, $guid, $gibbonPersonID, $notificationText, 'Badges', "/index.php?q=/modules/Badges/badges_view.php&gibbonPersonID=$gibbonPersonID");
            }
        }
    }
}

function getCourses($connection2)
{
    $return = false;

    try {
        $data = array();
        $sql = 'SELECT DISTINCT course FROM freeLearningUnit WHERE active=\'Y\' AND NOT course IS NULL AND NOT course=\'\' ORDER BY course';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    if ($result->rowCount() > 0) {
        $return = $result->fetchAll();
    }

    return $return;
}
?>
