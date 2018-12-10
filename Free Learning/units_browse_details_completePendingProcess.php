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

use Gibbon\Contracts\Comms\Mailer;

include '../../gibbon.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

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


$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&sidebar=true&tab=1&view='.$view;

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
                            AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Evidence Not Yet Approved')";
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
                                $partialFail = false;

                                //Move attached image  file, if there is one
                                if (!empty($_FILES['file']['tmp_name'])) {
                                    $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                                    $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                                    // Upload the file, return the /uploads relative path
                                    $location = $fileUploader->uploadFromPost($file, $_SESSION[$guid]['username']);

                                    if (empty($location)) {
                                        $partialFail = true;
                                    }
                                }
                                else {
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
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&sidebar=true&tab=2";
                                while ($row = $result->fetch()) {
                                    setNotification($connection2, $guid, $row['gibbonPersonID'], $text, 'Free Learning', $actionLink);
                                }
                            }
                            else if ($enrolmentMethod == 'schoolMentor' && $gibbonPersonIDSchoolMentor != '') { //Attempt to notify school mentor
                                $text = sprintf(__($guid, 'A student has requested unit completion approval and feedback (%1$s).', 'Free Learning'), $name);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_mentor_approval.php&freeLearningUnitStudentID=".$freeLearningUnitStudentID."&confirmationKey=$confirmationKey";
                                setNotification($connection2, $guid, $gibbonPersonIDSchoolMentor, $text, 'Free Learning', $actionLink);
                            }
                            elseif ($enrolmentMethod == 'externalMentor' && $emailExternalMentor != '') { //Attempt to notify external mentors
                                $mailFile = '../../lib/PHPMailer/PHPMailerAutoload.php';
                                if (is_file($mailFile)) {
                                    include $mailFile;
                                }
                                
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

                                $mail = $container->get(Mailer::class);
                                $mail->IsSMTP();
                                $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);
                                $mail->AddReplyTo($student[1], $student[0]);
                                $mail->AddAddress($emailExternalMentor);
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
                            else {
                                $partialFail = true;
                            }

                            //Success 0
                            if ($partialFail == true) {
                                $URL .= '&return=warning1';
                                header("Location: {$URL}");
                            } else {
                                $URL .= "&return=success0";
                                header("Location: {$URL}");
                            }
                        }
                    }
                }
            }
        }
    }
}
