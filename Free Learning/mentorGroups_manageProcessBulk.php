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

use Gibbon\Module\FreeLearning\Domain\MentorGroupGateway;

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/mentorGroups_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorGroups_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $action = $_REQUEST['action'] ?? '';
    $freeLearningMentorGroupIDList = $_REQUEST['freeLearningMentorGroupID'] ?? [];

    $mentorGroupGateway = $container->get(MentorGroupGateway::class);
    $partialFail = false;
    
    if (empty($action) || empty($freeLearningMentorGroupIDList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($action == 'Delete') {
        foreach ($freeLearningMentorGroupIDList as $freeLearningMentorGroupID) {
            $deleted = $mentorGroupGateway->delete($freeLearningMentorGroupID);
            $partialFail &= !$deleted;
        }
    } else {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
