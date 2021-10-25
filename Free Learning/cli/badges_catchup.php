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

require getcwd().'/../../../gibbon.php';
require getcwd().'/../moduleFunctions.php';

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($session->get('i18n')['code']) and $session->get('i18n')['code'] != null) {
    putenv('LC_ALL='.$session->get('i18n')['code']);
    setlocale(LC_ALL, $session->get('i18n')['code']);
    bindtextdomain('gibbon', getcwd().'/../i18n');
    textdomain('gibbon');
}

//Check for CLI, so this cannot be run through browser
$remoteCLIKey = getSettingByScope($connection2, 'System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
    echo __('This script cannot be run from a browser, only via CLI.');
} else {
    try {
        $data = array();
        $sql = "SELECT gibbonPersonID FROM gibbonPerson WHERE status='Full'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {}

    if ($result->rowCount() > 0) {
        while ($row = $result->fetch()) {
            grantBadges($connection2, $guid, $row['gibbonPersonID']);
        }
    }

}
?>
