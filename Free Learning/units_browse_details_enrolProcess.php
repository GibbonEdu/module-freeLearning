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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&sidebar=true&tab=1';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
        //Fail 0
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

        $freeLearningUnitID = $_POST['freeLearningUnitID'];

        if ($freeLearningUnitID == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            try {
                if ($highestAction == 'Browse Units_all' or $schoolType == 'Online') {
                    $data = array('freeLearningUnitID' => $freeLearningUnitID);
                    $sql = 'SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
                } elseif ($highestAction == 'Browse Units_prerequisites') {
                    if ($schoolType == 'Physical') {
                        $data['freeLearningUnitID'] = $freeLearningUnitID;
                        $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                        $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                        $sql = "SELECT freeLearningUnit.*, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2 FROM freeLearningUnit LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID) WHERE active='Y' AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber) AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC";
                    } else {
                        $data['freeLearningUnitID'] = $freeLearningUnitID;
                        $sql = "SELECT freeLearningUnit.* FROM freeLearningUnit WHERE active='Y' AND freeLearningUnitID=:freeLearningUnitID ORDER BY name DESC";
                    }
                }
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
                    if ($schoolType == 'Online') {
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
                    } else {
                        //Work out if we student enrolment is allowed
                        $enrolment = false;
                        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                        if ($roleCategory != 'Student') {
                            //Fail 2
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        } else {
                            //Proceed!
                            //Validate Inputs
                            $gibbonCourseClassID = $_POST['gibbonCourseClassID'];
                            $grouping = $_POST['grouping'];
                            $collaborators = null;
                            if (isset($_POST['collaborators'])) {
                                $collaborators = $_POST['collaborators'];
                            }
                            if ($gibbonCourseClassID == '' or $grouping == '') {
                                //Fail 3
                                $URL .= '&return=error3';
                                header("Location: {$URL}");
                            } else {
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

                                //Check enrolment
                                try {
                                    $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                    $sql = 'SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID';
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
                                        $data = array('gibbonPersonIDStudent' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'grouping' => $grouping, 'collaborationKey' => $collaborationKey, 'freeLearningUnitID' => $freeLearningUnitID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                        $sql = "INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseClassID=:gibbonCourseClassID, grouping=:grouping, collaborationKey=:collaborationKey, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, status='Current', timestampJoined='".date('Y-m-d H:i:s')."'";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        //Fail 2
                                        $URL .= '&return=error2';
                                        header("Location: {$URL}");
                                        exit();
                                    }

                                    //DEAL WITH COLLABORATORS!
                                    $partialFail = false;
                                    if (is_array($collaborators)) {
                                        foreach ($collaborators as $collaborator) {
                                            //Check enrolment
                                            try {
                                                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $collaborator);
                                                $sql = 'SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID';
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $partialFail = true;
                                            }

                                            if ($result->rowCount() > 0) {
                                                $partialFail = true;
                                            } else {
                                                //Write to database
                                                try {
                                                    $data = array('gibbonPersonIDStudent' => $collaborator, 'gibbonCourseClassID' => $gibbonCourseClassID, 'grouping' => $grouping, 'collaborationKey' => $collaborationKey, 'freeLearningUnitID' => $freeLearningUnitID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                                    $sql = "INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseClassID=:gibbonCourseClassID, grouping=:grouping, collaborationKey=:collaborationKey, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, status='Current', timestampJoined='".date('Y-m-d H:i:s')."'";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $partialFail = true;
                                                }
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
}
