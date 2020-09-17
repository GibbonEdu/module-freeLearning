<?php

use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;
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

//Get parameters
$freeLearningUnitStudentID = null;
if (isset($_POST['freeLearningUnitStudentID'])) {
    $freeLearningUnitStudentID = $_POST['freeLearningUnitStudentID'];
}
$freeLearningUnitID = null;
if (isset($_POST['freeLearningUnitID'])) {
    $freeLearningUnitID = $_POST['freeLearningUnitID'];
}
$confirmationKey = null;
if (isset($_POST['confirmationKey'])) {
    $confirmationKey = $_POST['confirmationKey'];
}

//Set return URL
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/units_mentor_approval.php&sidebar=true&freeLearningUnitStudentID=$freeLearningUnitStudentID&confirmationKey=$confirmationKey';

if ($freeLearningUnitStudentID == '' or $freeLearningUnitID == '' or $confirmationKey == '') {
    $URL .= '&return=error3';
    header("Location: {$URL}");
} else {
    //Check student & confirmation key
    try {
        $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'freeLearningUnitID' => $freeLearningUnitID, 'confirmationKey' => $confirmationKey) ;
        $sql = 'SELECT freeLearningUnitStudent.*, freeLearningUnit.name AS unit FROM freeLearningUnitStudent JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID AND freeLearningUnit.freeLearningUnitID=:freeLearningUnitID AND confirmationKey=:confirmationKey AND status=\'Complete - Pending\'';
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
        $name = $row['unit'];

        //Get Inputs
        $status = $_POST['status'] ?? '';
        $gibbonPersonIDStudent = $row['gibbonPersonIDStudent'] ?? '';
        $commentApproval = $_POST['commentApproval'] ?? '';
        $commentApproval = trim(preg_replace('/^<p>|<\/p>$/i', '', $commentApproval));

        //Validation
        if ($commentApproval == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } elseif ($status != 'Complete - Approved' && $status != 'Evidence Not Yet Approved') {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            // Post Discussion
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

            // Write to database
            $unitStudentGateway = $container->get(UnitStudentGateway::class); 
                        
            $data = [
                'status' => $status,
                'commentApproval' => $commentApproval,
                'gibbonPersonIDApproval' => null,
                'timestampCompleteApproved' => date('Y-m-d H:i:s')
            ];

            if ($collaborativeAssessment == 'Y' AND !empty($row['collaborationKey'])) {
                $updated = $unitStudentGateway->updateWhere(['collaborationKey' => $row['collaborationKey']], $data);
            } else {
                $updated = $unitStudentGateway->update($freeLearningUnitStudentID, $data);
            }

            if ($status == 'Complete - Approved') { //APPROVED!
                //Attempt to notify the student and grant awards
                $text = sprintf(__($guid, 'Your mentor has approved your request for unit completion (%1$s).', 'Free Learning'), $name);
                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=&difficulty=&name=&showInactive=&sidebar=true&tab=1";
                setNotification($connection2, $guid, $gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);
                grantBadges($connection2, $guid, $gibbonPersonIDStudent);

                $URL .= '&return=success0';
                header("Location: {$URL}");
            } elseif ($status == 'Evidence Not Yet Approved') { //NOT YET APPROVED
                //Attempt to notify the student
                $text = sprintf(__($guid, 'Your mentor has responded to your request for unit completion, but your evidence has not been approved (%1$s).', 'Free Learning'), $name);
                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&sidebar=true&tab=1&view=$view";
                setNotification($connection2, $guid, $gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);

                $URL .= '&return=success1';
                header("Location: {$URL}");
            }
        }
    }
}
