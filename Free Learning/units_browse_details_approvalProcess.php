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

use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&freeLearningUnitStudentID='.$_POST['freeLearningUnitStudentID'].'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&sidebar=true&tab=2&view='.$view;

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
                $sql = "SELECT * FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID AND (status='Complete - Pending' OR status='Complete - Approved' OR status='Evidence Not Yet Approved')";
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

                //Check to see if class is in one teacher teachers
                if ($row['enrolmentMethod'] == 'class') { //Is teacher of this class?
                    try {
                        $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $row['gibbonCourseClassID']);
                        $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher'";
                        $resultClasses = $connection2->prepare($sqlClasses);
                        $resultClasses->execute($dataClasses);
                    } catch (PDOException $e) {}
                    if ($resultClasses->rowCount() > 0) {
                        $proceed = true;
                    }
                }

                if ($proceed == false) {
                    //Fail 0
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                } else {
                    //Get Inputs
                    $status = $_POST['status'];

                    // Get the comment and strip the wrapping paragraph tags
                    $commentApproval = $_POST['commentApproval'];
                    $commentApproval = preg_replace('/^<p>|<\/p>$/i', '', $commentApproval);

                    $gibbonPersonIDStudent = $row['gibbonPersonIDStudent'];
                    $exemplarWork = $_POST['exemplarWork'];
                    $exemplarWorkLicense = '';
                    $exemplarWorkEmbed = '';
                    $attachment = '';
                    if ($exemplarWork == 'Y') {
                        $exemplarWorkLicense = $_POST['exemplarWorkLicense'];
                        $exemplarWorkEmbed = $_POST['exemplarWorkEmbed'];
                    }

                    //Validation
                    if ($commentApproval == '' or $exemplarWork == '') {
                        //Fail 3
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        $partialFail = false;

                        // Insert discussion records
                        $collaborativeAssessment = getSettingByScope($connection2, 'Free Learning', 'collaborativeAssessment');
                        $discussionGateway = $container->get(DiscussionGateway::class);
                        $unitStudentGateway = $container->get(UnitStudentGateway::class);
                            
                        $data = [
                            'foreignTable'   => 'freeLearningUnitStudent',
                            'foreignTableID' => $freeLearningUnitStudentID,
                            'gibbonModuleID' => getModuleIDFromName($connection2, 'Free Learning'), 
                            'gibbonPersonID' => $gibbonPersonID,
                            'comment'        => $commentApproval,
                            'type'           => $status,
                            'tag'            => $status == 'Complete - Approved' ? 'success' : 'warning',
                        ];

                        if ($collaborativeAssessment == 'Y' AND !empty($row['collaborationKey'])) {
                            $collaborators = $unitStudentGateway->selectBy(['collaborationKey' => $row['collaborationKey']])->fetchAll();
                            foreach ($collaborators as $collaborator) {
                                $data['foreignTableID'] = $collaborator['freeLearningUnitStudentID'];
                                $discussionGateway->insert($data);
                            }
                        } else {
                            $discussionGateway->insert($data);
                        }

                        if ($status == 'Complete - Approved') { //APPROVED!
                            //Move attached file, if there is one
                            if ($exemplarWork == 'Y') {
                                $attachment = $row['exemplarWorkThumb'];
                                $time = time();

                                //Move attached image  file, if there is one
                                if (!empty($_FILES['file']['tmp_name'])) {
                                    $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
                                    $fileUploader->getFileExtensions('Graphics/Design');

                                    $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                                    // Upload the file, return the /uploads relative path
                                    $attachment = $fileUploader->uploadFromPost($file, $name);

                                    if (empty($attachment)) {
                                        $partialFail = true;
                                    }
                                }
                            }

                            // Write to database
                            $unitStudentGateway = $container->get(UnitStudentGateway::class); 
                           
                            $data = [
                                'status' => $status,
                                'exemplarWork' => $exemplarWork,
                                'exemplarWorkThumb' => $attachment,
                                'exemplarWorkLicense' => $exemplarWorkLicense,
                                'exemplarWorkEmbed' => $exemplarWorkEmbed,
                                'commentApproval' => $commentApproval,
                                'gibbonPersonIDApproval' => $_SESSION[$guid]['gibbonPersonID'],
                                'timestampCompleteApproved' => date('Y-m-d H:i:s')
                            ];

                            if ($collaborativeAssessment == 'Y' AND !empty($row['collaborationKey'])) {
                                $updated = $unitStudentGateway->updateWhere(['collaborationKey' => $row['collaborationKey']], $data);
                            } else {
                                $updated = $unitStudentGateway->update($freeLearningUnitStudentID, $data);
                            }

                            //Attempt to assemble list of students for notification and badges
                            $gibbonPersonIDStudents[] = $gibbonPersonIDStudent;
                            if ($collaborativeAssessment == 'Y' AND  !empty($row['collaborationKey'])) {
                                try {
                                    $dataNotification = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'collaborationKey' => $row['collaborationKey']);
                                    $sqlNotification = "SELECT gibbonPersonIDStudent FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND NOT freeLearningUnitStudentID=:freeLearningUnitStudentID AND (status='Complete - Pending' OR status='Complete - Approved' OR status='Evidence Not Yet Approved') AND collaborationKey=:collaborationKey";
                                    $resultNotification = $connection2->prepare($sqlNotification);
                                    $resultNotification->execute($dataNotification);
                                } catch (PDOException $e) { echo $e->getMessage(); exit; }
                                while ($rowNotification = $resultNotification->fetch()) {
                                    $gibbonPersonIDStudents[] = $rowNotification['gibbonPersonIDStudent'];
                                }
                            }

                            //Attempt to notify the student and grant awards
                            if ($statusOriginal != $status or $commentApprovalOriginal != $commentApproval) { //Only if status or comment has changed.
                                $text = sprintf(__($guid, 'A teacher has approved your request for unit completion (%1$s).', 'Free Learning'), $name);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=&difficulty=&name=&showInactive=&sidebar=true&tab=1";
                                foreach ($gibbonPersonIDStudents AS $gibbonPersonIDStudent) {
                                    setNotification($connection2, $guid, $gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);
                                    if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_grant.php')) {
                                        grantBadges($connection2, $guid, $gibbonPersonIDStudent);
                                    }
                                }
                            }

                            if ($partialFail == true) {
                                $URL .= '&return=warning1';
                                header("Location: {$URL}");
                            } else {
                                $URL .= "&return=success0";
                                header("Location: {$URL}");
                            }
                        } elseif ($status == 'Evidence Not Yet Approved') { //NOT YET APPROVED
                            //Write to database
                            $collaborativeAssessment = getSettingByScope($connection2, 'Free Learning', 'collaborativeAssessment');
                            try {
                                $data = array('status' => $status, 'exemplarWork' => $exemplarWork, 'exemplarWorkThumb' => '', 'exemplarWorkLicense' => '', 'commentApproval' => $commentApproval, 'commentApproval' => $commentApproval, 'gibbonPersonIDApproval' => $_SESSION[$guid]['gibbonPersonID'], 'timestampCompleteApproved' => date('Y-m-d H:i:s'));
                                if ($collaborativeAssessment == 'Y' AND  !empty($row['collaborationKey'])) {
                                    $data['collaborationKey'] = $row['collaborationKey'];
                                    $sql = 'UPDATE freeLearningUnitStudent SET exemplarWork=:exemplarWork, exemplarWorkThumb=:exemplarWorkThumb, exemplarWorkLicense=:exemplarWorkLicense, status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE collaborationKey=:collaborationKey';
                                }
                                else {
                                    $data['freeLearningUnitStudentID'] = $freeLearningUnitStudentID;
                                    $sql = 'UPDATE freeLearningUnitStudent SET exemplarWork=:exemplarWork, exemplarWorkThumb=:exemplarWorkThumb, exemplarWorkLicense=:exemplarWorkLicense, status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';

                                }
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
                                $text = sprintf(__($guid, 'A teacher has responded to your request for unit completion, but your evidence has not been approved (%1$s).', 'Free Learning'), $name);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&sidebar=true&tab=1&view=$view";
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
