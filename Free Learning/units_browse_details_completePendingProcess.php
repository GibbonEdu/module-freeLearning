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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&applyAccessControls='.$applyAccessControls.'&sidebar=true&tab=1';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false) {
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
        if (empty($_POST)) {
            //Fail 6
            $URL .= '&return=error6';
            header("Location: {$URL}");
        } else {
            $schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

            $freeLearningUnitID = $_POST['freeLearningUnitID'];
            $freeLearningUnitStudentID = $_POST['freeLearningUnitStudentID'];

            if ($freeLearningUnitID == '' or $freeLearningUnitStudentID == '') {
                //Fail 3
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                try {
                    $data = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                    $sql = "SELECT freeLearningUnit.*, freeLearningUnitStudent.*, surname, preferredName, email
                        FROM freeLearningUnit
                            JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                            JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                        WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID
                            AND freeLearningUnitStudentID=:freeLearningUnitStudentID
                            AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Evidence Not Approved')";
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
                    $confirmationKey = $row['confirmationKey'];
                    $student[0] = formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                    $student[1] = $row['email'];
                    $enrolmentMethod = $row['enrolmentMethod'];
                    $gibbonPersonIDSchoolMentor = $row['gibbonPersonIDSchoolMentor'];
                    $emailExternalMentor = $row['emailExternalMentor'];
                    $nameExternalMentor = $row['nameExternalMentor'];

                    //Get Inputs
                    $status = 'Complete - Pending';
                    $commentStudent = $_POST['commentStudent'];
                    $type = $_POST['type'];
                    $link = trim($_POST['link']);
                    $gibbonCourseClassID = $row['gibbonCourseClassID'];

                    //Validation
                    if ($commentStudent == '' or $type == '' or ($_FILES['file']['name'] == '' and $link == '')) {
                        //Fail 3
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        $partialFail = false;
                        if ($type == 'Link') {
                            if (substr($link, 0, 7) != 'http://' and substr($link, 0, 8) != 'https://') {
                                $partialFail = true;
                            } else {
                                $location = $link;
                            }
                        }
                        if ($type == 'File') {
                            //Check extension to see if allow
                            try {
                                @$extension = end(explode('.', $_FILES['file']['name']));
                                $dataExt = array('extension' => $extension);
                                $sqlExt = 'SELECT * FROM gibbonFileExtension WHERE extension=:extension';
                                $resultExt = $connection2->prepare($sqlExt);
                                $resultExt->execute($dataExt);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }

                            if ($resultExt->rowCount() != 1) {
                                $partialFail = true;
                            } else {
                                //Attempt file upload
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
                                        $location = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.$_SESSION[$guid]['username'].'_'.preg_replace('/[^a-zA-Z0-9]/', '', $row['name'])."_$suffix".strrchr($_FILES['file']['name'], '.');
                                        if (!(file_exists($path.'/'.$location))) {
                                            $unique = true;
                                        }
                                        ++$count;
                                    }

                                    if (!(move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$location))) {
                                        //Fail 5
                                        $URL .= '&addReturn=error3';
                                        header("Location: {$URL}");
                                    }
                                } else {
                                    $partialFail = true;
                                }
                            }
                        }

                        //Deal with partial fail
                        if ($partialFail == true) {
                            //Fail 6
                            $URL .= '&return=error6';
                            header("Location: {$URL}");
                        } else {
                            if ($schoolType == 'Online') {
                                //Write to database
                                try {
                                    $data = array('commentStudent' => $commentStudent, 'evidenceType' => $type, 'evidenceLocation' => $location, 'timestampCompletePending' => date('Y-m-d H:i:s'), 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                                    $sql = "UPDATE freeLearningUnitStudent SET status='Complete - Approved', commentStudent=:commentStudent, evidenceType=:evidenceType, evidenceLocation=:evidenceLocation, timestampCompletePending=:timestampCompletePending WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    //Fail 2
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit;
                                }
                            } else {
                                //Write to database
                                try {
                                    $data = array('status' => $status, 'commentStudent' => $commentStudent, 'evidenceType' => $type, 'evidenceLocation' => $location, 'timestampCompletePending' => date('Y-m-d H:i:s'), 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                                    $sql = 'UPDATE freeLearningUnitStudent SET status=:status, commentStudent=:commentStudent, evidenceType=:evidenceType, evidenceLocation=:evidenceLocation, timestampCompletePending=:timestampCompletePending WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    //Fail 2
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit;
                                }


                                if ($enrolmentMethod == 'class') { //Attempt to notify teacher(s) of class
                                    try {
                                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                        $sql = "SELECT gibbonPersonID FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) { }

                                    $text = sprintf(__($guid, 'A student has requested unit completion approval and feedback (%1$s).', 'Free Learning'), $name);
                                    $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&sidebar=true&tab=2&applyAccessControls=N";
                                    while ($row = $result->fetch()) {
                                        setNotification($connection2, $guid, $row['gibbonPersonID'], $text, 'Free Learning', $actionLink);
                                    }
                                } elseif ($enrolmentMethod == 'schoolMentor' or $enrolmentMethod == 'externalMentor') { //Attempt to notify mentors
                                    $emailMentor = '';
                                    if ($enrolmentMethod == 'schoolMentor') {
                                        try {
                                            $dataInternal = array('gibbonPersonID' => $gibbonPersonIDSchoolMentor);
                                            $sqlInternal = 'SELECT email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                                            $resultInternal = $connection2->prepare($sqlInternal);
                                            $resultInternal->execute($dataInternal);
                                        } catch (PDOException $e) { }
                                        if ($resultInternal->rowCount() == 1) {
                                            $rowInternal = $resultInternal->fetch() ;
                                            $emailMentor = $rowInternal['email'] ;
                                        }
                                    } elseif ($enrolmentMethod == 'externalMentor') {
                                        $emailMentor = $emailExternalMentor ;
                                    }

                                    if ($emailMentor != '') {
                                        //Include mailer
                                        require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/PHPMailerAutoload.php';

                                        //Attempt email send
                                        $subject = sprintf(__($guid, 'Request For Mentor Feedback via %1$s at %2$s', 'Free Learning'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                                        $body = __($guid, 'To whom it may concern,', 'Free Learning').'<br/><br/>';
                                        if ($roleCategory == 'Staff') {
                                            $roleCategoryFull = 'member of staff';
                                        }
                                        else {
                                            $roleCategoryFull = strtolower($roleCategory);
                                        }
                                        $roleCategoryFull = __($guid, $roleCategoryFull) ;

                                        $body .= sprintf(__($guid, 'The following %1$s at %2$s has requested your feedback on their %3$sFree Learning%4$s work (%5$s), which they have just submitted, and on which you previously agreed to mentor them.', 'Free Learning'), $roleCategoryFull, $_SESSION[$guid]['systemName'], "<a target='_blank' href='http://rossparker.org'>", '</a>', '<b>'.$name.'</b>');
                                        $body .= '<br/>';
                                        $body .= '<ul>';
                                        $body .= '<li>'.$student[0].'</li>';
                                        $body .= '</ul>';
                                        $body .= sprintf(__($guid, 'Please %1$sclick here%2$s to view and give feedback on the submitted work.', 'Free Learning'), "<a style='font-weight: bold; text-decoration: underline; color: #390' target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_mentor_approval.php&freeLearningUnitStudentID=".$freeLearningUnitStudentID."&confirmationKey=$confirmationKey'>", '</a>');
                                        $body .= '<br/><br/>';
                                        $body .= sprintf(__($guid, 'Thank you very much for your time. Should you have any questions about this matter, please reply to this email, or contact %1$s on %2$s.', 'Free Learning'), $_SESSION[$guid]['organisationAdministratorName'], $_SESSION[$guid]['organisationAdministratorEmail']);
                                        $body .= '<br/><br/>';
                                        $body .= sprintf(__($guid, 'Email sent via %1$s at %2$s.', 'Free Learning'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']);
                                        $body .= '</p>';
                                        $bodyPlain = emailBodyConvert($body);

                                        $mail = new PHPMailer();
                                        $mail->IsSMTP();
                                        $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);
                                        $mail->AddReplyTo($student[1], $student[0]);
                                        $mail->AddAddress($emailMentor);
                                        $mail->CharSet = 'UTF-8';
                                        $mail->Encoding = 'base64';
                                        $mail->IsHTML(true);
                                        $mail->Subject = $subject;
                                        $mail->Body = $body;
                                        $mail->AltBody = $bodyPlain;

                                        try {
                                            $mail->Send();
                                        } catch (phpmailerException $e) {
                                            print "there"; exit();
                                        }
                                    }
                                }
                            }

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
