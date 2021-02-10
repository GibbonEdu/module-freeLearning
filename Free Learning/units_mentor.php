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

use Gibbon\Forms\Form;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

$highestAction = false;
$canManage = false;
$gibbonPersonID ='';
if ($gibbon->session->exists('gibbonPersonID')) {
    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse.php', $connection2);
    $gibbonPersonID = $gibbon->session->get('gibbonPersonID');
    $canManage = false;
    if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
        $canManage = true;
    }
    if ($canManage and isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }
}

//Get params
$freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';
$showInactive = ($canManage and isset($_GET['showInactive'])) ? $_GET['showInactive'] : 'N';
$gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$name = $_GET['name'] ?? '';
$view = $_GET['view'] ?? '';
if ($view != 'grid' and $view != 'map') {
    $view = 'list';
}
$mode = (isset($_GET['mode']) && $_GET['mode'] == 'internal') ? $_GET['mode'] : 'external';
$confirmationKey = $_GET['confirmationKey'] ?? null;

$urlParams = compact('view', 'name', 'difficulty', 'gibbonDepartmentID', 'showInactive', 'freeLearningUnitID');
$page->breadcrumbs
    ->add(__m('Browse Units'), 'units_browse.php', $urlParams);

if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, array('success0' => __m('Your request was completed successfully. Thank you for your time.'), 'success1' => __m('Your request was completed successfully. Thank you for your time. The learners you are helping will be in touch in due course: in the meanwhile, no further action is required on your part.')));
}

if ($freeLearningUnitID != '' && $gibbon->session->exists('gibbonPersonID')) {
    //Check unit
    try {
        $data = array('freeLearningUnitID' => $freeLearningUnitID) ;
        $sql = 'SELECT freeLearningUnit.* FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() != 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        $row = $result->fetch();

        $urlParams["sidebar"] = "true";
        $page->breadcrumbs->add(__m('Unit Details'), 'units_browse_details.php', $urlParams)
            ->add(__m('Approval'));

        //Show choice for school mentor
        if ($mode == "internal" && $confirmationKey != '') {
            echo '<p>';
            echo sprintf(__m('The following users at %1$s have requested your input into their %2$sFree Learning%3$s work, with the hope that you will be able to act as a "critical buddy" or mentor, offering feedback on their progress.'), $gibbon->session->get('systemName'), "<a target='_blank' href='http://rossparker.org'>", '</a>');
            echo '<br/>';
            echo '</p>';

            $freeLearningUnitStudentID = null;

            try {
                $dataConfCheck = array('confirmationKey' => $confirmationKey) ;
                $sqlConfCheck = 'SELECT freeLearningUnitStudentID, preferredName, surname
                    FROM freeLearningUnitStudent
                    JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                    WHERE confirmationKey=:confirmationKey
                    ORDER BY freeLearningUnitStudentID';
                $resultConfCheck = $connection2->prepare($sqlConfCheck);
                $resultConfCheck->execute($dataConfCheck);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultConfCheck->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('An error occurred.');
                echo '</div>';
            }
            else {
                echo '<ul>';
                while ($rowConfCheck = $resultConfCheck->fetch()) {
                    $freeLearningUnitStudentID = (is_null($freeLearningUnitStudentID) ? $rowConfCheck['freeLearningUnitStudentID'] : $freeLearningUnitStudentID);
                    echo '<li>'.formatName('', $rowConfCheck['preferredName'], $rowConfCheck['surname'], 'Student', true).'</li>';
                }
                echo '</ul>';
                echo '<p style=\'margin-top: 20px\'>';
                echo sprintf(__m('The unit you are being asked to advise on is called %1$s and is described as follows:'), '<b>'.$row['name'].'</b>').$row['blurb']."<br/><br/>";
                echo __m('Please use the form below to indicate whether you would like to accept or decline this invitation.')."</br>";

                $form = Form::create('action', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module')."/units_mentorProcess.php?gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view");

                $form->addHiddenValue('address', $gibbon->session->get('address'));
                $form->addHiddenValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);
                $form->addHiddenValue('confirmationKey', $confirmationKey);
                $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);

                $responses = array(
                    "Y" => __m("Accept"),
                    "N" => __m("Decline")
                );
                $row = $form->addRow();
                    $row->addLabel('response', __('Response'));
                    $row->addSelect('response')->fromArray($responses)->placeholder()->required();

                $form->toggleVisibilityByClass('reasons')->onSelect('response')->when('N');

                $reasons = array(
                    "Incorrect teacher selected" => __m("Incorrect teacher selected"),
                    "Lack of time" => __m("Lack of time"),
                    "Unfamiliar with this unit" => __m("Unfamiliar with this unit"),
                    "Unfamiliar with this knowledge area" => __m("Unfamiliar with this knowledge area"),
                    "Unfamiliar with Free Learning" => __m("Unfamiliar with Free Learning"),
                    "Unit does not match student year group" => __m("Unit does not match student year group"),
                    "Other" => __m("Other")
                );
                $row = $form->addRow()->addClass('reasons');;
                    $row->addLabel('reason', __('Reason'));
                    $row->addSelect('reason')->fromArray($reasons)->placeholder()->required();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}


?>
