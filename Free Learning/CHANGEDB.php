<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = array();
$count = 0;

//v0.1.00 - FIRST VERSION, SO NO CHANGES
$sql[$count][0] = '0.1.00';
$sql[$count][1] = '';

//v0.2.00
++$count;
$sql[$count][0] = '0.2.00';
$sql[$count][1] = "
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
";

//v0.3.00
++$count;
$sql[$count][0] = '0.3.00';
$sql[$count][1] = '
ALTER TABLE `freeLearningUnitStudent` CHANGE `freeLearningUnitStudentID` `freeLearningUnitStudentID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;end
';

//v0.4.00
++$count;
$sql[$count][0] = '0.4.00';
$sql[$count][1] = '
';

//v0.5.00
++$count;
$sql[$count][0] = '0.5.00';
$sql[$count][1] = "
UPDATE gibbonAction SET entrySidebar='N' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') AND name='Browse Units_all';end
UPDATE gibbonAction SET entrySidebar='N' WHERE gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') AND name='Browse Units_prerequisites';end
";

//v0.6.00
++$count;
$sql[$count][0] = '0.6.00';
$sql[$count][1] = '
ALTER TABLE `freeLearningUnit` ADD `logo` VARCHAR(255) NULL DEFAULT NULL AFTER `name`;end
';

//v0.7.00
++$count;
$sql[$count][0] = '0.7.00';
$sql[$count][1] = '
ALTER TABLE `freeLearningUnit` ADD `grouping` VARCHAR(255) NOT NULL AFTER `active`, ADD `gibbonYearGroupIDMinimum` INT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `grouping`;end
';

//v0.8.00
++$count;
$sql[$count][0] = '0.8.00';
$sql[$count][1] = "
ALTER TABLE `freeLearningUnitStudent` ADD `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NOT NULL AFTER `freeLearningUnitID`;end
ALTER TABLE `freeLearningUnitStudent` ADD `grouping` ENUM('Individual','Pairs','Threes','Fours','Fives') NOT NULL AFTER `gibbonSchoolYearID`;end
ALTER TABLE `freeLearningUnitStudent` ADD `collaborationKey` VARCHAR(20) NULL DEFAULT NULL AFTER `grouping`;end
";

//v0.9.00
++$count;
$sql[$count][0] = '0.9.00';
$sql[$count][1] = "
ALTER TABLE `freeLearningUnitStudent` CHANGE `evidenceURL` `evidenceLocation` INT(255) NOT NULL;end
ALTER TABLE `freeLearningUnitStudent` CHANGE `evidenceLocation` `evidenceLocation` TEXT NOT NULL;end
ALTER TABLE `freeLearningUnitStudent` ADD `gibbonCourseClassID` INT(8) UNSIGNED ZEROFILL NOT NULL AFTER `gibbonSchoolYearID`;end
UPDATE gibbonAction SET URLList='units_browse.php, units_browse_details.php, units_browse_details_approval.php' WHERE name='Browse Units_all' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning');end
";

//v1.0.00
++$count;
$sql[$count][0] = '1.0.00';
$sql[$count][1] = "
ALTER TABLE `freeLearningUnitStudent` CHANGE `status` `status` ENUM('Current','Complete - Pending','Complete - Approved','Exempt') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Current';end
";

//v1.1.00
++$count;
$sql[$count][0] = '1.1.00';
$sql[$count][1] = '';

//v1.2.00
++$count;
$sql[$count][0] = '1.2.00';
$sql[$count][1] = "
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Current Unit By Class', 0, 'Reports', 'Allows a user to see all classes in the school, with each student\'s current unit choice.', 'report_currentUnitByClass.php','report_currentUnitByClass.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Current Unit By Class'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Current Unit By Class'));end
";

//v1.2.01
++$count;
$sql[$count][0] = '1.2.01';
$sql[$count][1] = "
ALTER TABLE `freeLearningUnitAuthor` ADD `surname` VARCHAR(30) NOT NULL , ADD `preferredName` VARCHAR(30) NOT NULL , ADD `website` VARCHAR(255) NOT NULL ;end
ALTER TABLE `freeLearningUnit` CHANGE `logo` `logo` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;end
ALTER TABLE `freeLearningUnitAuthor` CHANGE `gibbonPersonID` `gibbonPersonID` INT(8) UNSIGNED ZEROFILL NULL DEFAULT NULL;end
UPDATE freeLearningUnit SET logo=concat((SELECT value FROM gibbonSetting WHERE name='absoluteURL'), '/', logo) ;end
UPDATE freeLearningUnitAuthor SET surname=(SELECT surname FROM gibbonPerson WHERE gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID) ;end
UPDATE freeLearningUnitAuthor SET preferredName=(SELECT preferredName FROM gibbonPerson WHERE gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID) ;end
UPDATE freeLearningUnitAuthor SET website=(SELECT website FROM gibbonPerson WHERE gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID) ;end
";

//v1.3.00
++$count;
$sql[$count][0] = '1.3.00';
$sql[$count][1] = "
INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'mapLink', 'Map Link', 'A URL pointing to a map of the available units.', '');end
";

//v1.4.00
++$count;
$sql[$count][0] = '1.4.00';
$sql[$count][1] = "
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Unit History By Student', 0, 'Reports', 'Allows a user to see all units undertaken by a student.', 'report_unitHistory_byStudent.php','report_unitHistory_byStudent.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'Y', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Unit History By Student'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Unit History By Student'));end
INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning Unit History', 'Student Profile', 'a:3:{s:16:\"sourceModuleName\";s:13:\"Free Learning\";s:18:\"sourceModuleAction\";s:23:\"Unit History By Student\";s:19:\"sourceModuleInclude\";s:35:\"hook_studentProfile_unitHistory.php\";}', (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'));end
";

//v1.4.01
++$count;
$sql[$count][0] = '1.4.01';
$sql[$count][1] = "
ALTER TABLE `freeLearningUnitStudent` CHANGE `status` `status` ENUM('Current','Complete - Pending','Complete - Approved','Exempt','Evidence Not Approved') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Current';end
";

//v1.4.02
++$count;
$sql[$count][0] = '1.4.02';
$sql[$count][1] = '';

//v1.4.03
++$count;
$sql[$count][0] = '1.4.03';
$sql[$count][1] = '';

//v1.5.00
++$count;
$sql[$count][0] = '1.5.00';
$sql[$count][1] = "
UPDATE gibbonAction SET name='Unit History By Student_all', precedence='1', description='Allows a user to see all units undertaken by any student.' WHERE name='Unit History By Student' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning');end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Unit History By Student_myChildren', 0, 'Reports', 'Allows a user to see all units undertaken by their own children.', 'report_unitHistory_byStudent.php','report_unitHistory_byStudent.php', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '4', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Unit History By Student_myChildren'));end
INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Parental Dashboard', 'a:3:{s:16:\"sourceModuleName\";s:13:\"Free Learning\";s:18:\"sourceModuleAction\";s:34:\"Unit History By Student_myChildren\";s:19:\"sourceModuleInclude\";s:38:\"hook_parentalDashboard_unitHistory.php\";}', (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'));end
UPDATE gibbonHook SET `options`='a:3:{s:16:\"sourceModuleName\";s:13:\"Free Learning\";s:18:\"sourceModuleAction\";s:27:\"Unit History By Student_all\";s:19:\"sourceModuleInclude\";s:35:\"hook_studentProfile_unitHistory.php\";}' WHERE name='Free Learning Unit History' AND type='Student Profile' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonModule.name='Free Learning');end
";

//v2.0.00
++$count;
$sql[$count][0] = '2.0.00';
$sql[$count][1] = "
UPDATE gibbonHook SET name='Free Learning' WHERE name='Free Learning Unit History' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning') ;end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Outcomes By Student', 0, 'Reports', 'Allows a user to see all outcomes met by a given student.', 'report_outcomes_byStudent.php','report_outcomes_byStudent.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Outcomes By Student'));end
";

//v2.1.00
++$count;
$sql[$count][0] = '2.1.00';
$sql[$count][1] = "
INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'schoolType', 'School Type', 'Determines how enrolment should function', 'Physical');end
ALTER TABLE `freeLearningUnitStudent` CHANGE `gibbonSchoolYearID` `gibbonSchoolYearID` INT( 3 ) UNSIGNED ZEROFILL NULL DEFAULT NULL ;end
ALTER TABLE `freeLearningUnitStudent` CHANGE `gibbonCourseClassID` `gibbonCourseClassID` INT( 8 ) UNSIGNED ZEROFILL NULL DEFAULT NULL ;
";

//v2.1.01
++$count;
$sql[$count][0] = '2.1.01';
$sql[$count][1] = '
';

//v2.1.02
++$count;
$sql[$count][0] = '2.1.02';
$sql[$count][1] = '
';

//v2.2.00
++$count;
$sql[$count][0] = '2.2.00';
$sql[$count][1] = '
';

//v2.2.01
++$count;
$sql[$count][0] = '2.2.01';
$sql[$count][1] = '
';

//v2.2.02
++$count;
$sql[$count][0] = '2.2.02';
$sql[$count][1] = '
';

//v2.3.00
++$count;
$sql[$count][0] = '2.3.00';
$sql[$count][1] = "
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'My Unit History', 0, 'Learning', 'Allows a student to see all the units they have studied and are studying.', 'report_unitHistory_my.php','report_unitHistory_my.php', 'Y', 'N', 'N', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '3', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='My Unit History'));end
ALTER TABLE `freeLearningUnitStudent` ADD `examplarWork` ENUM('N','Y') NOT NULL DEFAULT 'N' AFTER `commentApproval`;end
";

//v2.3.01
++$count;
$sql[$count][0] = '2.3.01';
$sql[$count][1] = '
';

//v2.3.02
++$count;
$sql[$count][0] = '2.3.02';
$sql[$count][1] = '
';

//v2.4.00
++$count;
$sql[$count][0] = '2.4.00';
$sql[$count][1] = "
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Free Learning Showcase', 0, 'Learning', 'Allows users to view Exemplar Work from across the system, in one place.', 'showcase.php','showcase.php', 'N', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Free Learning Showcase'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Free Learning Showcase'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '3', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Free Learning Showcase'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '4', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Free Learning Showcase'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '6', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Free Learning Showcase'));end
ALTER TABLE `freeLearningUnitStudent` CHANGE `examplarWork` `exemplarWork` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N';end
ALTER TABLE `freeLearningUnitStudent` ADD `exemplarWorkThumb` text NOT NULL AFTER `exemplarWork`, ADD `exemplarWorkLicense` VARCHAR(255) NOT NULL AFTER `exemplarWorkThumb`;end
";

//v2.4.01
++$count;
$sql[$count][0] = '2.4.01';
$sql[$count][1] = '
';

//v2.4.02
++$count;
$sql[$count][0] = '2.4.02';
$sql[$count][1] = '
';

//v2.4.03
++$count;
$sql[$count][0] = '2.4.03';
$sql[$count][1] = '
';

//v2.4.04
++$count;
$sql[$count][0] = '2.4.04';
$sql[$count][1] = '
';

//v2.5.00
++$count;
$sql[$count][0] = '2.5.00';
$sql[$count][1] = "
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Work Pending Approval', 0, 'Reports', 'Allows a user to see all work for which approval has been requested, and is still pending.', 'report_workPendingApproval.php','report_workPendingApproval.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Work Pending Approval'));end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '2', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Work Pending Approval'));end
";

//v2.6.00
++$count;
$sql[$count][0] = '2.6.00';
$sql[$count][1] = "
DELETE FROM gibbonSetting WHERE name='mapLink' AND scope='Free Learning';end
";

//v2.6.01
++$count;
$sql[$count][0] = '2.6.01';
$sql[$count][1] = "
UPDATE gibbonModule SET entryURL='units_browse.php' WHERE name='Free Learning';end
";

//v2.6.02
++$count;
$sql[$count][0] = '2.6.02';
$sql[$count][1] = '';

//v2.6.03
++$count;
$sql[$count][0] = '2.6.03';
$sql[$count][1] = '';

//v2.7.00
++$count;
$sql[$count][0] = '2.7.00';
$sql[$count][1] = '';

//v3.0.00
++$count;
$sql[$count][0] = '3.0.00';
$sql[$count][1] = '';

//v3.0.01
++$count;
$sql[$count][0] = '3.0.01';
$sql[$count][1] = '';

//v3.0.02
++$count;
$sql[$count][0] = '3.0.02';
$sql[$count][1] = '';

//v3.0.03
++$count;
$sql[$count][0] = '3.0.03';
$sql[$count][1] = '';

//v3.0.04
++$count;
$sql[$count][0] = '3.0.04';
$sql[$count][1] = '';

//v3.0.05
++$count;
$sql[$count][0] = '3.0.05';
$sql[$count][1] = '';

//v3.0.06
++$count;
$sql[$count][0] = '3.0.06';
$sql[$count][1] = '';

//v3.0.07
++$count;
$sql[$count][0] = '3.0.07';
$sql[$count][1] = "INSERT INTO `gibbonHook` (`gibbonHookID`, `name`, `type`, `options`, gibbonModuleID) VALUES (NULL, 'Free Learning', 'Student Dashboard', 'a:3:{s:16:\"sourceModuleName\";s:13:\"Free Learning\";s:18:\"sourceModuleAction\";s:15:\"My Unit History\";s:19:\"sourceModuleInclude\";s:37:\"hook_studentDashboard_unitHistory.php\";}', (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'));end
";

//v3.0.08
++$count;
$sql[$count][0] = '3.0.08';
$sql[$count][1] = '';

//v3.0.09
++$count;
$sql[$count][0] = '3.0.09';
$sql[$count][1] = '';

//v3.0.10
++$count;
$sql[$count][0] = '3.0.10';
$sql[$count][1] = '';

//v3.0.11
++$count;
$sql[$count][0] = '3.0.11';
$sql[$count][1] = '';

//v3.0.12
++$count;
$sql[$count][0] = '3.0.12';
$sql[$count][1] = '';

//v3.1.00
++$count;
$sql[$count][0] = '3.1.00';
$sql[$count][1] = "
INSERT INTO `gibbonSetting` (`gibbonSystemSettingsID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Free Learning', 'learningAreaRestriction', 'Learning Area Restriction', 'Should unit creation be limited to own Learning Areas?', 'Y');end
";

//v3.1.01
++$count;
$sql[$count][0] = '3.1.01';
$sql[$count][1] = '';

//v3.1.02
++$count;
$sql[$count][0] = '3.1.02';
$sql[$count][1] = '';

//v3.1.03
++$count;
$sql[$count][0] = '3.1.03';
$sql[$count][1] = '';

//v3.1.04
++$count;
$sql[$count][0] = '3.1.04';
$sql[$count][1] = '';

//v3.1.05
++$count;
$sql[$count][0] = '3.1.05';
$sql[$count][1] = '';

//v4.0.00
++$count;
$sql[$count][0] = '4.0.00';
$sql[$count][1] = "ALTER TABLE `freeLearningUnit` ADD `availableStudents` ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER `license`, ADD `availableStaff` ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER `availableStudents`, ADD `availableParents` ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER `availableStaff`;end
UPDATE gibbonAction SET categoryPermissionStaff='Y', categoryPermissionStudent='Y', categoryPermissionParent='Y' WHERE name='Browse Units_prerequisites' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning');end
ALTER TABLE `freeLearningUnitStudent` ADD `enrolmentMethod` ENUM('class','schoolMentor','externalMentor') NOT NULL DEFAULT 'class' AFTER `gibbonSchoolYearID`;end
ALTER TABLE `freeLearningUnitStudent` ADD `gibbonPersonIDSchoolMentor` INT(10) UNSIGNED ZEROFILL NULL DEFAULT NULL AFTER `gibbonCourseClassID`, ADD `emailExternalMentor` VARCHAR(255) NULL DEFAULT NULL AFTER `gibbonPersonIDSchoolMentor`, ADD `nameExternalMentor` VARCHAR(255) NULL DEFAULT NULL AFTER `emailExternalMentor`;end
ALTER TABLE `freeLearningUnitStudent` ADD `confirmationKey` VARCHAR(20) NULL DEFAULT NULL AFTER `collaborationKey`;
ALTER TABLE `freeLearningUnitStudent` CHANGE `status` `status` ENUM('Current','Current - Pending','Complete - Pending','Complete - Approved','Exempt','Evidence Not Approved') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Current';end
";

//v4.0.01
++$count;
$sql[$count][0] = '4.0.01';
$sql[$count][1] = "UPDATE freeLearningUnitStudent SET status='Current' WHERE status='Current - Pending' AND enrolmentMethod='class';end";

//v4.0.02
++$count;
$sql[$count][0] = '4.0.02';
$sql[$count][1] = "";

//v4.1.00
++$count;
$sql[$count][0] = '4.1.00';
$sql[$count][1] = "
CREATE TABLE `freeLearningBadge` (  `freeLearningBadgeID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,  `badgesBadgeID` int(8) unsigned zerofill NOT NULL,  `active` enum('Y','N') NOT NULL DEFAULT 'Y', `unitsCompleteTotal` int(2) DEFAULT NULL,  `unitsCompleteThisYear` int(2) DEFAULT NULL,  `unitsCompleteDepartmentCount` int(2) DEFAULT NULL,`unitsCompleteIndividual` int(2) DEFAULT NULL,`unitsCompleteGroup` int(2) DEFAULT NULL,`difficultyLevelMaxAchieved` varchar(255) DEFAULT NULL, PRIMARY KEY (`freeLearningBadgeID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;end
INSERT INTO `gibbonAction` (`gibbonActionID`, `gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES (NULL, (SELECT gibbonModuleID FROM gibbonModule WHERE name='Free Learning'), 'Manage Badges', 0, 'Gamification', 'Allows a user set how badges (from the Badges unit) are awarded.', 'badges_manage.php, badges_manage_add.php, badges_manage_edit.php','badges_manage.php', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Free Learning' AND gibbonAction.name='Manage Badges'));end
";

//v4.1.01
++$count;
$sql[$count][0] = '4.1.01';
$sql[$count][1] = "";


//v4.1.02
++$count;
$sql[$count][0] = '4.1.02';
$sql[$count][1] = "";
