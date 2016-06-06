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

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');
$schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

@session_start();

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details_approval.php', $connection2);

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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&freeLearningUnitStudentID='.$_POST['freeLearningUnitStudentID'].'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&applyAccessControls='.$applyAccessControls.'&sidebar=true&tab=2';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false and !$canManage) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if ($highestAction == false) {
        //Fail 0
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        $freeLearningUnitID = $_POST['freeLearningUnitID'];
        $freeLearningUnitStudentID = $_POST['freeLearningUnitStudentID'];

        if ($freeLearningUnitID == '' or $freeLearningUnitStudentID == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                $sql = "SELECT * FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID AND (status='Complete - Pending' OR status='Complete - Approved' OR status='Evidence Not Approved')";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            } else {
                //Proceed!
                $row = $result->fetch();
                $name = $row['name'];
                $statusOriginal = $row['status'];
                $commentApprovalOriginal = $row['commentApproval'];

                $proceed = false;
                //Check to see if we can set enrolmentType to "staffEdit" based on access to Manage Units_all
                $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php', 'Manage Units_all');
                if ($manageAll == true) {
                    $proceed = true;
                } else {
                    //Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s)
                    $learningAreas = getLearningAreas($connection2, $guid, true);
                    if ($learningAreas != '') {
                        for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                $proceed = true;
                            }
                        }
                    }
                }

                if ($proceed == false) {
                    //Fail 0
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                } else {
                    //Get Inputs
                    $status = $_POST['status'];
                    $commentApproval = $_POST['commentApproval'];
                    $gibbonPersonIDStudent = $row['gibbonPersonIDStudent'];
                    $exemplarWork = $_POST['exemplarWork'];
                    $exemplarWorkLicense = '';
                    $attachment = '';
                    if ($exemplarWork == 'Y') {
                        $exemplarWorkLicense = $_POST['exemplarWorkLicense'];
                    }

                    //Validation
                    if ($commentApproval == '' or $exemplarWork == '') {
                        //Fail 3
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        if ($status == 'Complete - Approved') { //APPROVED!
                            //Move attached file, if there is one
                            if ($exemplarWork == 'Y') {
                                $attachment = $row['logo'];
                                $time = time();
                                if ($_FILES['file']['tmp_name'] != '') {
                                    //Check for folder in uploads based on today's date
                                    $path = $_SESSION[$guid]['absolutePath'];
                                    if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                                        mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                                    }
                                    $unique = false;
                                    $count = 0;
                                    while ($unique == false and $count < 100) {
                                        $suffix = randomPassword(16);
                                        $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $name)."_$suffix".strrchr($_FILES['file']['name'], '.');
                                        if (!(file_exists($path.'/'.$attachment))) {
                                            $unique = true;
                                        }
                                        ++$count;
                                    }

                                    if (!(move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$attachment))) {
                                        //Fail 5
                                        $URL .= '&return=error5';
                                        header("Location: {$URL}");
                                    }
                                    if ($attachment != null) {
                                        $attachment = $_SESSION[$guid]['absoluteURL'].'/'.$attachment;
                                    }
                                }
                            }

                            //Write to database
                            try {
                                $data = array('status' => $status, 'exemplarWork' => $exemplarWork, 'exemplarWorkThumb' => $attachment, 'exemplarWorkLicense' => $exemplarWorkLicense, 'commentApproval' => $commentApproval, 'gibbonPersonIDApproval' => $_SESSION[$guid]['gibbonPersonID'], 'timestampCompleteApproved' => date('Y-m-d H:i:s'), 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                                $sql = 'UPDATE freeLearningUnitStudent SET exemplarWork=:exemplarWork, exemplarWorkThumb=:exemplarWorkThumb, exemplarWorkLicense=:exemplarWorkLicense, status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit;
                            }

                            //Attempt to notify the student, and issue like
                            if ($statusOriginal != $status or $commentApprovalOriginal != $commentApproval) { //Only if status or comment has changed.
                                $text = sprintf(__($guid, 'A teacher has approved your request for unit completion (%1$s).'), $name);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=&difficulty=&name=&showInactive=&sidebar=true&tab=2";
                                setNotification($connection2, $guid, $gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);

                                setLike($connection2, 'Free Learning', $_SESSION[$guid]['gibbonSchoolYearID'], 'freeLearningUnitStudentID', $freeLearningUnitStudentID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonIDStudent, 'Unit Approval', '');
                            }

                            //Success 0
                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        } elseif ($status == 'Evidence Not Approved') { //NOT APPROVED
                            //Write to database
                            try {
                                $data = array('status' => $status, 'exemplarWork' => $exemplarWork, 'exemplarWorkThumb' => '', 'exemplarWorkLicense' => '', 'commentApproval' => $commentApproval, 'commentApproval' => $commentApproval, 'gibbonPersonIDApproval' => $_SESSION[$guid]['gibbonPersonID'], 'timestampCompleteApproved' => date('Y-m-d H:i:s'), 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                                $sql = 'UPDATE freeLearningUnitStudent SET exemplarWork=:exemplarWork, exemplarWorkThumb=:exemplarWorkThumb, exemplarWorkLicense=:exemplarWorkLicense, status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit;
                            }

                            //Attempt to notify the student
                            if ($statusOriginal != $status or $commentApprovalOriginal != $commentApproval) { //Only if status or comment has changed.
                                $text = sprintf(__($guid, 'A teacher has responded to your request for unit completion, but your evidence has not been approved (%1$s).'), $name);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&sidebar=true&tab=2";
                                setNotification($connection2, $guid, $gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);
                            }

                            //Success 0
                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        } else {
                            //Fail 3
                            $URL .= '&return=error3';
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
