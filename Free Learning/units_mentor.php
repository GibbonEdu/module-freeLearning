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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');
$schoolType = getSettingByScope($connection2, 'Free Learning', 'schoolType');

$highestAction = false;
$canManage = false;
$gibbonPersonID ='';
if (isset($_SESSION[$guid]['gibbonPersonID'])) {
    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse.php', $connection2);
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $canManage = false;
    if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
        $canManage = true;
    }
    if ($canManage) {
        if (isset($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
        }
    }
}

//Get params
$freeLearningUnitID = '';
if (isset($_GET['freeLearningUnitID'])) {
    $freeLearningUnitID = $_GET['freeLearningUnitID'];
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
$freeLearningUnitID = null;
if (isset($_GET['freeLearningUnitID'])) {
    $freeLearningUnitID = $_GET['freeLearningUnitID'];
}

if ($freeLearningUnitID != '' && isset($_SESSION[$guid]['gibbonPersonID'])) {
    //Check student & confirmation key
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
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $row = $result->fetch();
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_browse_details.php)'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view'>".__($guid, 'Browse Units', 'Free Learning')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse_details.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&view=$view&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&applyAccessControls=$applyAccessControls&gibbonPersonID=$gibbonPersonID&sidebar=true&tab=2'>".__($guid, 'Unit Details', 'Free Learning')."</a> > </div><div class='trailEnd'>".__($guid, 'Approval', 'Free Learning').'</div>';
        echo '</div>';
    }
}
else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Free Learning Mentor Confirmation', 'Free Learning').'</div>';
    echo '</div>';
}

if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, array('success0' => __($guid, 'Your request was completed successfully. Thank you for your time.', 'Free Learning'), 'success1' => __($guid, 'Your request was completed successfully. Thank you for your time. The learners you are helping will be in touch in due course: in the meanwhile, no further action is required on your part.', 'Free Learning')));
}

?>
