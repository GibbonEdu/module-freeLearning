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

use Gibbon\View\View;
use Gibbon\Contracts\Comms\Mailer;

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

$highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);

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


$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&sidebar=true&tab=1&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&view='.$view;

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
                exit();
            }

            if ($result->rowCount() != 1) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            } else {
                $row = $result->fetch();
                $unit = $row['name'];
                $blurb = $row['blurb'];

                $proceed = false;
                if ($highestAction == 'Browse Units_all') {
                    $proceed = true;
                } elseif ($highestAction == 'Browse Units_prerequisites') {
                    if ($row['freeLearningUnitIDPrerequisiteList'] == null or $row['freeLearningUnitIDPrerequisiteList'] == '') {
                        $proceed = true;
                    } else {
                        $prerequisitesActive = prerequisitesRemoveInactive($connection2, $row['freeLearningUnitIDPrerequisiteList']);
                        $prerequisitesMet = prerequisitesMet($connection2, $_SESSION[$guid]['gibbonPersonID'], $prerequisitesActive, true);
                        if ($prerequisitesMet) {
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
                    //Proceed!
                    //Validate Inputs
                    $checkFail = false;
                    $enrolmentMethod = $_POST['enrolmentMethod'];
                    $status = 'Current - Pending';
                    if ($enrolmentMethod == 'class')
                        $status = 'Current';
                    $gibbonCourseClassID = null;
                    $gibbonPersonIDSchoolMentor = null;
                    $emailExternalMentor = null;
                    $nameExternalMentor = null;
                    if ($enrolmentMethod == 'class') {
                        $gibbonCourseClassID = $_POST['gibbonCourseClassID'];
                    } elseif ($enrolmentMethod == 'schoolMentor') {
                        $gibbonPersonIDSchoolMentor = $_POST['gibbonPersonIDSchoolMentor'];
                        try {
                            $dataInternal = array('freeLearningUnitID3' => $freeLearningUnitID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDSchoolMentor1' => $gibbonPersonIDSchoolMentor);
                            $sqlInternal = "(SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname
                                FROM gibbonPerson
                                    JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                    JOIN freeLearningUnit ON (freeLearningUnit.gibbonDepartmentIDList LIKE concat('%',gibbonDepartmentStaff.gibbonDepartmentID,'%'))
                                WHERE gibbonPerson.status='Full'
                                    AND freeLearningUnitID=:freeLearningUnitID3
                                    AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID2
                                    AND gibbonPerson.gibbonPersonID=:gibbonPersonIDSchoolMentor1
                                )";
                            if ($row['schoolMentorCompletors'] == 'Y') {
                                $dataInternal['gibbonPersonID1'] = $_SESSION[$guid]['gibbonPersonID'];
                                $dataInternal['freeLearningUnitID1'] = $freeLearningUnitID;
                                $dataInternal['freeLearningUnitID2'] = $freeLearningUnitID;
                                $dataInternal['gibbonPersonIDSchoolMentor2'] = $gibbonPersonIDSchoolMentor;
                                $sqlInternal .= " UNION DISTINCT
                                    (SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname
                                        FROM gibbonPerson
                                        LEFT JOIN freeLearningUnitAuthor ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID AND freeLearningUnitAuthor.freeLearningUnitID=:freeLearningUnitID1)
                                        LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID2)
                                        WHERE gibbonPerson.status='Full'
                                            AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID1
                                            AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitAuthor.freeLearningUnitAuthorID IS NOT NULL)
                                            AND gibbonPerson.gibbonPersonID=:gibbonPersonIDSchoolMentor2
                                        GROUP BY gibbonPersonID)";
                            }
                            if ($row['schoolMentorCustom'] != '') {
                                $staffs = explode(",", $row['schoolMentorCustom']);
                                $staffCount = 0 ;
                                foreach ($staffs AS $staff) {
                                    $dataInternal["staff$staffCount"] = $staff;
                                    $dataInternal["mentor$staffCount"] = $gibbonPersonIDSchoolMentor;
                                    $sqlInternal .= " UNION DISTINCT
                                    (SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname
                                        FROM gibbonPerson
                                        WHERE gibbonPersonID=:staff$staffCount
                                            AND gibbonPersonID=:mentor$staffCount
                                            AND status='Full')";
                                    $staffCount ++;
                                }
                            }
                            if ($row['schoolMentorCustomRole'] != '') {
                                $dataInternal["gibbonRoleID"] = $row['schoolMentorCustomRole'];
                                $dataInternal["gibbonPersonIDSchoolMentor"] = $gibbonPersonIDSchoolMentor;
                                $sqlInternal .= " UNION DISTINCT
                                (SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname
                                    FROM gibbonPerson
                                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                                    WHERE gibbonRoleID=:gibbonRoleID
                                        AND gibbonPersonID=:gibbonPersonIDSchoolMentor
                                        AND status='Full')";
                            }
                            $sqlInternal .= " ORDER BY surname, preferredName";
                            $resultInternal = $connection2->prepare($sqlInternal);
                            $resultInternal->execute($dataInternal);
                        } catch (PDOException $e) { echo $e->getMessage();}
                        if ($resultInternal->rowCount() == 1) {
                            $rowInternal = $resultInternal->fetch() ;
                        }
                        else {
                            $checkFail = true;
                        }
                    } elseif ($enrolmentMethod == 'externalMentor') {
                        $emailExternalMentor = $_POST['emailExternalMentor'];
                        $nameExternalMentor = $_POST['nameExternalMentor'];
                    }
                    $grouping = $_POST['grouping'];
                    $collaborators = $_POST['collaborators'] ?? [];
                    

                    $enableClassEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableClassEnrolment');
                    if ($roleCategory != 'Student') {
                        $enableClassEnrolment = 'N';
                    }
                    $enableSchoolMentorEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableSchoolMentorEnrolment');
                    $enableExternalMentorEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableExternalMentorEnrolment');

                    if ($checkFail or $grouping == '' or ($enrolmentMethod == 'class' and $gibbonCourseClassID == '' and $enableClassEnrolment == 'N') or ($enrolmentMethod == 'schoolMentor' and $gibbonPersonIDSchoolMentor == '' and $enableSchoolMentorEnrolment == 'N') or ($enrolmentMethod == 'externalMentor' and $enableExternalMentorEnrolment == 'N' and ($emailExternalMentor == '' or $nameExternalMentor == ''))) {
                        //Fail 3
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        //If there are mentors, generate a unique confirmation key
                        $confirmationKey = null;
                        $unique = false;
                        if ($enrolmentMethod == 'schoolMentor' or $enrolmentMethod == 'externalMentor') {
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
                        if (is_array($collaborators) && !empty($collaborators)) {
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
                            $whereExtra = '' ;
                            if (count($collaborators) > 0) {
                                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
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
                            //Create an array of student data, recyclying data query from above
                            $students = array();
                            try {
                                unset($data['freeLearningUnitID']);
                                $whereExtra = str_replace ('gibbonPersonIDStudent', 'gibbonPersonID', $whereExtra);
                                $sql = 'SELECT email, surname, preferredName FROM gibbonPerson WHERE (gibbonPersonID=:gibbonPersonID'.$whereExtra.') ORDER BY (gibbonPerson.gibbonPersonID=\''.$_SESSION[$guid]['gibbonPersonID'].'\') DESC, surname, preferredName';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }
                            if ($result->rowCount() < count($collaborators)) {
                                //Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            } else {
                                $studentCount = 0;
                                while ($row = $result->fetch()) {
                                    $students[$studentCount][0] = formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                                    $students[$studentCount][1] = $row['email'];
                                    $studentCount ++;
                                }
                                //Write to database
                                try {
                                    $data = array('gibbonPersonIDStudent' => $_SESSION[$guid]['gibbonPersonID'], 'enrolmentMethod' => $enrolmentMethod, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDSchoolMentor' => $gibbonPersonIDSchoolMentor, 'emailExternalMentor' => $emailExternalMentor, 'nameExternalMentor' => $nameExternalMentor, 'grouping' => $grouping, 'confirmationKey' => $confirmationKey, 'collaborationKey' => $collaborationKey, 'freeLearningUnitID' => $freeLearningUnitID, 'status' => $status, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sql = "INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonIDStudent, enrolmentMethod=:enrolmentMethod, gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonIDSchoolMentor=:gibbonPersonIDSchoolMentor, emailExternalMentor=:emailExternalMentor, nameExternalMentor=:nameExternalMentor, `grouping`=:grouping, confirmationKey=:confirmationKey, collaborationKey=:collaborationKey, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, status=:status, timestampJoined='".date('Y-m-d H:i:s')."'";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    //Fail 2
                                    print $sql."<br/>";
                                    echo $e->getMessage();exit;
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }

                                //Last insert ID
                                $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

                                //DEAL WITH COLLABORATORS (availability checked above)!
                                $partialFail = false;
                                if (is_array($collaborators)) {
                                    foreach ($collaborators as $collaborator) {
                                        //Write to database
                                        try {
                                            $data = array('gibbonPersonIDStudent' => $collaborator, 'enrolmentMethod' => $enrolmentMethod, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDSchoolMentor' => $gibbonPersonIDSchoolMentor, 'emailExternalMentor' => $emailExternalMentor, 'nameExternalMentor' => $nameExternalMentor, 'grouping' => $grouping, 'confirmationKey' => $confirmationKey, 'collaborationKey' => $collaborationKey, 'freeLearningUnitID' => $freeLearningUnitID, 'status' => $status, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                            $sql = "INSERT INTO freeLearningUnitStudent SET gibbonPersonIDStudent=:gibbonPersonIDStudent, enrolmentMethod=:enrolmentMethod, gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonIDSchoolMentor=:gibbonPersonIDSchoolMentor, emailExternalMentor=:emailExternalMentor, nameExternalMentor=:nameExternalMentor, `grouping`=:grouping, confirmationKey=:confirmationKey, collaborationKey=:collaborationKey, freeLearningUnitID=:freeLearningUnitID, gibbonSchoolYearID=:gibbonSchoolYearID, status=:status, timestampJoined='".date('Y-m-d H:i:s')."'";
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $partialFail = true;
                                        }
                                    }
                                }

                                //Notify internal mentors by gibbon
                                if ($enrolmentMethod == 'schoolMentor') {
                                    $notificationText = sprintf(__($guid, 'A learner (or group of learners) has requested that you mentor them for the Free Learning unit %1$s.', 'Free Learning'), $unit);
                                    $actionLink = "/index.php?q=/modules/Free Learning/units_mentor.php&mode=internal&freeLearningUnitID=$freeLearningUnitID&freeLearningUnitStudentID=".$AI."&confirmationKey=$confirmationKey";
                                    setNotification($connection2, $guid, $gibbonPersonIDSchoolMentor, $notificationText, 'Free Learning', $actionLink);
                                }

                                // Notify external mentors by email
                                if (($enrolmentMethod == 'externalMentor' and $_POST['emailExternalMentor'] != '')) {
                                    
                                    $subject = sprintf(__m('Request For Mentorship via %1$s at %2$s'), $gibbon->session->get('systemName'), $gibbon->session->get('organisationNameShort'));
                                    $buttonURL = "/modules/Free Learning/units_mentorProcess.php?freeLearningUnitStudentID=$AI&confirmationKey=$confirmationKey";
                                    
                                    $body = $container->get(View::class)->fetchFromTemplate('mentorRequest.twig.html', [
                                        'roleCategoryFull' => $roleCategory == 'Staff' ? __m('member of staff') : __(strtolower($roleCategory)),
                                        'unitName' => $unit,
                                        'unitBlurb' => $blurb,
                                        'students' => $students,
                                        'organisationNameShort' => $gibbon->session->get('organisationNameShort'),
                                        'organisationAdministratorName' => $gibbon->session->get('organisationAdministratorName'),
                                        'organisationAdministratorEmail' => $gibbon->session->get('organisationAdministratorEmail'),
                                    ]);

                                    // Attempt email send
                                    $mail = $container->get(Mailer::class);
                                    $mail->AddReplyTo($students[0][1], $students[0][0]);
                                    $mail->AddAddress($emailExternalMentor, $nameExternalMentor);
                                    $mail->setDefaultSender($subject);
                                    $mail->renderBody('mail/message.twig.html', [
                                        'title'  => __m('Request For Mentorship'),
                                        'body'   => $body,
                                        'button' => [
                                            'url'  => $buttonURL.'&response=Y',
                                            'text' => __('Accept'),
                                        ],
                                        'button2' => [
                                            'url'  => $buttonURL.'&response=N',
                                            'text' => __('Decline'),
                                        ],
                                    ]);

                                    $sent = $mail->Send();
                                    $partialFail &= !$sent;
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
