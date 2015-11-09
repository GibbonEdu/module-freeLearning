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
$description="Free Learning is a module which enables a student-focused and student-driven pedagogy that goes by the same name as the module (see <a href='http://rossparker.org/free-learning'>http://rossparker.org/free-learning</a> for more)." ;
$entryURL="units_browse.php" ;
$type="Additional" ;
$category="Learn" ;
$version="2.6.01" ;
$author="Ross Parker" ;
$url="http://rossparker.org/free-learning" ;

//Module tables
$moduleTables[0]="CREATE TABLE `freeLearningUnit` (
`freeLearningUnitID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonDepartmentIDList` text,
  `name` varchar(40) NOT NULL,
  `logo` text,
  `active` enum('Y','N') DEFAULT 'Y',
  `grouping` VARCHAR(255) NOT NULL,
  `gibbonYearGroupIDMinimum` INT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `difficulty` varchar(255) NOT NULL,
  `blurb` text NOT NULL,
  `outline` text NOT NULL,
  `license` varchar(50) DEFAULT NULL,
  `sharedPublic` enum('Y','N') DEFAULT NULL,
  `freeLearningUnitIDPrerequisiteList` text,
  `gibbonPersonIDCreator` int(10) unsigned zerofill NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`freeLearningUnitID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[1]="CREATE TABLE `freeLearningUnitBlock` (
`freeLearningUnitBlockID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `length` varchar(3) NOT NULL,
  `contents` text NOT NULL,
  `teachersNotes` text NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  PRIMARY KEY (`freeLearningUnitBlockID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[2]="CREATE TABLE `freeLearningUnitOutcome` (
`freeLearningUnitOutcomeID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `gibbonOutcomeID` int(8) unsigned zerofill NOT NULL,
  `sequenceNumber` int(4) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`freeLearningUnitOutcomeID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[3]="CREATE TABLE `freeLearningUnitAuthor` (
`freeLearningUnitAuthorID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `gibbonPersonID` int(8) unsigned zerofill DEFAULT NULL,
  `surname` varchar(30) NOT NULL,
  `preferredName` varchar(30) NOT NULL,
  `website` varchar(255) NOT NULL,
  PRIMARY KEY (`freeLearningUnitAuthorID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[4]="CREATE TABLE `freeLearningUnitStudent` (
`freeLearningUnitStudentID` int(12) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonPersonIDStudent` int(10) unsigned zerofill DEFAULT NULL,
  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,
  `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `gibbonCourseClassID` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL,
  `grouping` ENUM('Individual','Pairs','Threes','Fours','Fives') NOT NULL,
  `collaborationKey` VARCHAR(20) NULL DEFAULT NULL,
  `status` enum('Current','Complete - Pending','Complete - Approved','Exempt','Evidence Not Approved') NOT NULL DEFAULT 'Current',
  `timestampJoined` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampCompletePending` timestamp NULL DEFAULT NULL,
  `timestampCompleteApproved` timestamp NULL DEFAULT NULL,
  `gibbonPersonIDApproval` int(10) unsigned zerofill DEFAULT NULL,
  `evidenceType` enum('File','Link') NOT NULL,
  `evidenceLocation` text NOT NULL,
  `commentStudent` text NOT NULL,
  `commentApproval` text NOT NULL,
  `exemplarWork` enum('N','Y') NOT NULL DEFAULT 'N',
  `exemplarWorkThumb` text NOT NULL,
  `exemplarWorkLicense` varchar(255) NOT NULL,
  PRIMARY KEY (`freeLearningUnitStudentID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

//Settings
$moduleTables[5]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'difficultyOptions', 'Difficulty Options', 'The range of dicciulty options available when creating units, from lowest to highest, as a comma-separated list.', 'Low,Medium,High');";
$moduleTables[6]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'publicUnits', 'Public Units', 'Should selected units be made available to members of the public, via the home page?', 'N');";
$moduleTables[7]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'unitOutlineTemplate', 'Unit Outline Template', 'An HTML template to be used as the default for all new units.', '');";
$moduleTables[8]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'mapLink', 'Map Link', 'A URL pointing to a map of the available units.', '');";
$moduleTables[9]="INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'schoolType', 'School Type', 'Determines how enrolment should function', 'Physical');";

//Action rows
$actionRows[0]["name"]="Manage Units_all" ;
$actionRows[0]["precedence"]="1";
$actionRows[0]["category"]="Admin" ;
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

$actionRows[1]["name"]="Manage Units_learningAreas" ;
$actionRows[1]["precedence"]="0";
$actionRows[1]["category"]="Admin" ;
$actionRows[1]["description"]="Allows a privileged user within a learning area to manage all Free Learning units with their learning area." ;
$actionRows[1]["URLList"]="units_manage.php, units_manage_add.php, units_manage_edit.php, units_manage_delete.php" ;
$actionRows[1]["entryURL"]="units_manage.php" ;
$actionRows[1]["defaultPermissionAdmin"]="N" ;
$actionRows[1]["defaultPermissionTeacher"]="Y" ;
$actionRows[1]["defaultPermissionStudent"]="N" ;
$actionRows[1]["defaultPermissionParent"]="N" ;
$actionRows[1]["defaultPermissionSupport"]="N" ;
$actionRows[1]["categoryPermissionStaff"]="Y" ;
$actionRows[1]["categoryPermissionStudent"]="N" ;
$actionRows[1]["categoryPermissionParent"]="N" ;
$actionRows[1]["categoryPermissionOther"]="N" ;

$actionRows[2]["name"]="Manage Settings" ;
$actionRows[2]["precedence"]="0";
$actionRows[2]["category"]="Admin" ;
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

$actionRows[3]["name"]="Browse Units_all" ;
$actionRows[3]["precedence"]="1";
$actionRows[3]["category"]="Learning" ;
$actionRows[3]["description"]="Allows a user to browse all active units." ;
$actionRows[3]["URLList"]="units_browse.php, units_browse_details.php, units_browse_details_approval.php" ;
$actionRows[3]["entryURL"]="units_browse.php" ;
$actionRows[3]["entrySidebar"]="N" ;
$actionRows[3]["defaultPermissionAdmin"]="Y" ;
$actionRows[3]["defaultPermissionTeacher"]="Y" ;
$actionRows[3]["defaultPermissionStudent"]="N" ;
$actionRows[3]["defaultPermissionParent"]="N" ;
$actionRows[3]["defaultPermissionSupport"]="Y" ;
$actionRows[3]["categoryPermissionStaff"]="Y" ;
$actionRows[3]["categoryPermissionStudent"]="Y" ;
$actionRows[3]["categoryPermissionParent"]="Y" ;
$actionRows[3]["categoryPermissionOther"]="Y" ;

$actionRows[4]["name"]="Browse Units_prerequisites" ;
$actionRows[4]["precedence"]="0";
$actionRows[4]["category"]="Learning" ;
$actionRows[4]["description"]="Allows a user to browse all active units, with enforcement of prerequisite units." ;
$actionRows[4]["URLList"]="units_browse.php, units_browse_details.php" ;
$actionRows[4]["entryURL"]="units_browse.php" ;
$actionRows[4]["entrySidebar"]="N" ;
$actionRows[4]["defaultPermissionAdmin"]="N" ;
$actionRows[4]["defaultPermissionTeacher"]="N" ;
$actionRows[4]["defaultPermissionStudent"]="Y" ;
$actionRows[4]["defaultPermissionParent"]="N" ;
$actionRows[4]["defaultPermissionSupport"]="N" ;
$actionRows[4]["categoryPermissionStaff"]="N" ;
$actionRows[4]["categoryPermissionStudent"]="Y" ;
$actionRows[4]["categoryPermissionParent"]="N" ;
$actionRows[4]["categoryPermissionOther"]="N" ;

$actionRows[5]["name"]="Current Unit By Class" ;
$actionRows[5]["precedence"]="0";
$actionRows[5]["category"]="Reports" ;
$actionRows[5]["description"]="Allows a user to see all classes in the school, with each student\'s current unit choice." ;
$actionRows[5]["URLList"]="report_currentUnitByClass.php" ;
$actionRows[5]["entryURL"]="report_currentUnitByClass.php" ;
$actionRows[5]["entrySidebar"]="Y" ;
$actionRows[5]["defaultPermissionAdmin"]="Y" ;
$actionRows[5]["defaultPermissionTeacher"]="Y" ;
$actionRows[5]["defaultPermissionStudent"]="N" ;
$actionRows[5]["defaultPermissionParent"]="N" ;
$actionRows[5]["defaultPermissionSupport"]="N" ;
$actionRows[5]["categoryPermissionStaff"]="Y" ;
$actionRows[5]["categoryPermissionStudent"]="Y" ;
$actionRows[5]["categoryPermissionParent"]="N" ;
$actionRows[5]["categoryPermissionOther"]="N" ;

$actionRows[6]["name"]="Unit History By Student_all" ;
$actionRows[6]["precedence"]="1";
$actionRows[6]["category"]="Reports" ;
$actionRows[6]["description"]="Allows a user to see all units undertaken by any student." ;
$actionRows[6]["URLList"]="report_unitHistory_byStudent.php" ;
$actionRows[6]["entryURL"]="report_unitHistory_byStudent.php" ;
$actionRows[6]["entrySidebar"]="Y" ;
$actionRows[6]["defaultPermissionAdmin"]="Y" ;
$actionRows[6]["defaultPermissionTeacher"]="Y" ;
$actionRows[6]["defaultPermissionStudent"]="N" ;
$actionRows[6]["defaultPermissionParent"]="N" ;
$actionRows[6]["defaultPermissionSupport"]="N" ;
$actionRows[6]["categoryPermissionStaff"]="Y" ;
$actionRows[6]["categoryPermissionStudent"]="Y" ;
$actionRows[6]["categoryPermissionParent"]="N" ;
$actionRows[6]["categoryPermissionOther"]="N" ;

$actionRows[7]["name"]="Unit History By Student_myChildren" ;
$actionRows[7]["precedence"]="0";
$actionRows[7]["category"]="Reports" ;
$actionRows[7]["description"]="Allows a user to see all units undertaken by their own children." ;
$actionRows[7]["URLList"]="report_unitHistory_byStudent.php" ;
$actionRows[7]["entryURL"]="report_unitHistory_byStudent.php" ;
$actionRows[7]["entrySidebar"]="Y" ;
$actionRows[7]["defaultPermissionAdmin"]="N" ;
$actionRows[7]["defaultPermissionTeacher"]="N" ;
$actionRows[7]["defaultPermissionStudent"]="N" ;
$actionRows[7]["defaultPermissionParent"]="Y" ;
$actionRows[7]["defaultPermissionSupport"]="N" ;
$actionRows[7]["categoryPermissionStaff"]="N" ;
$actionRows[7]["categoryPermissionStudent"]="N" ;
$actionRows[7]["categoryPermissionParent"]="Y" ;
$actionRows[7]["categoryPermissionOther"]="N" ;

$actionRows[8]["name"]="Outcomes By Student" ;
$actionRows[8]["precedence"]="0";
$actionRows[8]["category"]="Reports" ;
$actionRows[8]["description"]="Allows a user to see all outcomes met by a given student." ;
$actionRows[8]["URLList"]="report_outcomes_byStudent.php" ;
$actionRows[8]["entryURL"]="report_outcomes_byStudent.php" ;
$actionRows[8]["entrySidebar"]="Y" ;
$actionRows[8]["defaultPermissionAdmin"]="Y" ;
$actionRows[8]["defaultPermissionTeacher"]="N" ;
$actionRows[8]["defaultPermissionStudent"]="N" ;
$actionRows[8]["defaultPermissionParent"]="N" ;
$actionRows[8]["defaultPermissionSupport"]="N" ;
$actionRows[8]["categoryPermissionStaff"]="Y" ;
$actionRows[8]["categoryPermissionStudent"]="N" ;
$actionRows[8]["categoryPermissionParent"]="Y" ;
$actionRows[8]["categoryPermissionOther"]="N" ;

$actionRows[9]["name"]="My Unit History" ;
$actionRows[9]["precedence"]="0";
$actionRows[9]["category"]="Learning" ;
$actionRows[9]["description"]="Allows a student to see all the units they have studied and are studying." ;
$actionRows[9]["URLList"]="report_unitHistory_my.php" ;
$actionRows[9]["entryURL"]="report_unitHistory_my.php" ;
$actionRows[9]["entrySidebar"]="Y" ;
$actionRows[9]["defaultPermissionAdmin"]="N" ;
$actionRows[9]["defaultPermissionTeacher"]="N" ;
$actionRows[9]["defaultPermissionStudent"]="Y" ;
$actionRows[9]["defaultPermissionParent"]="N" ;
$actionRows[9]["defaultPermissionSupport"]="N" ;
$actionRows[9]["categoryPermissionStaff"]="N" ;
$actionRows[9]["categoryPermissionStudent"]="Y" ;
$actionRows[9]["categoryPermissionParent"]="N" ;
$actionRows[9]["categoryPermissionOther"]="N" ;

$actionRows[10]["name"]="Free Learning Showcase" ;
$actionRows[10]["precedence"]="0";
$actionRows[10]["category"]="Learning" ;
$actionRows[10]["description"]="Allows users to view Exemplar Work from across the system, in one place." ;
$actionRows[10]["URLList"]="showcase.php" ;
$actionRows[10]["entryURL"]="showcase.php" ;
$actionRows[10]["entrySidebar"]="N" ;
$actionRows[10]["defaultPermissionAdmin"]="Y" ;
$actionRows[10]["defaultPermissionTeacher"]="Y" ;
$actionRows[10]["defaultPermissionStudent"]="Y" ;
$actionRows[10]["defaultPermissionParent"]="Y" ;
$actionRows[10]["defaultPermissionSupport"]="Y" ;
$actionRows[10]["categoryPermissionStaff"]="Y" ;
$actionRows[10]["categoryPermissionStudent"]="Y" ;
$actionRows[10]["categoryPermissionParent"]="Y" ;
$actionRows[10]["categoryPermissionOther"]="Y" ;

$actionRows[11]["name"]="Work Pending Approval" ;
$actionRows[11]["precedence"]="0";
$actionRows[11]["category"]="Reports" ;
$actionRows[11]["description"]="Allows a user to see all work for which approval has been requested, and is still pending." ;
$actionRows[11]["URLList"]="report_workPendingApproval.php" ;
$actionRows[11]["entryURL"]="report_workPendingApproval.php" ;
$actionRows[11]["entrySidebar"]="Y" ;
$actionRows[11]["defaultPermissionAdmin"]="Y" ;
$actionRows[11]["defaultPermissionTeacher"]="Y" ;
$actionRows[11]["defaultPermissionStudent"]="N" ;
$actionRows[11]["defaultPermissionParent"]="N" ;
$actionRows[11]["defaultPermissionSupport"]="N" ;
$actionRows[11]["categoryPermissionStaff"]="Y" ;
$actionRows[11]["categoryPermissionStudent"]="N" ;
$actionRows[11]["categoryPermissionParent"]="N" ;
$actionRows[11]["categoryPermissionOther"]="N" ;


$array=array() ;
$array["toggleSettingName"]="publicUnits" ;
$array["toggleSettingScope"]="Free Learning" ;
$array["toggleSettingValue"]="Y" ;
$array["title"]="Free Learning With Us" ;
$array["text"]="Free Learning is a way to promote student independence and engagement, by encouraging students to find their own path through a set of content. As a member of the public, we invite you to <a href=\'./index.php?q=/modules/Free Learning/units_browse.php\'>browse a range of our units</a>." ;
$hooks[0]="INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Public Home Page ', '" . serialize($array) . "', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));" ;

$array=array() ;
$array["sourceModuleName"]="Free Learning" ;
$array["sourceModuleAction"]="Unit History By Student_all" ;
$array["sourceModuleInclude"]="hook_studentProfile_unitHistory.php" ;
$hooks[1]="INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Student Profile', '" . serialize($array) . "', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));" ;

$array=array() ;
$array["sourceModuleName"]="Free Learning" ;
$array["sourceModuleAction"]="Unit History By Student_myChildren" ;
$array["sourceModuleInclude"]="hook_parentalDashboard_unitHistory.php" ;
$hooks[2]="INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Parental Dashboard', '" . serialize($array) . "', (SELECT gibbonModuleID FROM gibbonModule WHERE name='$name'));" ;
?>