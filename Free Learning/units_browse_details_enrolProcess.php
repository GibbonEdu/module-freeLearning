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

$highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);

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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&sidebar=true&tab=2';

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
        $schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

        $freeLearningUnitID = $_POST['freeLearningUnitID'];

        if ($freeLearningUnitID == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            try {
                $unitList = getUnitList($connection2, $guid, $_SESSION[$guid]['gibbonPersonID'], $roleCategory, $highestAction, $schoolType, $gibbonDepartmentID, $difficulty, $name, $showInactive, $applyAccessControls, $publicUnits, $freeLearningUnitID);
                $data = $unitList[0];
                $sql = $unitList[1];
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
                $row = $result->fetch();

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
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                } else {
                    if ($schoolType == 'Online') { //ONLINE
                        //Write to database
                        try {
                            $data = array('gibbonPersonIDStudent' => $_SESSION[$guid]['gibbonPersonID'], 'freeLearningUnitID' => $freeLearningUnitID);
                            $sql = "INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseClassID=NULL, grouping='Individual', collaborationKey='', freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=NULL, status='Current', timestampJoined='".date('Y-m-d H:i:s')."'";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            //Fail 2
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Success 0
                        $URL = $URL.'&return=success0';
                        header("Location: {$URL}");
                    } else { //PHYSICAL!
                        //Proceed!
                        //Validate Inputs
                        $enrolmentMethod = $_POST['enrolmentMethod'];
                        $gibbonCourseClassID = null;
                        $gibbonPersonIDSchoolMentor = null;
                        $emailExternalMentor = null;
                        $nameExternalMentor = null;
                        if ($enrolmentMethod =='class') {
                            $gibbonCourseClassID = $_POST['gibbonCourseClassID'];
                        } elseif ($enrolmentMethod =='schoolMentor') {
                            $gibbonPersonIDSchoolMentor = $_POST['gibbonPersonIDSchoolMentor'];
                        } elseif ($enrolmentMethod =='externalMentor') {
                            $emailExternalMentor = $_POST['emailExternalMentor'];
                            $nameExternalMentor = $_POST['nameExternalMentor'];
                        }
                        $grouping = $_POST['grouping'];
                        $collaborators = null;
                        if (isset($_POST['collaborators'])) {
                            $collaborators = $_POST['collaborators'];
                        }
                        if ($grouping == '' or ($enrolmentMethod =='class' and $gibbonCourseClassID =='') or ($enrolmentMethod =='schoolMentor' and $gibbonPersonIDSchoolMentor =='') or ($enrolmentMethod =='externalMentor' and ($emailExternalMentor =='' or $nameExternalMentor==''))) {
                            //Fail 3
                            $URL .= '&return=error3';
                            header("Location: {$URL}");
                        } else {
                            //If there are mentors, generate a unique confirmation key
                            $confirmationKey = null;
                            $unique = false;
                            if ($enrolmentMethod =='schoolMentor' or $enrolmentMethod =='externalMentor') {
                                $spinCount = 0;
                                while ($spinCount < 100 and $unique != true) {
                                    $confirmationKey = randomPassword(20);
                                    $checkFail = false;
                                    try {
                                        $data = array('confirmationKey' => $confirmationKey);
                                        $sql = 'SELECT * FROM freeLearningUnitStudent WHERE confirmationKey=:confirmationKey';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $checkFail = true;
                                    }
                                    if ($checkFail == false) {
                                        if ($result->rowCount() == 0) {
                                            $unique = true;
                                        }
                                    }
                                    ++$spinCount;
                                }

                                if ($unique == false) {
                                    //Fail 2
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }
                            }

                            //If there are collaborators, generate a unique collaboration key
                            $collaborationKey = null;
                            $unique = false;
                            if (is_array($collaborators)) {
                                $spinCount = 0;
                                while ($spinCount < 100 and $unique != true) {
                                    $collaborationKey = randomPassword(20);
                                    $checkFail = false;
                                    try {
                                        $data = array('collaborationKey' => $collaborationKey);
                                        $sql = 'SELECT * FROM freeLearningUnitStudent WHERE collaborationKey=:collaborationKey';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $checkFail = true;
                                    }
                                    if ($checkFail == false) {
                                        if ($result->rowCount() == 0) {
                                            $unique = true;
                                        }
                                    }
                                    ++$spinCount;
                                }

                                if ($unique == false) {
                                    //Fail 2
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }
                            }

                            //Check enrolment (and for collaborators too)
                            try {
                                if (count($collaborators) > 0) {
                                    $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $whereExtra = '' ;
                                    $collaboratorCount = 0;
                                    foreach ($collaborators AS $collaborator) {
                                        $data['gibbonPersonID'.$collaboratorCount] = $collaborator;
                                        $whereExtra .= ' OR gibbonPersonIDStudent=:gibbonPersonID'.$collaboratorCount ;
                                        $collaboratorCount ++;
                                    }
                                    $sql = 'SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitID=:freeLearningUnitID AND (gibbonPersonIDStudent=:gibbonPersonID'.$whereExtra.')';
                                }
                                else {
                                    $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sql = 'SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID';
                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }

                            if ($result->rowCount() > 0) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            } else {
                                //Write to database
                                try {
                                    $data = array('gibbonPersonIDStudent' => $_SESSION[$guid]['gibbonPersonID'], 'enrolmentMethod' => $enrolmentMethod, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDSchoolMentor' => $gibbonPersonIDSchoolMentor, 'emailExternalMentor' => $emailExternalMentor, 'nameExternalMentor' => $nameExternalMentor, 'grouping' => $grouping, 'confirmationKey' => $confirmationKey, 'collaborationKey' => $collaborationKey, 'freeLearningUnitID' => $freeLearningUnitID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sql = "INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonIDStudent, enrolmentMethod=:enrolmentMethod, gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonIDSchoolMentor=:gibbonPersonIDSchoolMentor, emailExternalMentor=:emailExternalMentor, nameExternalMentor=:nameExternalMentor, grouping=:grouping, confirmationKey=:confirmationKey, collaborationKey=:collaborationKey, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, status='Current', timestampJoined='".date('Y-m-d H:i:s')."'";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    //Fail 2
                                    print $e->getMessage() ; exit();
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }

                                //DEAL WITH COLLABORATORS (availability checked above)!
                                $partialFail = false;
                                if (is_array($collaborators)) {
                                    foreach ($collaborators as $collaborator) {
                                        //Write to database
                                        try {
                                            $data = array('gibbonPersonIDStudent' => $collaborator, 'enrolmentMethod' => $enrolmentMethod, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDSchoolMentor' => $gibbonPersonIDSchoolMentor, 'emailExternalMentor' => $emailExternalMentor, 'nameExternalMentor' => $nameExternalMentor, 'grouping' => $grouping, 'confirmationKey' => $confirmationKey, 'collaborationKey' => $collaborationKey, 'freeLearningUnitID' => $freeLearningUnitID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                            $sql = "INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonIDStudent, enrolmentMethod=:enrolmentMethod, gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonIDSchoolMentor=:gibbonPersonIDSchoolMentor, emailExternalMentor=:emailExternalMentor, nameExternalMentor=:nameExternalMentor, grouping=:grouping, confirmationKey=:confirmationKey, collaborationKey=:collaborationKey, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, status='Current', timestampJoined='".date('Y-m-d H:i:s')."'";
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $partialFail = true;
                                        }
                                    }
                                }

                                if ($partialFail == true) {
                                    //Fail 5
                                    $URL .= '&return=error5';
                                    header("Location: {$URL}");
                                } else {
                                    //Success 0
                                    $URL = $URL.'&return=success0';
                                    header("Location: {$URL}");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
