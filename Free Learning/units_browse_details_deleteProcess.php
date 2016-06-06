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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/units_browse_details_delete.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&freeLearningUnitStudentID='.$_POST['freeLearningUnitStudentID'].'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&applyAccessControls='.$applyAccessControls.'&sidebar=true&tab=2';
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&freeLearningUnitStudentID='.$_POST['freeLearningUnitStudentID'].'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&applyAccessControls='.$applyAccessControls.'&sidebar=true&tab=2';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_delete.php') == false and !$canManage) {
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
                $sql = 'SELECT * FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID';
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
                    //Write to database
                    try {
                        $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                        $sql = 'DELETE FROM freeLearningUnitStudent WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        //Fail 2
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit;
                    }

                    //Success 0
                    $URLDelete .= '&return=success0';
                    header("Location: {$URLDelete}");
                }
            }
        }
    }
}
