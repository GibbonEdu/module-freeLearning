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

use Gibbon\FileUploader;
use Gibbon\Module\FreeLearning\UnitImporter;

require_once '../../gibbon.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $gibbonDepartmentIDList = isset($_POST['gibbonDepartmentIDList']) ? implode(',', $_POST['gibbonDepartmentIDList']) : '';
    $course = $_POST['course'] ?? '';

    if (empty($_FILES['file'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $fileUploader = new FileUploader($pdo, $gibbon->session);
    $zipFile = $fileUploader->uploadFromPost($_FILES['file']);

    if (empty($zipFile)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $importer = $container->get(UnitImporter::class);
    $importer->setDefaults($gibbonDepartmentIDList, $course);
    $success = $importer->importFromFile($gibbon->session->get('absolutePath').'/'.$zipFile);


    $URL .= !$success
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");

}
