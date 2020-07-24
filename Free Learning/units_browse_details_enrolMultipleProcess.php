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

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details.php', $connection2);

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
if ($view != 'grid' and $view != 'map') {
    $view = 'list';
}
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
if ($canManage) {
    if (isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }
}


$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/units_browse_details_enrolMultiple.php&freeLearningUnitID='.$freeLearningUnitID.'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&tab=2&view='.$view;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false and !$canManage) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if ($highestAction == false) {
        //Fail 0
        $URL .= '&updateReturn=error0';
        header("Location: {$URL}");
    } else {
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        $freeLearningUnitID = '';
        if (isset($_GET['freeLearningUnitID'])) {
            $freeLearningUnitID = $_GET['freeLearningUnitID'];
        }

        if ($freeLearningUnitID == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            try {
                $unitList = getUnitList($connection2, $guid, $_SESSION[$guid]['gibbonPersonID'], $roleCategory, $highestAction, null, null, null, $showInactive, $publicUnits, $freeLearningUnitID, null);
                $data = $unitList[0];
                $sql = $unitList[1];
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
            }

            if ($result->rowCount() != 1) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $row = $result->fetch();

                //Proceed!
                if (isset($_POST['gibbonPersonIDMulti'])) {
                    $gibbonPersonIDMulti = $_POST['gibbonPersonIDMulti'];
                } else {
                    $gibbonPersonIDMulti = null;
                }
                $gibbonCourseClassID = $_POST['gibbonCourseClassID'];
                $status = $_POST['status'];

                if (is_null($gibbonPersonIDMulti) == true or $status == '' or $gibbonCourseClassID == '') {
                    //Fail 3
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
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
                        $URL .= '&updateReturn=error0';
                        header("Location: {$URL}");
                    } else {
                        $partialFail = false;

                        foreach ($gibbonPersonIDMulti as $gibbonPersonID) {
                            //Write to database
                            try {
                                $data = array('gibbonPersonID' => $gibbonPersonID, 'freeLearningUnitID' => $freeLearningUnitID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'grouping' => 'Individual', 'status' => $status);
                                $sql = 'INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonID, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonCourseClassID=:gibbonCourseClassID, `grouping`=:grouping, status=:status';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }

                        if ($partialFail == true) {
                            //Fail 5
                            $URL .= '&return=error5';
                            header("Location: {$URL}");
                        } else {
                            //Success 0
                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
