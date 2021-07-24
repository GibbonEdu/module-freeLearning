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

use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/settings_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/settings_manage') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $difficultyOptions = $_POST['difficultyOptions'] ?? '';
    $publicUnits = $_POST['publicUnits'] ?? '';
    $unitOutlineTemplate = $_POST['unitOutlineTemplate'] ?? '';
    $learningAreaRestriction = $_POST['learningAreaRestriction'] ?? '';
    $customField = $_POST['customField'] ?? '';
    $maxMapSize = $_POST['maxMapSize'] ?? '';
    $enableClassEnrolment = $_POST['enableClassEnrolment'] ?? '';
    $enableSchoolMentorEnrolment = $_POST['enableSchoolMentorEnrolment'] ?? '';
    $enableExternalMentorEnrolment = $_POST['enableExternalMentorEnrolment'] ?? '';
    $autoAcceptMentorGroups = $_POST['autoAcceptMentorGroups'] ?? '';
    $collaborativeAssessment = $_POST['collaborativeAssessment'] ?? '';
    $certificatesAvailable = $_POST['certificatesAvailable'] ?? '';
    $certificateTemplate = $_POST['certificateTemplate'] ?? '';
    $certificateOrientation = $_POST['certificateOrientation'] ?? '';
    $disableParentEvidence = $_POST['disableParentEvidence'] ?? '';
    $disableLearningAreas = $_POST['disableLearningAreas'] ?? '';
    $enableManualBadges = $_POST['enableManualBadges'] ?? '';

    $settingGateway = $container->get(SettingGateway::class);

    //Validate Inputs
    if ($difficultyOptions == '' or $publicUnits == '' or $learningAreaRestriction == ''or $enableClassEnrolment == '' or $enableSchoolMentorEnrolment == '' or $enableExternalMentorEnrolment == '' or $collaborativeAssessment == '' or $certificatesAvailable == '' or $disableParentEvidence == '' or $disableLearningAreas == '' or $enableManualBadges == '') {
        //Fail 3
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } else {
        //Write to database
        $partialFail = false;

        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'difficultyOptions', $difficultyOptions);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'publicUnits', $publicUnits);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'unitOutlineTemplate', $unitOutlineTemplate);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'learningAreaRestriction', $learningAreaRestriction);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'customField', $customField);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'maxMapSize', $maxMapSize);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableClassEnrolment', $enableClassEnrolment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableSchoolMentorEnrolment', $enableSchoolMentorEnrolment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableExternalMentorEnrolment', $enableExternalMentorEnrolment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'autoAcceptMentorGroups', $autoAcceptMentorGroups);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'collaborativeAssessment', $collaborativeAssessment);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'certificatesAvailable', $certificatesAvailable);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'certificateTemplate', $certificateTemplate);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'certificateOrientation', $certificateOrientation);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableParentEvidence', $disableParentEvidence);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'disableLearningAreas', $disableLearningAreas);
        $partialFail = !$settingGateway->updateSettingByScope('Free Learning', 'enableManualBadges', $enableManualBadges);

        $URL .= $partialFail
            ? '&return=error2'
            : '&return=success0';
        header("Location: {$URL}");
    }
}
