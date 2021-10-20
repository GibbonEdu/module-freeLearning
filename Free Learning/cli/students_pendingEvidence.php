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

use Gibbon\Services\Format;
use Gibbon\Comms\NotificationSender;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;


$_POST['address'] = '/modules/Free Learning/report_unitHistory_my.php';

require __DIR__.'/../../../gibbon.php';

// Setup some of the globals
getSystemSettings($guid, $connection2);
setCurrentSchoolYear($guid, $connection2);
Format::setupFromSession($container->get('session'));

if (!isCommandLineInterface()) {
    print __('This script cannot be run from a browser, only via CLI.');
    return;
}

if ($session->get('organisationEmail') == '') {
    echo __('This script cannot be run, as no school email address has been set.');
    return;
}

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);


$gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
$notificationSender = $container->get(NotificationSender::class);

// Get the list of students with current enrolments older than 31 days
$students = $container->get(UnitStudentGateway::class)->selectEvidencePending($gibbonSchoolYearID)->fetchGrouped();

// Loop over each mentor and add a notification to send
foreach ($students as $gibbonPersonID => $units) {
    $actionText = __m('You have one or more current units that have not had any activity in the past month:<br/>{units} Please visit your My Unit History page to view your current units.', ['units' => Format::list(array_column($units, 'name'))]);
    $actionLink = '/index.php?q=/modules/Free Learning/report_unitHistory_my.php';
    $notificationSender->addNotification($gibbonPersonID, $actionText, 'Free Learning', $actionLink);
}

$sendReport = $notificationSender->sendNotifications();

// Notify admin
$actionText = __m('A Free Learning CLI script ({name}) has run.', ['name' => 'Pending Student Evidence']).'<br/><br/>';
$actionText .= __('Date').': '.Format::date(date('Y-m-d')).'<br/>';
$actionText .= __('Total Count').': '.($sendReport['emailSent'] + $sendReport['emailFailed']).'<br/>';
$actionText .= __('Send Succeed Count').': '.$sendReport['emailSent'].'<br/>';
$actionText .= __('Send Fail Count').': '.$sendReport['emailFailed'];

$actionLink = '/index.php?q=/modules/Free Learning/report_mentorshipOverview.php';

$notificationSender = $container->get(NotificationSender::class);
$notificationSender->addNotification($session->get('organisationAdministrator'), $actionText, 'Free Learning', $actionLink);
$notificationSender->sendNotifications();

// Output the result to terminal
echo sprintf('Sent %1$s emails: %2$s emails sent, %3$s emails failed.', $sendReport['emailSent'] + $sendReport['emailFailed'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
