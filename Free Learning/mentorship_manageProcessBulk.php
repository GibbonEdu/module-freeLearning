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

use Gibbon\Module\FreeLearning\Domain\MentorshipGateway;

$_POST['address'] = '/modules/Free Learning/mentorship_manage.php';

require_once '../../gibbon.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Free Learning/mentorship_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorship_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $action = $_REQUEST['action'] ?? '';
    $gibbonPersonIDStudentList = $_REQUEST['gibbonPersonIDStudent'] ?? [];

    $mentorshipGateway = $container->get(MentorshipGateway::class);
    $partialFail = false;
    
    if (empty($action) || empty($gibbonPersonIDStudentList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($action == 'Delete') {
        foreach ($gibbonPersonIDStudentList as $gibbonPersonIDStudent) {
            $deleted = $mentorshipGateway->deleteWhere(['gibbonPersonIDStudent' => $gibbonPersonIDStudent]);
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
