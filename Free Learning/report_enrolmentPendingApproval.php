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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/report_enrolmentPendingApproval.php', $connection2);

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_enrolmentPendingApproval.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
        print __("You do not have access to this action.") ;
    print "</div>" ;
}
else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Enrolment Pending Approval'));

    //Check for custom field
    $customField = getSettingByScope($connection2, 'Free Learning', 'customField');

    print "<p>" ;
        print __m('This report shows all units for which your mentorship has been requested, and is still pending.') ;
    print "<p>" ;

    //Filter
    $allMentors = (isset($_GET['allMentors']) && $highestAction == 'Enrolment Pending Approval_all') ? $_GET['allMentors'] : '';
    $search = $_GET['search'] ?? '';

    if ($highestAction == 'Enrolment Pending Approval_all') {
        $form = Form::create('search', $gibbon->session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/report_enrolmentPendingApproval.php');

        $row = $form->addRow();
            $row->addLabel('allMentors', __('All Mentors'))->description(__('Include evidence pending for all mentors.'));
            $row->addCheckbox('allMentors')->setValue('on')->checked($allMentors);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'));

        echo $form->getOutput();
    }

    //Table
    $unitGateway = $container->get(UnitGateway::class);
    $unitStudentGateway = $container->get(UnitStudentGateway::class);

    $criteria = $unitStudentGateway->newQueryCriteria()
        ->sortBy('timestampCompletePending')
        ->fromPOST();

    if (!empty($allMentors)) {
        $journey = $unitStudentGateway->queryEnrolmentPending($criteria, $gibbon->session->get('gibbonSchoolYearID'));
    }
    else {
        $journey = $unitStudentGateway->queryEnrolmentPending($criteria, $gibbon->session->get('gibbonSchoolYearID'), $gibbon->session->get('gibbonPersonID'));
    }

    $collaborationKeys = [];

    // Render table
    $table = DataTable::createPaginated('pending', $criteria);

    $table->setTitle(__('Data'));

    $table->modifyRows(function ($journey, $row) {
        $row->addClass('currentPending');
        return $row;
    });

    $table->addColumn('grouping', __m('Mentor'))
        ->sortable(['course', 'class', 'grouping'])
        ->description(__m('Grouping'))
        ->format(function($values) use (&$collaborationKeys) {
            $output = '';
            $output .= formatName('', $values['mentorpreferredName'], $values['mentorsurname'], 'Student', false);

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

    $table->addColumn('unit', __m('Unit'))
        ->description(__m('Learning Area')."/".__m('Course'))
        ->format(function($values) use ($gibbon) {
             $output = "<a href='" . $gibbon->session->get("absoluteURL") . "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=" . $values["freeLearningUnitID"] . "&tab=2&sidebar=true'>" . $values["unit"] . "</a><br/>" ;
             $output .= !empty($values['learningArea']) ? '<div class="text-xxs">'.$values['learningArea'].'</div>' : '';
             $output .= !empty($values['flCourse']) && ($values['learningArea'] != $values['flCourse']) ? '<div class="text-xxs">'.$values['flCourse'].'</div>' : '';
             return $output;
        });

    $table->addColumn('student', __('Student'))
        ->sortable('gibbonPersonID')
        ->format(function($values) use ($guid, $customField) {
            $output = "";
            if ($values['category'] == 'Student') {
                $output .= "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $values["gibbonPersonID"] . "'>" . formatName("", $values["studentpreferredName"], $values["studentsurname"], "Student", true) . "</a>";
            }
            else {
                $output .= formatName("", $values["studentpreferredName"], $values["studentsurname"], "Student", true);
            }
            $output .= "<br/>";
            $fields = json_decode($values['fields'], true);
            if (!empty($fields[$customField])) {
                $value = $fields[$customField];
                if ($value != '') {
                    $output .= "<span style='font-size: 85%; font-style: italic'>".$value.'</span>';
                }
            }
            return $output;
        });

    $table->addColumn('status', __m('Status'))
        ->sortable(false)
        ->format(function($values){
            return __m($values['status']);
        });

    $table->addColumn('timestampJoined', __('When'))->format(Format::using('relativeTime', 'timestampJoined'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('freeLearningUnitID')
        ->addParam('sidebar', true)
        ->addParam('tab', 2)
        ->format(function ($student, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Free Learning/units_browse_details.php');
        });

    echo $table->render($journey);
}
?>
