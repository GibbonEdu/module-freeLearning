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

include '../../functions.php';
include '../../config.php';

include './moduleFunctions.php';

//New PDO DB connection
try {
    $connection2 = new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
    $connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getMessage();
}

@session_start();

//Get parameters
$freeLearningUnitStudentID = null;
if (isset($_POST['freeLearningUnitStudentID'])) {
    $freeLearningUnitStudentID = $_POST['freeLearningUnitStudentID'];
}
$freeLearningUnitID = null;
if (isset($_POST['freeLearningUnitID'])) {
    $freeLearningUnitID = $_POST['freeLearningUnitID'];
}
$confirmationKey = null;
if (isset($_POST['confirmationKey'])) {
    $confirmationKey = $_POST['confirmationKey'];
}

//Set return URL
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_mentor_approval.php&sidebar=true&freeLearningUnitStudentID=$freeLearningUnitStudentID&confirmationKey=$confirmationKey';

if ($freeLearningUnitStudentID == '' or $freeLearningUnitID == '' or $confirmationKey == '') {
    $URL .= '&return=error3';
    header("Location: {$URL}");
} else {
    //Check student & confirmation key
    try {
        $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'freeLearningUnitID' => $freeLearningUnitID, 'confirmationKey' => $confirmationKey) ;
        $sql = 'SELECT freeLearningUnitStudent.*, freeLearningUnit.name AS unit FROM freeLearningUnitStudent JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID AND freeLearningUnit.freeLearningUnitID=:freeLearningUnitID AND confirmationKey=:confirmationKey AND status=\'Complete - Pending\'';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    if ($result->rowCount()!=1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }
    else {
        $row = $result->fetch() ;
        $name = $row['unit'];

        //Get Inputs
        $status = $_POST['status'];
        $commentApproval = $_POST['commentApproval'];
        $gibbonPersonIDStudent = $row['gibbonPersonIDStudent'];

        //Validation
        if ($commentApproval == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            if ($status == 'Complete - Approved') { //APPROVED!
                //Write to database
                try {
                    $data = array('status' => $status, 'commentApproval' => $commentApproval, 'gibbonPersonIDApproval' => null, 'timestampCompleteApproved' => date('Y-m-d H:i:s'), 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                    $sql = 'UPDATE freeLearningUnitStudent SET status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit;
                }

                //Attempt to notify the student, issue like and grant awards
                $text = sprintf(__($guid, 'Your mentor has approved your request for unit completion (%1$s).', 'Free Learning'), $name);
                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=&difficulty=&name=&showInactive=&sidebar=true&tab=1";
                setNotification($connection2, $guid, $gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);
                setLike($connection2, 'Free Learning', $_SESSION[$guid]['gibbonSchoolYearID'], 'freeLearningUnitStudentID', $freeLearningUnitStudentID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonIDStudent, 'Unit Approval', '');
                grantBadges($connection2, $guid, $gibbonPersonIDStudent);


                $URL .= '&return=success0';
                header("Location: {$URL}");
            } elseif ($status == 'Evidence Not Yet Approved') { //NOT YET APPROVED
                //Write to database
                try {
                    $data = array('status' => $status, 'commentApproval' => $commentApproval, 'commentApproval' => $commentApproval, 'gibbonPersonIDApproval' => $_SESSION[$guid]['gibbonPersonID'], 'timestampCompleteApproved' => date('Y-m-d H:i:s'), 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                    $sql = 'UPDATE freeLearningUnitStudent SET status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit;
                }

                //Attempt to notify the student
                $text = sprintf(__($guid, 'Your mentor has responded to your request for unit completion, but your evidence has not been approved (%1$s).', 'Free Learning'), $name);
                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&sidebar=true&tab=1&view=$view";
                setNotification($connection2, $guid, $gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);

                $URL .= '&return=success1';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            }
        }
    }
}
