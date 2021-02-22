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
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

// Module includes
include './modules/Free Learning/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_mentorshipOverview.php")==FALSE) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__m('Mentorship Overview'));

    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/report_mentorshipOverview.php', $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Check for custom field
    $customField = getSettingByScope($connection2, 'Free Learning', 'customField');

    print '<p>';
        print __m('This report shows all units for which your mentorship has been requested.');
    print '<p>';

    // Filter
    $allMentors = !empty($_GET['allMentors']) && $highestAction == 'Mentorship Overview_all' 
        ? $_GET['allMentors'] 
        : '';

    $gibbonPersonID = !empty($_GET['gibbonPersonID']) && $highestAction == 'Mentorship Overview_all' 
        ? $_GET['gibbonPersonID'] 
        : $gibbon->session->get('gibbonPersonID');

    if ($highestAction == 'Mentorship Overview_all') {
        $form = Form::create('search', $gibbon->session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/report_mentorshipOverview.php');

        $row = $form->addRow();
            $row->addLabel('allMentors', __('All Mentors'));
            $row->addCheckbox('allMentors')->setValue('on')->checked($allMentors);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

        echo $form->getOutput();
    }

    // DATA TABLE
    $unitGateway = $container->get(UnitGateway::class);
    $unitStudentGateway = $container->get(UnitStudentGateway::class);

    $criteria = $unitStudentGateway->newQueryCriteria(true)
        ->sortBy(['statusSort', 'timestamp'], 'DESC')
        ->fromPOST();

    $mentorship = $unitStudentGateway->queryMentorship($criteria, $gibbon->session->get('gibbonSchoolYearID'), !empty($allMentors) ? null : $gibbonPersonID);

    $collaborationKeys = [];

    $table = DataTable::createPaginated('mentorship', $criteria);
    $table->setTitle(__('Data'));

    $table->modifyRows(function ($student, $row) {
        if ($student['status'] == 'Current - Pending') $row->addClass('currentPending');
        if ($student['status'] == 'Current') $row->addClass('currentUnit');
        if ($student['status'] == 'Evidence Not Yet Approved') $row->addClass('warning');
        if ($student['status'] == 'Complete - Pending') $row->addClass('pending');
        if ($student['status'] == 'Complete - Approved') $row->addClass('success');
        if ($student['status'] == 'Exempt') $row->addClass('success');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'status:Current - Pending'         => __('Status').': '.__m('Current - Pending'),
        'status:Current'                   => __('Status').': '.__m('Current'),
        'status:Evidence Not Yet Approved' => __('Status').': '.__m('Evidence Not Yet Approved'),
        'status:Complete - Pending'        => __('Status').': '.__m('Complete - Pending'),
        'status:Complete - Approved'       => __('Status').': '.__m('Complete - Approved'),
        'status:Exempt'                    => __('Status').': '.__m('Exempt'),
    ]);

    if ($highestAction == 'Mentorship Overview_all') {
        $table->addColumn('grouping', __m('Mentor'))
            ->description(__m('Enrolment Method'))
            ->sortable(['mentorsurname', 'mentorpreferredName'])
            ->format(function($values) {
                if ($values['enrolmentMethod'] == 'schoolMentor') {
                    $name = Format::name('', $values['mentorpreferredName'], $values['mentorsurname'], 'Student', false);
                    $output = Format::link('./index.php?q=/modules/Free Learning/report_mentorshipOverview.php&gibbonPersonID='.$values['gibbonPersonIDSchoolMentor'], $name);
                } else if ($values['enrolmentMethod'] == 'externalMentor') {
                    $output = $values['nameExternalMentor'];
                }

                $output .= '<br/>'.Format::small(ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $values['enrolmentMethod'])));

                return $output;
            });
    }

    $table->addColumn('unit', __m('Unit'))
        ->description(__m('Learning Area')."/".__m('Course'))
        ->format(function($values) use ($gibbon) {
             $output = "<a href='" . $gibbon->session->get("absoluteURL") . "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=" . $values["freeLearningUnitID"] . "&tab=2&sidebar=true'>" . $values["unit"] . "</a><br/>" ;
             $output .= !empty($values['learningArea']) ? '<div class="text-xxs">'.$values['learningArea'].'</div>' : '';
             $output .= !empty($values['flCourse']) && ($values['learningArea'] != $values['flCourse']) ? '<div class="text-xxs">'.$values['flCourse'].'</div>' : '';
             return $output;
        });

    $table->addColumn('student', __('Student'))
        ->description(__m('Grouping'))
        ->sortable('gibbonPersonID')
        ->format(function($values) use ($customField) {
            $output = "";
            if ($values['category'] == 'Student') {
                $output .= Format::nameLinked($values['gibbonPersonID'], '', $values['studentpreferredName'], $values['studentsurname'], 'Student', true, true);
            } else {
                $output .= Format::name('', $values['studentpreferredName'], $values['studentsurname'], 'Student', true);
            }

            $fields = json_decode($values['fields'], true);
            if (!empty($fields[$customField])) {
                $value = $fields[$customField];
                if ($value != '') {
                    $output .= '<br/>'.Format::small($value);
                }
            }
            
            return $output;
        });

    $table->addColumn('status', __m('Status'))
        ->format(function ($values) use (&$collaborationKeys) {
            $output = $values['status'];
            $grouping = $values['grouping'];

            if ($values['collaborationKey'] != '') {
                // Get the index for the group, otherwise add it to the array
                $group = array_search($values['collaborationKey'], $collaborationKeys);
                if ($group === false) {
                    $collaborationKeys[] = $values['collaborationKey'];
                    $group = count($collaborationKeys);
                } else {
                    $group++;
                }
                $grouping .= " (".__m("Group")." ".$group.")";
            }
            
            $output .= '<br/>' . Format::small($grouping);

            return $output;
        });

    $table->addColumn('timestamp', __('When'))
        ->format(Format::using('relativeTime', 'timestamp'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('freeLearningUnitStudentID')
        ->addParam('freeLearningUnitID')
        ->addParam('confirmationKey')
        ->addParam('mode', 'internal')
        ->addParam('sidebar', true)
        ->format(function ($values, $actions) use ($allMentors, $gibbonPersonID) {

            if ($values['enrolmentMethod'] != 'schoolMentor' && $values['enrolmentMethod'] != 'externalMentor') return;

            if (empty($allMentors) && $values['gibbonPersonIDSchoolMentor'] != $gibbonPersonID) return;

            if ($values['status'] == 'Current - Pending') {
                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Free Learning/units_mentor.php');
            } else {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Free Learning/units_browse_details_approval.php');
            }
            
        });

    echo $table->render($mentorship);
}
