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

include './moduleFunctions.php';

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

$freeLearningUnitID = $_POST['freeLearningUnitID'];
$authorList = $_POST['authorList'];
$authors = array();
if ($authorList != '') {
    $authors = explode(',', $authorList);
}
$mode = $_POST['mode']; //can be "add" or "remove"
$comment = '';
if (isset($_POST['comment'])) {
    $comment = $_POST['comment'];
}

if ($freeLearningUnitID == '' or count($authors) < 1 or ($mode != 'add' and $mode != 'remove')) {
    echo __($guid, 'Error');
} else {
    $script = '<script type="text/javascript">
		$(document).ready(function(){
			$("#starAdd'.$freeLearningUnitID.'").click(function(){
				$("#star'.$freeLearningUnitID.'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Free%20Learning/units_browse_starAjax.php",{"freeLearningUnitID": "'.$freeLearningUnitID.'", "mode": "add", "comment": "'.$comment.'", "authorList": "'.$authorList.'"});
			});
			$("#starRemove'.$freeLearningUnitID.'").click(function(){
				$("#star'.$freeLearningUnitID.'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Free%20Learning/units_browse_starAjax.php",{"freeLearningUnitID": "'.$freeLearningUnitID.'", "mode": "remove", "comment": "'.$comment.'", "authorList": "'.$authorList.'"});
			});
		});
	</script>';
    $on = $script."<a id='starRemove".$freeLearningUnitID."' onclick='return false;' href='#'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
    $off = $script."<a id='starAdd".$freeLearningUnitID."' onclick='return false;' href='#'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";

    //Act based on the mode
    if ($mode == 'add') { //ADD
        foreach ($authors as $author) {
            $return = setLike($connection2, 'Free Learning', $_SESSION[$guid]['gibbonSchoolYearID'], 'freeLearningUnitID', $freeLearningUnitID, $_SESSION[$guid]['gibbonPersonID'], $author, 'Free Learning - Unit Feedback', $comment);
        }
        echo $on;
    } elseif ($mode == 'remove') { //REMOVE
        foreach ($authors as $author) {
            $return = deleteLike($connection2, 'Free Learning', 'freeLearningUnitID', $freeLearningUnitID, $_SESSION[$guid]['gibbonPersonID'], $author, 'Free Learning - Unit Feedback');
        }
        echo $off;
    }
}
