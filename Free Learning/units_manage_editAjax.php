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

use Gibbon\Module\FreeLearning\Domain\UnitBlockGateway;

require_once '../../gibbon.php';

if (empty($session->get('gibbonPersonID')) || empty($session->get('gibbonRoleIDPrimary'))
    || !isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_edit.php')) {
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $freeLearningUnitBlockID = $_POST['freeLearningUnitBlockID'] ?? null;

    if (empty($freeLearningUnitBlockID)) {
        echo -1;
        die();
    }

    $updated = $container->get(UnitBlockGateway::class)->update($freeLearningUnitBlockID, [
        'title'        => $_POST['title'] ?? '',
        'type'         => $_POST['type'] ?? '',
        'length'       => $_POST['length'] ?? '',
        'contents'     => $_POST['contents'] ?? '',
        'teachersNotes' => $_POST['teachersNotes'] ?? '',
    ]);

    echo $updated;
}
