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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorship_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Mentorship'), 'mentorship_manage.php')
        ->add(__m('Add Mentorship'));

    if (!empty($_GET['editID'])) {
        $page->return->setEditLink($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/mentorship_manage_edit.php&gibbonPersonIDStudent='.$_GET['editID']);
    }

    $staff = $container->get(UserGateway::class)->selectUserNamesByStatus('Full', 'Staff')->fetchAll();
    $staff = Format::nameListArray($staff, 'Staff', true, true);
    
    $form = Form::create('mentorship', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/mentorship_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDStudentList', __('Student'));
        $row->addSelectStudent('gibbonPersonIDStudentList', $gibbon->session->get('gibbonSchoolYearID'), ['byName' => true, 'byRoll' => true])
            ->selectMultiple()
            ->placeholder()
            ->required();

    $col = $form->addRow()->addColumn();
        $col->addLabel('mentors', __('Mentors'));
        $select = $col->addMultiSelect('mentors')->isRequired();
        $select->source()->fromArray($staff);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
