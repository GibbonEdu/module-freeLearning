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

require_once '../../gibbon.php';

$gibbonPersonIDStudent = $_POST['gibbonPersonIDStudent'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Free Learning/mentorship_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorship_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonPersonIDStudent)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $mentorshipGateway = $container->get(MentorshipGateway::class);
    $result = $mentorshipGateway->selectBy(['gibbonPersonIDStudent' => $gibbonPersonIDStudent]);

    if ($result->rowCount() == 0) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $mentorshipGateway->deleteWhere(['gibbonPersonIDStudent' => $gibbonPersonIDStudent]);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
