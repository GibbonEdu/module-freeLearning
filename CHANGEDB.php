<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql=array() ;
$count=0 ;

//v0.1.00 - FIRST VERSION, SO NO CHANGES
$sql[$count][0]="0.1.00" ;
$sql[$count][1]="" ;

//v0.2.00
$count++ ;
$sql[$count][0]="0.2.00" ;
$sql[$count][1]="
UPDATE gibbonAction SET category='Admin' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') AND name='Manage Units_all';end
UPDATE gibbonAction SET category='Admin' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') AND name='Manage Units_learningAreas';end
UPDATE gibbonAction SET category='Admin' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') AND name='Manage Settings';end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Browse Units_all', 1, 'Learning', 'Allows a user to browse all active units.', 'units_browse.php, units_browse_details.php','units_browse.php', 'Y', 'Y', 'Y', 'N', 'N', 'Y', 'Y', 'Y', 'Y', 'Y');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Browse Units_all'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Browse Units_all'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '6', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Browse Units_all'));end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Browse Units_prerequisites', 0, 'Learning', 'Allows a user to browse all active units, with enforcement of prerequisite units.', 'units_browse.php, units_browse_details.php','units_browse.php', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '3', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Browse Units_prerequisites'));end
CREATE TABLE `freeLearningUnitStudent` (`freeLearningUnitStudentID` int(12) unsigned zerofill NOT NULL,  `gibbonPersonIDStudent` int(10) unsigned zerofill DEFAULT NULL,  `freeLearningUnitID` int(10) unsigned zerofill NOT NULL,  `status` enum('Current','Complete - Pending','Complete - Approved') NOT NULL DEFAULT 'Current',  `timestampJoined` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,  `timestampCompletePending` timestamp NULL DEFAULT NULL,  `timestampCompleteApproved` timestamp NULL DEFAULT NULL,  `gibbonPersonIDApproval` int(10) unsigned zerofill DEFAULT NULL,  `evidenceType` enum('File','Link') NOT NULL,  `evidenceURL` int(255) NOT NULL,  `commentStudent` text NOT NULL,  `commentApproval` text NOT NULL,  PRIMARY KEY (`freeLearningUnitStudentID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;end
" ;

//v0.3.00
$count++ ;
$sql[$count][0]="0.3.00" ;
$sql[$count][1]="
ALTER TABLE `freeLearningUnitStudent` CHANGE `freeLearningUnitStudentID` `freeLearningUnitStudentID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;end
" ;

//v0.4.00
$count++ ;
$sql[$count][0]="0.4.00" ;
$sql[$count][1]="
" ;

//v0.5.00
$count++ ;
$sql[$count][0]="0.5.00" ;
$sql[$count][1]="
UPDATE gibbonAction SET entrySidebar='N' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') AND name='Browse Units_all';end
UPDATE gibbonAction SET entrySidebar='N' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') AND name='Browse Units_prerequisites';end
" ;

//v0.6.00
$count++ ;
$sql[$count][0]="0.6.00" ;
$sql[$count][1]="
ALTER TABLE `freeLearningUnit` ADD `logo` VARCHAR(255) NULL DEFAULT NULL AFTER `name`;end
" ;

//v0.7.00
$count++ ;
$sql[$count][0]="0.7.00" ;
$sql[$count][1]="
ALTER TABLE `freeLearningUnit` ADD `grouping` VARCHAR(255) NOT NULL AFTER `active`, ADD `gibbonYearGroupIDMinimum` INT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `grouping`;end
" ;

//v0.8.00
$count++ ;
$sql[$count][0]="0.8.00" ;
$sql[$count][1]="
ALTER TABLE `freeLearningUnitStudent` ADD `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NOT NULL AFTER `freeLearningUnitID`;end
ALTER TABLE `freeLearningUnitStudent` ADD `grouping` ENUM('Individual','Pairs','Threes','Fours','Fives') NOT NULL AFTER `gibbonSchoolYearID`;end
ALTER TABLE `freeLearningUnitStudent` ADD `collaborationKey` VARCHAR(20) NULL DEFAULT NULL AFTER `grouping`;end
" ;

//v0.9.00
$count++ ;
$sql[$count][0]="0.9.00" ;
$sql[$count][1]="
ALTER TABLE `freeLearningUnitStudent` CHANGE `evidenceURL` `evidenceLocation` INT(255) NOT NULL;end
ALTER TABLE `freeLearningUnitStudent` CHANGE `evidenceLocation` `evidenceLocation` TEXT NOT NULL;end
ALTER TABLE `freeLearningUnitStudent` ADD `gibbonCourseClassID` INT(8) UNSIGNED ZEROFILL NOT NULL AFTER `gibbonSchoolYearID`;end
UPDATE gibbonAction SET URLList='units_browse.php, units_browse_details.php, units_browse_details_approval.php' WHERE name='Browse Units_all' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning');end
" ;

//v1.0.00
$count++ ;
$sql[$count][0]="1.0.00" ;
$sql[$count][1]="" ;

?>