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

//Check to see if system settings are set from databases
if (@$_SESSION[$guid]['systemSettingsSet'] == false) {
    getSystemSettings($guid, $connection2);
}

//Set return URL
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_mentor.php&sidebar=true';

//Get parameters
$response = null;
if (isset($_GET['response'])) {
    $response = $_GET['response'];
}
$freeLearningUnitStudentID = null;
if (isset($_GET['freeLearningUnitStudentID'])) {
    $freeLearningUnitStudentID = $_GET['freeLearningUnitStudentID'];
}
$confirmationKey = null;
if (isset($_GET['confirmationKey'])) {
    $confirmationKey = $_GET['confirmationKey'];
}

if ($response == '' or $freeLearningUnitStudentID == '' or $confirmationKey == '') {
    $URL .= '&return=error3';
    header("Location: {$URL}");
} else {
    //Check student & confirmation key
    try {
        $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'confirmationKey' => $confirmationKey) ;
        $sql = 'SELECT freeLearningUnitStudent.*, freeLearningUnit.name AS unit FROM freeLearningUnitStudent JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID AND confirmationKey=:confirmationKey AND status=\'Current - Pending\'';
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
        $unit = $row['unit'];
        $freeLearningUnitID = $row['freeLearningUnitID'];

        if ($response == 'Y') { //If yes, updated student and collaborators based on confirmation key
            try {
                $data = array('confirmationKey' => $confirmationKey) ;
                $sql = 'UPDATE freeLearningUnitStudent SET status=\'Current\' WHERE confirmationKey=:confirmationKey';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Notify student
            $notificationText = sprintf(__($guid, 'Your mentorship request for the Free Learning unit %1$s has been accepted.', 'Free Learning'), $unit);
            setNotification($connection2, $guid, $row['gibbonPersonIDStudent'], $notificationText, 'Free Learning', '/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$freeLearningUnitID.'&freeLearningUnitStudentID='.$freeLearningUnitStudentID.'&gibbonDepartmentID=&difficulty=&name=&sidebar=true&tab=1');

            //Return to thanks page
            $URL .= '&return=success1';
            header("Location: {$URL}");
        }
        else { //If no, delete the records
            try {
                $data = array('confirmationKey' => $confirmationKey) ;
                $sql = 'DELETE FROM freeLearningUnitStudent WHERE confirmationKey=:confirmationKey';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Notify student
            $notificationText = sprintf(__($guid, 'Your mentorship request for the Free Learning unit %1$s has been rejected. Your enrolment has been deleted.', 'Free Learning'), $unit);
            setNotification($connection2, $guid, $row['gibbonPersonIDStudent'], $notificationText, 'Free Learning', '/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$freeLearningUnitID.'&freeLearningUnitStudentID='.$freeLearningUnitStudentID.'&gibbonDepartmentID=&difficulty=&name=&sidebar=true&tab=1');

            //Return to thanks page
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
