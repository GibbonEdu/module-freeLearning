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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/report_workPendingApproval.php', $connection2);

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_workPendingApproval.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
        print __($guid, "You do not have access to this action.") ;
    print "</div>" ;
}
else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Work Pending Approval'));

    //Check for custom field
    $customField = getSettingByScope($connection2, 'Free Learning', 'customField');

    print "<p>" ;
        print __($guid, 'This report shows all work that is complete, but pending approval, in all of your classes.', 'Free Learning') ;
    print "<p>" ;

    //Filter
    $allMentors = (isset($_GET['allMentors']) && $highestAction == 'Work Pending Approval_all') ? $_GET['allMentors'] : '';
    $search = $_GET['search'] ?? '';

    if ($highestAction == 'Work Pending Approval_all') {
        $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/report_workPendingApproval.php');

        $row = $form->addRow();
            $row->addLabel('allMentors', __('All Mentors'))->description(__('Include evidence pending for all mentors.'));
            $row->addCheckbox('allMentors')->setValue('on')->checked($allMentors);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'));

        echo $form->getOutput();
    }

    //Table
    $unitStudentGateway = $container->get(UnitStudentGateway::class);

    $criteria = $unitStudentGateway->newQueryCriteria()
        ->sortBy('course')
        ->sortBy('class')
        ->sortBy('unit')
        ->sortBy('studentsurname')
        ->sortBy('studentpreferredName')
        ->fromPOST();

    if (!empty($allMentors)) {
         $journey = $unitStudentGateway->queryEvidencePending($criteria, $gibbon->session->get('gibbonSchoolYearID'));
    }
    else {
        $journey = $unitStudentGateway->queryEvidencePending($criteria, $gibbon->session->get('gibbonSchoolYearID'), $gibbon->session->get('gibbonPersonID'));
    }

    $collaborationKeys = [];

    // Render table
    $table = DataTable::createPaginated('pending', $criteria);

    $table->setTitle(__('Data'));

    $table->modifyRows(function ($journey, $row) {
        $row->addClass('pending');
        return $row;
    });

    $table->addColumn('enrolmentMethod', __m('Enrolment Method'))
        ->format(function($values) {
            return ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $values["enrolmentMethod"])).'<br/>';
        });

    $table->addColumn('classMentor', __m('Class/Mentor'))
        ->description(__m('Grouping'))
        ->format(function($values) use (&$collaborationKeys) {
            $output = '';
            if ($values['enrolmentMethod'] == 'class') {
                if ($values['course'] != '' and $values['class'] != '') {
                    $output .= $values['course'].'.'.$values['class'];
                } else {
                    $output .= '<i>'.__($guid, 'N/A').'</i>';
                }
            }
            else if ($values['enrolmentMethod'] == 'schoolMentor') {
                $output .= formatName('', $values['mentorpreferredName'], $values['mentorsurname'], 'Student', false);
            }
            else if ($values['enrolmentMethod'] == 'externalMentor') {
                $output .= $values['nameExternalMentor'];
            }

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
        ->format(function($values) use ($gibbon) {
             return "<a href='" . $gibbon->session->get("absoluteURL") . "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=" . $values["freeLearningUnitID"] . "&tab=2&sidebar=true'>" . $values["unit"] . "</a>" ;
        });

    $table->addColumn('student', __('Student'))
        ->notSortable()
        ->format(function($values) use ($guid, $customField) {
            $output = "";
            if ($values['category'] == 'Student') {
                $output .= "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $values["gibbonPersonID"] . "'>" . formatName("", $values["studentpreferredName"], $values["studentsurname"], "Student", true) . "</a>";
            }
            else {
                $output .= formatName("", $values["studentpreferredName"], $values["studentsurname"], "Student", true);
            }
            $output .= "<br/>";
            $fields = unserialize($values['fields']);
            if (!empty($fields[$customField])) {
                $value = $fields[$customField];
                if ($value != '') {
                    $output .= "<span style='font-size: 85%; font-style: italic'>".$value.'</span>';
                }
            }
            return $output;
        });

    $table->addColumn('status', __m('Status'));

    echo $table->render($journey);
}
?>
