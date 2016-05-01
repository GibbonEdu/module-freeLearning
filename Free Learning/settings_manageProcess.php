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

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/settings_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/settings_manage') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $schoolType = $_POST['schoolType'];
    $difficultyOptions = $_POST['difficultyOptions'];
    $publicUnits = $_POST['publicUnits'];
    $unitOutlineTemplate = $_POST['unitOutlineTemplate'];
    $learningAreaRestriction = $_POST['learningAreaRestriction'];

    //Validate Inputs
    if ($difficultyOptions == '' or $publicUnits == '' or $learningAreaRestriction == '') {
        //Fail 3
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Write to database
        $fail = false;

        try {
            $data = array('schoolType' => $schoolType);
            $sql = "UPDATE gibbonSetting SET value=:schoolType WHERE scope='Free Learning' AND name='schoolType'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('difficultyOptions' => $difficultyOptions);
            $sql = "UPDATE gibbonSetting SET value=:difficultyOptions WHERE scope='Free Learning' AND name='difficultyOptions'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('publicUnits' => $publicUnits);
            $sql = "UPDATE gibbonSetting SET value=:publicUnits WHERE scope='Free Learning' AND name='publicUnits'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('unitOutlineTemplate' => $unitOutlineTemplate);
            $sql = "UPDATE gibbonSetting SET value=:unitOutlineTemplate WHERE scope='Free Learning' AND name='unitOutlineTemplate'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('learningAreaRestriction' => $learningAreaRestriction);
            $sql = "UPDATE gibbonSetting SET value=:learningAreaRestriction WHERE scope='Free Learning' AND name='learningAreaRestriction'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($fail == true) {
            //Fail 2
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Success 0
            getSystemSettings($guid, $connection2);
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
