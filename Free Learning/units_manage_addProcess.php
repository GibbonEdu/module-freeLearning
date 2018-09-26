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

include '../../gibbon.php';


$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/units_manage_add.php&gibbonDepartmentID='.$_GET['gibbonDepartmentID'].'&difficulty='.$_GET['difficulty'].'&name='.$_GET['name'];

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_add.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
        //Fail 0
        $URL .= "&updateReturn=error0$params";
        header("Location: {$URL}");
    } else {
        if (!(isset($_POST))) {
            //Fail 5
            $URL .= '&return=error5';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Validate Inputs
            $name = $_POST['name'];
            $difficulty = $_POST['difficulty'];
            $blurb = $_POST['blurb'];
            $count = $_POST['count'];
            $gibbonDepartmentIDList = null;
            for ($i = 0; $i < $count; ++$i) {
                if (isset($_POST["gibbonDepartmentIDCheck$i"])) {
                    if ($_POST["gibbonDepartmentIDCheck$i"] == 'on') {
                        $gibbonDepartmentIDList = $gibbonDepartmentIDList.$_POST["gibbonDepartmentID$i"].',';
                    }
                }
            }
            $gibbonDepartmentIDList = substr($gibbonDepartmentIDList, 0, (strlen($gibbonDepartmentIDList) - 1));
            if ($gibbonDepartmentIDList == '') {
                $gibbonDepartmentIDList = null;
            }
            $license = $_POST['license'];
            $availableStudents = $_POST['availableStudents'];
            $availableStaff = $_POST['availableStaff'];
            $availableParents = $_POST['availableParents'];
            $availableOther = $_POST['availableOther'];
            $sharedPublic = null;
            if (isset($_POST['sharedPublic'])) {
                $sharedPublic = $_POST['sharedPublic'];
            }
            $active = $_POST['active'];
            $gibbonYearGroupIDMinimum = null;
            if ($_POST['gibbonYearGroupIDMinimum'] != '') {
                $gibbonYearGroupIDMinimum = $_POST['gibbonYearGroupIDMinimum'];
            }
            $grouping = '';
            if (isset($_POST['Individual'])) {
                if ($_POST['Individual'] == 'on') {
                    $grouping .= 'Individual,';
                }
            }
            if (isset($_POST['Pairs'])) {
                if ($_POST['Pairs'] == 'on') {
                    $grouping .= 'Pairs,';
                }
            }
            if (isset($_POST['Threes'])) {
                if ($_POST['Threes'] == 'on') {
                    $grouping .= 'Threes,';
                }
            }
            if (isset($_POST['Fours'])) {
                if ($_POST['Fours'] == 'on') {
                    $grouping .= 'Fours,';
                }
            }
            if (isset($_POST['Fives'])) {
                if ($_POST['Fives'] == 'on') {
                    $grouping .= 'Fives,';
                }
            }
            if (substr($grouping, -1) == ',') {
                $grouping = substr($grouping, 0, -1);
            }
            $freeLearningUnitIDPrerequisiteList = null;
            if (isset($_POST['prerequisites'])) {
                $prerequisites = $_POST['prerequisites'];
                foreach ($prerequisites as $prerequisite) {
                    $freeLearningUnitIDPrerequisiteList .= $prerequisite.',';
                }
                $freeLearningUnitIDPrerequisiteList = substr($freeLearningUnitIDPrerequisiteList, 0, -1);
            }
            $outline = $_POST['outline'];
            $schoolMentorCompletors = null ;
            if (isset($_POST['schoolMentorCompletors'])) {
                $schoolMentorCompletors = $_POST['schoolMentorCompletors'];
            }
            $schoolMentorCustom = null ;
            if (isset($_POST['schoolMentorCustom']) && is_array($_POST['schoolMentorCustom'])) {
                $schoolMentorCustom = implode(",", $_POST['schoolMentorCustom']);
            }

            if ($name == '' or $difficulty == '' or $active == '' or $availableStudents == '' or $availableStaff == '' or $availableParents == '' or $availableOther == '') {
                //Fail 3
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                $partialFail = false;

                //Lock tables
                try {
                    $sql = 'LOCK TABLES freeLearningUnit WRITE, freeLearningUnitAuthor WRITE, freeLearningUnitBlock WRITE';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Get next autoincrement
                try {
                    $sqlAI = "SHOW TABLE STATUS LIKE 'freeLearningUnit'";
                    $resultAI = $connection2->query($sqlAI);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $rowAI = $resultAI->fetch();
                $AI = str_pad($rowAI['Auto_increment'], 10, '0', STR_PAD_LEFT);

                //Move attached file, if there is one
                $partialFail = false;
                $attachment = null;
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
                if ($attachment != null) {
                    $attachment = $_SESSION[$guid]['absoluteURL'].'/'.$attachment;
                }

                //Write to database
                try {
                    $data = array('name' => $name, 'logo' => $attachment, 'difficulty' => $difficulty, 'blurb' => $blurb, 'license' => $license, 'availableStudents'=>$availableStudents, 'availableStaff'=>$availableStaff, 'availableParents'=>$availableParents, 'availableOther' => $availableOther, 'sharedPublic' => $sharedPublic, 'active' => $active, 'gibbonYearGroupIDMinimum' => $gibbonYearGroupIDMinimum, 'grouping' => $grouping, 'gibbonDepartmentIDList' => $gibbonDepartmentIDList, 'freeLearningUnitIDPrerequisiteList' => $freeLearningUnitIDPrerequisiteList, 'schoolMentorCompletors' => $schoolMentorCompletors, 'schoolMentorCustom' => $schoolMentorCustom, 'outline' => $outline, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'timestamp' => date('Y-m-d H:i:s'));
                    $sql = 'INSERT INTO freeLearningUnit SET name=:name, logo=:logo, difficulty=:difficulty, blurb=:blurb, license=:license, availableStudents=:availableStudents, availableStaff=:availableStaff, availableParents=:availableParents, availableOther=:availableOther, sharedPublic=:sharedPublic, active=:active, gibbonYearGroupIDMinimum=:gibbonYearGroupIDMinimum, grouping=:grouping, gibbonDepartmentIDList=:gibbonDepartmentIDList, freeLearningUnitIDPrerequisiteList=:freeLearningUnitIDPrerequisiteList, schoolMentorCompletors=:schoolMentorCompletors, schoolMentorCustom=:schoolMentorCustom, outline=:outline, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Write author to database
                try {
                    $data = array('freeLearningUnitID' => $AI, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'surname' => $_SESSION[$guid]['surname'], 'preferredName' => $_SESSION[$guid]['preferredName'], 'website' => $_SESSION[$guid]['website']);
                    $sql = 'INSERT INTO freeLearningUnitAuthor SET freeLearningUnitID=:freeLearningUnitID, gibbonPersonID=:gibbonPersonID, surname=:surname, preferredName=:preferredName, website=:website';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                //ADD BLOCKS
                $blockCount = ($_POST['blockCount'] - 1);
                $sequenceNumber = 0;
                if ($blockCount > 0) {
                    $order = array();
                    if (isset($_POST['order'])) {
                        $order = $_POST['order'];
                    }
                    foreach ($order as $i) {
                        $title = '';
                        if ($_POST["title$i"] != "Block $i") {
                            $title = $_POST["title$i"];
                        }
                        $type2 = '';
                        if ($_POST["type$i"] != 'type (e.g. discussion, outcome)') {
                            $type2 = $_POST["type$i"];
                        }
                        $length = '';
                        if ($_POST["length$i"] != 'length (min)') {
                            $length = $_POST["length$i"];
                        }
                        $contents = $_POST["contents$i"];
                        $teachersNotes = $_POST["teachersNotes$i"];

                        if ($title != '' or $contents != '') {
                            try {
                                $dataBlock = array('freeLearningUnitID' => $AI, 'title' => $title, 'type' => $type2, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                                $sqlBlock = 'INSERT INTO freeLearningUnitBlock SET freeLearningUnitID=:freeLearningUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber';
                                $resultBlock = $connection2->prepare($sqlBlock);
                                $resultBlock->execute($dataBlock);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            ++$sequenceNumber;
                        }
                    }
                }

                //Unlock module table
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                }

                if ($partialFail == true) {
                    //Fail 6
                    $URL .= '&return=error6';
                    header("Location: {$URL}");
                } else {
                    //Success 0
                    $URL = $URL.'&return=success0&editID='.$AI;
                    header("Location: {$URL}");
                }
            }
        }
    }
}
