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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Module\FreeLearning\Domain\MentorshipGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorship_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__m('Manage Mentorship'));

    $search = $_GET['search'] ?? '';

    echo '<p>'.__m('This page allows you to pre-define the school mentors available for particular students. Students with specific mentors will choose from this list rather than the mentors available for a particular unit.').'</p>';
    
    // QUERY
    $mentorshipGateway = $container->get(MentorshipGateway::class);
    $criteria = $mentorshipGateway->newQueryCriteria(true)
        ->searchBy($mentorshipGateway->getSearchableColumns(), $search)
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    // FORM
    $form = Form::create('filter', $gibbon->session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));

    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/Free Learning/mentorship_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Student'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    $mentorship = $mentorshipGateway->queryMentorship($criteria, $gibbon->session->get('gibbonSchoolYearID'));

    $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'].'/modules/Free Learning/mentorship_manageProcessBulk.php');

    $bulkActions = ['Delete' => __('Delete')];
    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSubmit(__('Go'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('units', $criteria)->withData($mentorship);
    $table->setTitle(__('View'));

    $table->addHeaderAction('add', __('Add'))
        ->addParam('search', $search)
        ->setURL('/modules/Free Learning/mentorship_manage_add.php')
        ->displayLabel();

    $table->addMetaData('bulkActions', $col);

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($values) {
            return  Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true);
        });

    $table->addColumn('rollGroup', __m('Roll Group'));

    $table->addColumn('mentors', __m('Mentors'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('search', $criteria->getSearchText(true))
        ->addParam('freeLearningMentorshipID')
        ->addParam('gibbonPersonIDStudent')
        ->format(function ($values, $actions)  {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Free Learning/mentorship_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Free Learning/mentorship_manage_delete.php');
        });

    $table->addCheckboxColumn('gibbonPersonIDStudent');

    echo $form->getOutput();

}
