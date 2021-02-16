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
use Gibbon\Module\FreeLearning\Domain\MentorshipGateway;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorship_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Mentorship'), 'mentorship_manage.php')
        ->add(__m('Edit Mentorship'));

    if (empty($gibbonPersonIDStudent)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $mentorshipGateway = $container->get(MentorshipGateway::class);

    // Get a list of mentor names
    $mentors = $mentorshipGateway->selectMentorsByStudent($gibbonPersonIDStudent)->fetchAll();
    $mentors = Format::nameListArray($mentors, 'Staff', true, true);

    if (empty($mentors)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Get a list of staff and remove and selected users
    $staff = $container->get(UserGateway::class)->selectUserNamesByStatus('Full', 'Staff')->fetchAll();
    $staff = Format::nameListArray($staff, 'Staff', true, true);
    $staff = array_diff_key($staff, $mentors);


    $form = Form::create('mentorship', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/mentorship_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDStudent', __('Student'));
        $row->addSelectStudent('gibbonPersonIDStudent', $gibbon->session->get('gibbonSchoolYearID'))
            ->readonly()
            ->selected($gibbonPersonIDStudent);

    $col = $form->addRow()->addColumn();
        $col->addLabel('mentors', __('Mentors'));
        $select = $col->addMultiSelect('mentors')->isRequired();
        $select->source()->fromArray($staff);
        $select->destination()->fromArray($mentors);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
