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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basica variables
$name="Free Learning" ;
$description="Free Learning is a module which enables a student-focused and student-driven pedagogy that goes by the same name as the module. Read more about Free Learning at http://rossparker.org/free-learning." ;
$entryURL="units_browse.php" ;
$type="Additional" ;
$category="Learn" ;
$version="0.1.00" ;
$author="Ross Parker" ;
$url="http://rossparker.org/free-learning" ;

//Module tables
$moduleTables[0]="
CREATE TABLE `freeLearningUnit` (
`freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `gibbonDepartmentIDList` text,
  `name` varchar(40) NOT NULL,
  `difficulty` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `overview` text NOT NULL,
  `license` varchar(50) DEFAULT NULL,
  `sharedPublic` enum('Y','N') DEFAULT NULL,
  `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

//Settings
$moduleTables[1]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'difficultyOptions', 'Difficulty Options', 'The range of dicciulty options available when creating units, from lowest to highest, as a comma-separated list.', 'Low,Medium,High');";
$moduleTables[2]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'publicUnits', 'Public Units', 'Should selected units be made available to members of the public, via the home page?', 'N');";
$moduleTables[3]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'unitOverviewTemplate', 'Unit Overview Template', 'An HTML template to be used as the default for all new units.', '');";

//Action rows
$actionRows[0]["name"]="Manage Units_all" ;
$actionRows[0]["precedence"]="1";
$actionRows[0]["category"]="" ;
$actionRows[0]["description"]="Allows privileged users to manage all Free Learning units." ;
$actionRows[0]["URLList"]="units_manage.php, units_manage_add.php, units_manage_edit.php, units_manage_delete.php" ;
$actionRows[0]["entryURL"]="units_manage.php" ;
$actionRows[0]["defaultPermissionAdmin"]="Y" ;
$actionRows[0]["defaultPermissionTeacher"]="N" ;
$actionRows[0]["defaultPermissionStudent"]="N" ;
$actionRows[0]["defaultPermissionParent"]="N" ;
$actionRows[0]["defaultPermissionSupport"]="N" ;
$actionRows[0]["categoryPermissionStaff"]="Y" ;
$actionRows[0]["categoryPermissionStudent"]="N" ;
$actionRows[0]["categoryPermissionParent"]="N" ;
$actionRows[0]["categoryPermissionOther"]="N" ;

$actionRows[1]["name"]="Manage Units_department" ;
$actionRows[1]["precedence"]="0";
$actionRows[1]["category"]="" ;
$actionRows[1]["description"]="Allows a privileged user within a department to manage all Free Learning units with their department." ;
$actionRows[1]["URLList"]="units_manage.php, units_manage_add.php, units_manage_edit.php, units_manage_delete.php" ;
$actionRows[1]["entryURL"]="units_manage.php" ;
$actionRows[1]["defaultPermissionAdmin"]="N" ;
$actionRows[1]["defaultPermissionTeacher"]="N" ;
$actionRows[1]["defaultPermissionStudent"]="N" ;
$actionRows[1]["defaultPermissionParent"]="N" ;
$actionRows[1]["defaultPermissionSupport"]="N" ;
$actionRows[1]["categoryPermissionStaff"]="Y" ;
$actionRows[1]["categoryPermissionStudent"]="N" ;
$actionRows[1]["categoryPermissionParent"]="N" ;
$actionRows[1]["categoryPermissionOther"]="N" ;

$actionRows[2]["name"]="Manage Settings" ;
$actionRows[2]["precedence"]="0";
$actionRows[2]["category"]="" ;
$actionRows[2]["description"]="Allows a privileged user to manage Free Learning settings." ;
$actionRows[2]["URLList"]="settings_manage.php" ;
$actionRows[2]["entryURL"]="settings_manage.php" ;
$actionRows[2]["defaultPermissionAdmin"]="Y" ;
$actionRows[2]["defaultPermissionTeacher"]="N" ;
$actionRows[2]["defaultPermissionStudent"]="N" ;
$actionRows[2]["defaultPermissionParent"]="N" ;
$actionRows[2]["defaultPermissionSupport"]="N" ;
$actionRows[2]["categoryPermissionStaff"]="Y" ;
$actionRows[2]["categoryPermissionStudent"]="N" ;
$actionRows[2]["categoryPermissionParent"]="N" ;
$actionRows[2]["categoryPermissionOther"]="N" ;

$array=array() ;
$array["toggleSettingName"]="publicUnits" ;
$array["toggleSettingScope"]="Free Learning" ;
$array["toggleSettingValue"]="Y" ;
$array["title"]="Free Learning With Us" ;
$array["text"]="Free Learning is a way to promote student independence and engagement, by encouraging students to find their own path through a set of content. As a member of the public, we invite you to <a href=\\'./index.php?q=/modules/Free Learning/units_browse.php\\'>browse a range of our units</a>." ;
$hooks[0]="INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Public Home Page ', '" . serialize($array) . "', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));" ;
?>