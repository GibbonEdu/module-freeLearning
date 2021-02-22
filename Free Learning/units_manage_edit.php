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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $freeLearningUnitID = $_GET['freeLearningUnitID'];
        $canManage = false;
        if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and getHighestGroupedAction($guid, '/modules/Free Learning/units_browse.php', $connection2) == 'Browse Units_all') {
            $canManage = true;
        }
        $showInactive = 'N';
        if ($canManage and !empty($_GET['showInactive'])) {
            $showInactive = $_GET['showInactive'];
        }

        $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';
        $view = $_GET['view'] ?? '';

        //Proceed!
        $urlParams = compact('view', 'name', 'difficulty', 'gibbonDepartmentID', 'showInactive', 'freeLearningUnitID');

        $page->breadcrumbs
             ->add(__m('Manage Units'), 'units_manage.php', $urlParams)
             ->add(__m('Edit Unit'));

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $addReturn = $_GET['addReturn'] ?? '';
        $addReturnMessage = '';
        $class = 'error';
        if (!($addReturn == '')) {
            if ($addReturn == 'success0') {
                $addReturnMessage = __($guid, 'Your Smart Unit was successfully created: you can now edit it using the form below.', 'Free Learning');
                $class = 'success';
            }
            echo "<div class='$class'>";
            echo $addReturnMessage;
            echo '</div>';
        }

        try {
            if ($highestAction == 'Manage Units_all') {
                $data = array('freeLearningUnitID' => $freeLearningUnitID);
                $sql = 'SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
            } elseif ($highestAction == 'Manage Units_learningAreas') {
                $data = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'freeLearningUnitID' => $freeLearningUnitID);
                $sql = "SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND freeLearningUnitID=:freeLearningUnitID ORDER BY difficulty, name";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            // Check edit lock
            if ($highestAction != "Manage Units_all" && $values['editLock'] == "Y") {
                echo "<div class='error'>";
                echo __($guid, 'The specified record cannot be found.');
                echo '</div>';
                return;
            }

            if ($gibbonDepartmentID != '' or $difficulty != '' or $name != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$gibbon->session->get('absoluteURL')."/index.php?q=/modules/Free Learning/units_manage.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php')) {
                if ($values['active'] == 'N') $showInactive = 'Y';

                echo "<div class='linkTop'>";
                echo "<a href='".$gibbon->session->get('absoluteURL')."/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&view=$view'>".__($guid, 'View')."<img style='margin: 0 0 -4px 3px' title='".__($guid, 'View')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/plus.png'/></a>";
                echo '</div>';
            }

            $form = Form::create('action', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module')."/units_manage_editProcess.php?freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&view=$view");
            $form->setFactory(FreeLearningFormFactory::create($pdo));

            $form->addHiddenValue('address', $gibbon->session->get('address'));


            // UNIT BASICS
            $form->addRow()->addHeading(__m('Unit Basics'));

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
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
                    $row->addCheckbox('gibbonDepartmentIDList')->fromResults($results)->loadFromCSV($values);
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
                $row->addFileUpload('file')
                    ->accepts('.jpg,.jpeg,.gif,.png')
                    ->setAttachment('logo', null, $values['logo']);

            $row = $form->addRow();
            $row->addLabel('majorEdit', __('Major Edit'))->description(__m('If checked, you will be added as an author.'));
                $row->addCheckbox('majorEdit')->setValue('Y')->description(__('Yes'));


            // ACCESS
            $form->addRow()->addHeading(__m('Access'))->append(__m('Users with permission to manage units can override avaiability preferences.'));

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('editLock', __('Edit Lock'))->description(__m('Restricts editing to users with Manage Units_all'));
                $row->addYesNo('editLock')->required();

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
                $row->addSelect('freeLearningUnitIDPrerequisiteList')->fromQuery($pdo, $sql, array())->selectMultiple()->loadFromCSV($values);

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
                $row->addCheckbox('grouping')->fromArray($groups)->loadFromCSV($values);


            // MENTORSHIP
            $enableSchoolMentorEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableSchoolMentorEnrolment');
            if ($enableSchoolMentorEnrolment == 'Y') {
                $form->addRow()->addHeading(__m('Mentorship'))->append(__m('Determines who can act as a school mentor for this unit. These mentorship settings are overridden when a student is part of a mentor group.'));

                $row = $form->addRow();
                    $row->addLabel('schoolMentorCompletors', __m('Completors'))->description(__m('Allow students who have completed a unit to become a mentor?'));
                    $row->addYesNo('schoolMentorCompletors')->required()->selected('N');

                $row = $form->addRow();
                    $row->addLabel('schoolMentorCustom', __m('Specific Users'))->description(__m('Choose specific users who can act as mentors.'));
                    $row->addSelectUsers('schoolMentorCustom')->selectMultiple()->loadFromCSV($values);

                $row = $form->addRow();
                    $row->addLabel('schoolMentorCustomRole', __m('Specific Role'))->description(__m('Choose a specific user role, members of whom can act as mentors.'));
                    $row->addSelectRole('schoolMentorCustomRole');
            }


            // OUTCOMES
            $form->addRow()->addHeading(__('Outcomes'))->append(__('Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.'));
            $allowOutcomeEditing = getSettingByScope($connection2, 'Planner', 'allowOutcomeEditing');
            $row = $form->addRow();
                $customBlocks = $row->addFreeLearningOutcomeBlocks('outcome', $gibbon->session, implode(",", $values['gibbonDepartmentIDList']), $allowOutcomeEditing);

            $dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
            $sqlBlocks = "SELECT freeLearningUnitOutcome.*, scope, name, category FROM freeLearningUnitOutcome JOIN gibbonOutcome ON (freeLearningUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y' ORDER BY sequenceNumber";
            $resultBlocks = $pdo->select($sqlBlocks, $dataBlocks);

            while ($rowBlocks = $resultBlocks->fetch()) {
                $outcome = array(
                    'outcometitle' => $rowBlocks['name'],
                    'outcomegibbonOutcomeID' => $rowBlocks['gibbonOutcomeID'],
                    'outcomecategory' => $rowBlocks['category'],
                    'outcomecontents' => $rowBlocks['content']
                );
                $customBlocks->addBlock($rowBlocks['gibbonOutcomeID'], $outcome);
            }


            // UNIT OUTLINE
            $form->addRow()->addHeading(__m('Unit Outline'))->append(__m('The contents of this field are viewable to all users, SO AVOID CONFIDENTIAL OR SENSITIVE DATA!'));

            $unitOutline = getSettingByScope($connection2, 'Free Learning', 'unitOutlineTemplate');
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('outline', __('Unit Outline'));
                $column->addEditor('outline', $guid)->setRows(30)->showMedia()->setValue($unitOutline);


            // SMART BLOCKS
            $form->addRow()->addHeading(__('Smart Blocks'))->append(__('Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller units which can be deployed to lesson plans. As well as predefined fields to fill, Smart Units provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.'));
            $blockCreator = $form->getFactory()
                ->createButton('addNew')
                ->setValue(__('Click to create a new block'))
                ->addClass('addBlock');

            $row = $form->addRow();
                $customBlocks = $row->addFreeLearningSmartBlocks('smart', $gibbon->session, $guid)
                    ->addToolInput($blockCreator);

            $dataBlocks = array('freeLearningUnitID' => $freeLearningUnitID);
            $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';
            $resultBlocks = $pdo->select($sqlBlocks, $dataBlocks);

            while ($rowBlocks = $resultBlocks->fetch()) {
                $smart = array(
                    'title' => $rowBlocks['title'],
                    'type' => $rowBlocks['type'],
                    'length' => $rowBlocks['length'],
                    'contents' => $rowBlocks['contents'],
                    'teachersNotes' => $rowBlocks['teachersNotes'],
                    'freeLearningUnitBlockID' => $rowBlocks['freeLearningUnitBlockID']
                );
                $customBlocks->addBlock($rowBlocks['freeLearningUnitBlockID'], $smart);
            }


            $form->loadAllValuesFrom($values);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
<script>
    // Allows script tags to be used inside Free Learning smart blocks
    $(document).on('gibbon-setup', function(){
        Gibbon.config.tinymce.extended_valid_elements = 'script[src|async|defer|type|charset]';
    });
</script>
