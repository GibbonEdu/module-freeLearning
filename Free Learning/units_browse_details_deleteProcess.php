<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Http\Url;
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$publicUnits = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'publicUnits');

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details_approval.php', $connection2);

$canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all';

$urlParams = [
    'freeLearningUnitStudentID' => $_POST['freeLearningUnitStudentID'] ?? '',
    'freeLearningUnitID'        => $_REQUEST['freeLearningUnitID'] ?? '',
    'showInactive'              => ($canManage and isset($_GET['showInactive'])) ? $_GET['showInactive'] : 'N',
    'gibbonDepartmentID'        => $_REQUEST['gibbonDepartmentID'] ?? '',
    'difficulty'                => $_GET['difficulty'] ?? '',
    'name'                      => $_GET['name'] ?? '',
    'view'                      => in_array($_GET['view'] ?? '', ['list', 'grid', 'map']) ? $_GET['view'] : 'list',
    'sidebar'                   => 'true',
    'gibbonPersonID'            => ($canManage and isset($_GET['gibbonPersonID'])) ? $_GET['gibbonPersonID'] : '',
    'tab'                       => "2",
];

$URL = Url::fromModuleRoute('Free Learning', 'units_browse_details_delete')->withQueryParams($urlParams);
$URLDelete = Url::fromModuleRoute('Free Learning', 'units_browse_details')->withQueryParams($urlParams);

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
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);

        if ($urlParams["freeLearningUnitID"] == '' or $urlParams["freeLearningUnitStudentID"] == '') {
            //Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('freeLearningUnitID' => $urlParams["freeLearningUnitID"], 'freeLearningUnitStudentID' => $urlParams["freeLearningUnitStudentID"]);
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
                //Check to see if we have access to manage all enrolments, or only those belonging to ourselves
                $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/enrolment_manage.php', 'Manage Enrolment_all');
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
                        $dataClasses = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $row['gibbonCourseClassID']);
                        $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND (role='Teacher' OR role='Assistant')";
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
                    //Write to database
                    try {
                        $data = array('freeLearningUnitStudentID' => $urlParams["freeLearningUnitStudentID"]);
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
