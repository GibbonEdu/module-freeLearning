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

use Gibbon\Module\FreeLearning\Domain\UnitBlockGateway;

$_POST['address'] = '/modules/Free Learning/units_manage_add.php';

require_once '../../gibbon.php';

if (empty($gibbon->session->get('gibbonPersonID')) || empty($gibbon->session->get('gibbonRoleIDPrimary'))
    || !isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_add.php')) {
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $freeLearningUnitBlockID = $_POST['freeLearningUnitBlockID'] ?? null;

    if ($freeLearningUnitBlockID == null) {
        echo -1;
        die();
    }

    $unitBlockGateway = $container->get(UnitBlockGateway::class);

    $unit = $unitBlockGateway->getByID($freeLearningUnitBlockID);
    $unit['freeLearningUnitBlockID'] = null;

    echo json_encode($unit);
}

?>
