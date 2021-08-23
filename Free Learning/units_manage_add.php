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
use Gibbon\Module\FreeLearning\Forms\FreeLearningFormFactory;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';
        $gibbonYearGroupIDMinimum = $_GET['gibbonYearGroupIDMinimum'] ?? '';
        $view = $_GET['view'] ?? '';

        //Proceed!
        $urlParams = compact('gibbonDepartmentID', 'difficulty', 'name', 'gibbonYearGroupIDMinimum', 'view');

        $page->breadcrumbs
             ->add(__m('Manage Units'), 'units_manage.php', $urlParams)
             ->add(__m('Add Unit'));

        $returns = array();
        $editLink = '';
        if (isset($_GET['editID'])) {
            $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_manage_edit.php&freeLearningUnitID='.$_GET['editID'].'&'.http_build_query($urlParams);
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, $returns);
        }

        if ($gibbonDepartmentID != '' or $difficulty != '' or $name != '' or $gibbonYearGroupIDMinimum != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Free Learning/units_manage.php&".http_build_query($urlParams)."'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }

        $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/units_manage_addProcess.php?".http_build_query($urlParams));
        $form->setFactory(FreeLearningFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));


        // UNIT BASICS
        $form->addRow()->addHeading(__m('Unit Basics'));

        $row = $form->addRow();
            $row->addLabel('name', __m('Unit Name'));
            $row->addTextField('name')->maxLength(40)->required();

        $difficultyOptions = getSettingByScope($connection2, 'Free Learning', 'difficultyOptions');
        $difficultyOptions = ($difficultyOptions != false) ? explode(',', $difficultyOptions) : [];
        $difficulties = [];
        foreach ($difficultyOptions as $difficultyOption) {
            $difficulties[$difficultyOption] = __m($difficultyOption);
        }
        $row = $form->addRow();
            $row->addLabel('difficulty', __m('Difficulty'))->description(__m("How hard is this unit?"));
            $row->addSelect('difficulty')->fromArray($difficulties)->required()->placeholder();

        $row = $form->addRow();
            $row->addLabel('blurb', __('Blurb'));
            $row->addTextArea('blurb')->required();

        $row = $form->addRow();
            $row->addLabel('studentReflectionText', __m('Student Reflection Prompt'));
            $row->addTextArea('studentReflectionText')->setRows(3);

        $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $results = $pdo->executeQuery(array(), $sql);
        $row = $form->addRow();
        $row->addLabel('gibbonDepartmentIDList', __('Learning Areas'));
            if ($results->rowCount() == 0) {
                $row->addContent(__('No Learning Areas available.'))->wrap('<i>', '</i>');
            } else {
                $row->addCheckbox('gibbonDepartmentIDList')->fromResults($results);
            }

        $sql = "SELECT DISTINCT course FROM freeLearningUnit WHERE active='Y'  ORDER BY course";
        $result = $pdo->executeQuery(array(), $sql);
        $options = ($result && $result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN) : array();
        $row = $form->addRow();
            $row->addLabel('course', __('Course'))->description(__m('Add this unit into an ad hoc course?'));
            $row->addTextField('course')->maxLength(50)->autocomplete($options);

        $licences = array(
            "Copyright" => __("Copyright"),
            "Creative Commons BY" => __("Creative Commons BY"),
            "Creative Commons BY-SA" => __("Creative Commons BY-SA"),
            "Creative Commons BY-SA-NC" => __("Creative Commons BY-SA-NC"),
            "Public Domain" => __("Public Domain")
        );
        $row = $form->addRow()->addClass('advanced');
            $row->addLabel('license', __('License'))->description(__('Under what conditions can this work be reused?'));
            $row->addSelect('license')->fromArray($licences)->placeholder();

        $row = $form->addRow();
            $row->addLabel('file', __m('Logo'))->description(__m('125px x 125px'));
            $row->addFileUpload('file')->accepts('.jpg,.jpeg,.gif,.png');


        // ACCESS
        $form->addRow()->addHeading(__m('Access'))->append(__m('Users with permission to manage units can override avaiability preferences.'));

        $row = $form->addRow();
            $row->addLabel('active', __('Active'));
            $row->addYesNo('active')->required();

        $row = $form->addRow();
            $row->addLabel('editLock', __('Edit Lock'))->description(__m('Restricts editing to users with Manage Units_all'));
            $row->addYesNo('editLock')->selected('N')->required();

        $row = $form->addRow();
            $row->addLabel('availableStudents', __m('Available To Students'))->description(__m('Should students be able to browse and enrol?'));
            $row->addYesNo('availableStudents')->required();

        $row = $form->addRow();
            $row->addLabel('availableStaff', __m('Available To Staff'))->description(__m('Should staff be able to browse and enrol?'));
            $row->addYesNo('availableStaff')->required();

        $row = $form->addRow();
            $row->addLabel('availableParents', __m('Available To Parents'))->description(__m('Should parents be able to browse and enrol?'));
            $row->addYesNo('availableParents')->required();

        $row = $form->addRow();
            $row->addLabel('availableOther', __m('Available To Others'))->description(__m('Should other users be able to browse and enrol?'));
            $row->addYesNo('availableOther')->required();

        $makeUnitsPublic = getSettingByScope($connection2, 'Free Learning', 'publicUnits');
        if ($makeUnitsPublic == 'Y') {
            $row = $form->addRow();
                $row->addLabel('sharedPublic', __m('Shared Publicly'))->description(__m('Share this unit via the public listing of units? Useful for building MOOCS.'));
                $row->addYesNo('sharedPublic')->required()->selected('N');
        }


        // CONSTRAINTS
        $form->addRow()->addHeading(__m('Constraints'));

        $sql = 'SELECT freeLearningUnitID as value, CONCAT(name, \' (\', difficulty, \')\') AS name FROM freeLearningUnit ORDER BY name';
        $row = $form->addRow();
            $row->addLabel('freeLearningUnitIDPrerequisiteList', __m('Prerequisite Units'));
            $row->addSelect('freeLearningUnitIDPrerequisiteList')->fromQuery($pdo, $sql, array())->selectMultiple();

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupIDMinimum', __m('Minimum Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupIDMinimum')->placeholder();

        $groups = [
            "Individual" => __m("Individual"),
            "Pairs" => __m("Pairs"),
            "Threes" => __m("Threes"),
            "Fours" => __m("Fours"),
            "Fives" => __m("Fives"),
        ];
        $row = $form->addRow();
        $row->addLabel('grouping', __('Grouping'))->description(__m('How should students work during this unit?'));
            $row->addCheckbox('grouping')->fromArray($groups);


        // MENTORSHIP
        $enableSchoolMentorEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableSchoolMentorEnrolment');
        if ($enableSchoolMentorEnrolment == 'Y') {
            $form->addRow()->addHeading(__m('Mentorship'))->append(__m('Determines who can act as a school mentor for this unit. These mentorship settings are overridden when a student is part of a mentor group.'));

            $row = $form->addRow();
                $row->addLabel('schoolMentorCompletors', __m('Completors'))->description(__m('Allow students who have completed a unit to become a mentor?'));
                $row->addYesNo('schoolMentorCompletors')->required()->selected('N');

            $row = $form->addRow();
                $row->addLabel('schoolMentorCustom', __m('Specific Users'))->description(__m('Choose specific users who can act as mentors.'));
                $row->addSelectUsers('schoolMentorCustom')->selectMultiple();

            $row = $form->addRow();
                $row->addLabel('schoolMentorCustomRole', __m('Specific Role'))->description(__m('Choose a specific user role, members of whom can act as mentors.'));
                $row->addSelectRole('schoolMentorCustomRole');
        }


        // OUTCOMES
        $disableOutcomes = getSettingByScope($connection2, 'Free Learning', 'disableOutcomes');
        if ($disableOutcomes != 'Y') {
            $form->addRow()->addHeading(__m('Outcomes'));
            $form->addRow()->addAlert(__m('Outcomes can only be set after the new unit has been saved once. Click submit below, and when you land on the edit page, you will be able to manage outcomes.'), "message");
        }

        // UNIT OUTLINE
        $form->addRow()->addHeading(__m('Unit Outline'))->append(__m('The contents of this field are viewable to all users, SO AVOID CONFIDENTIAL OR SENSITIVE DATA!'));

        $unitOutline = getSettingByScope($connection2, 'Free Learning', 'unitOutlineTemplate');
        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('outline', __('Unit Outline'));
            $column->addEditor('outline', $guid)->setRows(30)->showMedia()->setValue($unitOutline);


        // SMART BLOCKS
        $form->addRow()->addHeading(__m('Smart Blocks'))->append(__m('Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller chunks. As well as predefined fields to fill, Smart Blocks provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.'));

        $blockCreator = $form->getFactory()
            ->createButton('addNewBlock')
            ->setValue(__('Click to create a new block'))
            ->addClass('advanced addBlock');

        $row = $form->addRow()->addClass('advanced');
            $customBlocks = $row->addFreeLearningSmartBlocks('smart', $session, $guid)
                ->addToolInput($blockCreator);

        for ($i=0 ; $i<5 ; $i++) {
            $customBlocks->addBlock("block$i");
        }

        $form->addHiddenValue('blockCount', "5");

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
?>
