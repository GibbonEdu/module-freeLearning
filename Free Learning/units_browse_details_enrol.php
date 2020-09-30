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
use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Check ability to enrol
    $proceed = false;
    $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];

    if ($highestAction == 'Browse Units_all') {
        $proceed = true;
    } elseif ($highestAction == 'Browse Units_prerequisites') {
        if ($values['freeLearningUnitIDPrerequisiteList'] == null or $values['freeLearningUnitIDPrerequisiteList'] == '') {
            $proceed = true;
        } else {
            $prerequisitesActive = prerequisitesRemoveInactive($connection2, $values['freeLearningUnitIDPrerequisiteList']);
            $prerequisitesMet = prerequisitesMet($connection2, $gibbonPersonID, $prerequisitesActive, true);
            if ($prerequisitesMet) {
                $proceed = true;
            }
        }
    }

    if ($proceed == false && $values['status'] != 'Exempt') {
        echo Format::alert(__m('You cannot enrol, as you have not fully met the prerequisites for this unit.'), 'warning');
        return;
    }

    // Get enrolment settings
    $enableSchoolMentorEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableSchoolMentorEnrolment');
    $enableExternalMentorEnrolment = getSettingByScope($connection2, 'Free Learning', 'enableExternalMentorEnrolment');
    $enableClassEnrolment = $roleCategory == 'Student'
        ? getSettingByScope($connection2, 'Free Learning', 'enableClassEnrolment')
        : 'N';

    // Check enrolment status
    $unitStudentGateway = $container->get(UnitStudentGateway::class);
    $rowEnrol = $unitStudentGateway->getUnitStudentDetailsByID($freeLearningUnitID, $gibbonPersonID);

    if (empty($rowEnrol)) { 

        // ENROL NOW
        $form = Form::create('enrol', $gibbon->session->get('absoluteURL').'/modules/Free Learning/units_browse_details_enrolProcess.php?'.http_build_query($urlParams));
        $form->setTitle(__m('Enrol Now'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        
        $form->addHiddenValue('address', $gibbon->session->get('address'));
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);

        $enrolmentMethodSelected = '';
        $enrolmentMethods = [];

        if ($enableExternalMentorEnrolment == 'Y') {
            $enrolmentMethodSelected = 'class';
            $enrolmentMethods['externalMentor'] = __m('External Mentor');
        }
        if ($enableSchoolMentorEnrolment == 'Y') {
            $enrolmentMethodSelected = 'class';
            $enrolmentMethods['schoolMentor'] = __m('School Mentor');
        }
        if ($enableClassEnrolment == 'Y') {
            $enrolmentMethodSelected = 'class';
            $enrolmentMethods['class'] = __m('Timetable Class');
        }

        $row = $form->addRow();
            $row->addLabel('enrolmentMethod', __m('Enrolment Method'));
            $row->addRadio('enrolmentMethod')->fromArray(array_reverse($enrolmentMethods))->required();

        // CLASS ENROLMENT
        if ($enableClassEnrolment == 'Y') {
            $form->toggleVisibilityByClass('classEnrolment')->onRadio('enrolmentMethod')->when('class');

            $row = $form->addRow()->addClass('classEnrolment');
                $row->addLabel('gibbonCourseClassID', __m('Class'))->description(__m('Which class are you enroling for?'));
                $row->addSelectClass('gibbonCourseClassID', $gibbonSchoolYearID, $gibbonPersonID, [
                    'allClasses' => false, 
                    'courseFilter' => 'Free Learning',
                    'departments' => $values['gibbonDepartmentIDList'],
                ])->required();
        }

        // SCHOOL MENTOR
        if ($enableSchoolMentorEnrolment == 'Y') {
            $form->toggleVisibilityByClass('schoolMentorEnrolment')->onRadio('enrolmentMethod')->when('schoolMentor');

            $mentors = $unitStudentGateway->selectUnitMentors($freeLearningUnitID, $gibbonPersonID)->fetchAll();
            $mentors = Format::nameListArray($mentors, 'Staff', true, true);

            $row = $form->addRow()->addClass('schoolMentorEnrolment');
                $row->addLabel('gibbonPersonIDSchoolMentor', __m('School Mentor'));
                $row->addSelectPerson('gibbonPersonIDSchoolMentor')->fromArray($mentors)->required()->placeholder();
        }

        // EXTERNAL MENTOR
        if ($enableExternalMentorEnrolment == 'Y') {
            $form->toggleVisibilityByClass('externalMentorEnrolment')->onRadio('enrolmentMethod')->when('externalMentor');

            $row = $form->addRow()->addClass('externalMentorEnrolment');
                $row->addLabel('nameExternalMentor', __m('External Mentor Name'));
                $row->addTextField('nameExternalMentor')->required();

            $row = $form->addRow()->addClass('externalMentorEnrolment');
                $row->addLabel('emailExternalMentor', __m('External Mentor Email'));
                $row->addEmail('emailExternalMentor')->required();
        }

        // GROUPING 
        $groupings = [];
        $extraSlots = 0;
        if (strpos($values['grouping'], 'Individual') !== false) {
            $groupings['Individual'] = __m('Individual');
        }
        if (strpos($values['grouping'], 'Pairs') !== false) {
            $form->toggleVisibilityByClass('group1')->onSelect('grouping')->when(['Pairs','Threes','Fours','Fives']);
            $groupings['Pairs'] = __m('Pair');
            $extraSlots = 1;
        }
        if (strpos($values['grouping'], 'Threes') !== false) {
            $form->toggleVisibilityByClass('group2')->onSelect('grouping')->when(['Threes','Fours','Fives']);
            $groupings['Threes'] = __m('Three');
            $extraSlots = 2;
        }
        if (strpos($values['grouping'], 'Fours') !== false) {
            $form->toggleVisibilityByClass('group3')->onSelect('grouping')->when(['Fours','Fives']);
            $groupings['Fours'] = __m('Four');
            $extraSlots = 3;
        }
        if (strpos($values['grouping'], 'Fives') !== false) {
            $form->toggleVisibilityByClass('group4')->onSelect('grouping')->when(['Fives']);
            $groupings['Fives'] = __m('Five');
            $extraSlots = 4;
        }

        $row = $form->addRow();
            $row->addLabel('grouping', __m('Grouping'))->description(__m('How do you want to study this unit?'));
            $row->addSelect('grouping')->fromArray($groupings)->required()->placeholder();
        
        // COLLABORATORS
        if ($extraSlots > 0) {
            $prerequisitesActive = prerequisitesRemoveInactive($connection2, $roleCategory == 'Student' ? $values['freeLearningUnitIDPrerequisiteList'] : '');
            $prerequisiteCount = !empty($prerequisitesActive) ? count(explode(',', $prerequisitesActive)) : 0;

            $collaborators = $unitStudentGateway->selectPotentialCollaborators($gibbonSchoolYearID, $gibbonPersonID, $roleCategory, $prerequisiteCount, $values)->fetchAll();
            $collaborators = Format::nameListArray($collaborators, 'Student', true);

            for ($i = 1; $i <= $extraSlots; ++$i) {
                $row = $form->addRow()->addClass('group'.$i);
                    $row->addLabel('collaborators', __m('Collaborator {number}', ['number' => $i]));
                    $row->addSelect('collaborators[]')
                        ->setID('collaborator'.$i)
                        ->fromArray($collaborators)
                        ->required()
                        ->placeholder();
            }
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit(__m('Enrol Now'));

        echo $form->getOutput();

    } elseif ($rowEnrol['status'] == 'Current' or $rowEnrol['status'] == 'Current - Pending' or $rowEnrol['status'] == 'Evidence Not Yet Approved') { 
        // Currently enroled, allow to set status to complete and submit feedback...or previously submitted evidence not accepted

        $form = Form::create('enrolComment', $gibbon->session->get('absoluteURL').'/modules/Free Learning/units_browse_details_commentProcess.php?'.http_build_query($urlParams));
        $form->setClass('blank');
        $form->setTitle(__m('Currently Enroled'));

        $form->addHiddenValue('address', $gibbon->session->get('address'));
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
        $form->addHiddenValue('freeLearningUnitStudentID', $rowEnrol['freeLearningUnitStudentID']);

        if ($rowEnrol['status'] == 'Current - Pending') {
            $form->setDescription(sprintf(__m('You are currently enroled in %1$s, but your chosen mentor has yet to confirm their participation. You cannot submit evidence until they have done so.'), $values['name']));
        } else {
            $description = '';

            $collaborativeAssessment = getSettingByScope($connection2, 'Free Learning', 'collaborativeAssessment');
            if ($collaborativeAssessment == 'Y' AND  !empty($rowEnrol['collaborationKey'])) {
                $collaborators = $unitStudentGateway->selectUnitCollaboratorsByKey($rowEnrol['collaborationKey'])->fetchAll();
                $collaborators = Format::nameListArray($collaborators, 'Student');

                $description .= Format::alert(__m('Collaborative Assessment is enabled: by submitting this work, you will be submitting on behalf of your collaborators as well as yourself.') .'<br/><br/>'. __m('Your Group').': '. implode(', ', $collaborators), 'message');
            }

            if ($rowEnrol['status'] == 'Current') {
                $description .= '<p>'.sprintf(__m('You are currently enroled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your class teacher or mentor will be notified, and will approve your unit completion in due course.'), $values['name']).'</p>';
            } elseif ($rowEnrol['status'] == 'Evidence Not Yet Approved') {
                $description .= Format::alert(__m('Your evidence has not been approved. Please read the feedback below, adjust your evidence, and submit again:'), 'warning');
    
                
            }
            $form->setDescription($description);

            // DISCUSSION
            $logs = $unitStudentGateway->selectUnitStudentDiscussion($rowEnrol['freeLearningUnitStudentID'])->fetchAll();
            $form->addRow()->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'title' => __('Comments'),
                'discussion' => $logs
            ]));

            // ADD COMMENT
            if ($rowEnrol['enrolmentMethod'] != 'externalMentor') {
                $commentBox = $form->getFactory()->createColumn()->addClass('flex flex-col');
                $commentBox->addTextArea('addComment')
                    ->placeholder(__m('Leave a comment'))
                    ->setClass('flex w-full')
                    ->setRows(3);
                $commentBox->addButton(__m('Add Comment'))
                    ->onClick('$(this).prop("disabled", true).wrap("<span class=\"submitted\"></span>");document.getElementById("enrolComment").submit()')
                    ->setClass('button rounded-sm right');

                $form->addRow()->addClass('-mt-4')->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                    'discussion' => [[
                        'surname' => $gibbon->session->get('surname'),
                        'preferredName' => $gibbon->session->get('preferredName'),
                        'image_240' => $gibbon->session->get('image_240'),
                        'comment' => $commentBox->getOutput(),
                    ]]
                ]));
            }
            
            echo $form->getOutput();
    
            // SUBMIT EVIDENCE
            $form = Form::create('enrol', $gibbon->session->get('absoluteURL').'/modules/Free Learning/units_browse_details_completePendingProcess.php?'.http_build_query($urlParams));

            $form->addHiddenValue('address', $gibbon->session->get('address'));
            $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
            $form->addHiddenValue('freeLearningUnitStudentID', $rowEnrol['freeLearningUnitStudentID']);

            $form->addRow()->addHeading(__('Submit your Evidence'));

            $row = $form->addRow();
                $row->addLabel('status', __('Status'));
                $row->addTextField('status')->readonly()->setValue(__m('Complete - Pending'));

            $row = $form->addRow();
                $row->addLabel('commentStudent', __('Comment'))->description(!empty($values['studentReflectionText']) ? $values['studentReflectionText'] : __m('Leave a brief reflective comment on this unit<br/>and what you learned.'));
                $row->addTextArea('commentStudent')->setRows(4)->required();

            $types = ['Link' => __('Link'), 'File' => __('File')];
            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addRadio('type')->fromArray($types)->inline()->required()->checked('Link');
    
            // File
            $fileUploader = $container->get(FileUploader::class);
            $form->toggleVisibilityByClass('evidenceFile')->onRadio('type')->when('File');
            $row = $form->addRow()->addClass('evidenceFile');
                $row->addLabel('file', __('Submit File'));
                $row->addFileUpload('file')->accepts($fileUploader->getFileExtensions())->required();

            // Link
            $form->toggleVisibilityByClass('evidenceLink')->onRadio('type')->when('Link');
            $row = $form->addRow()->addClass('evidenceLink');
                $row->addLabel('link', __('Submit Link'));
                $row->addURL('link')->maxLength(255)->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();
        }

        echo $form->getOutput();

    } elseif ($rowEnrol['status'] == 'Complete - Pending') { 
        // Waiting for teacher feedback

        $form = Form::create('enrolComment', $gibbon->session->get('absoluteURL').'/modules/Free Learning/units_browse_details_commentProcess.php?'.http_build_query($urlParams));
        $form->setClass('blank');
        $form->setTitle(__m('Complete - Pending Approval'));
        $form->setDescription(__m('Your evidence, shown below, has been submitted to your teacher/mentor for approval. This screen will show a teacher comment, once approval has been given.'));

        $form->addHiddenValue('address', $gibbon->session->get('address'));
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
        $form->addHiddenValue('freeLearningUnitStudentID', $rowEnrol['freeLearningUnitStudentID']);

        $evidenceLink = $rowEnrol['evidenceType'] == 'Link' ? $rowEnrol['evidenceLocation'] : './'.$rowEnrol['evidenceLocation'];

        // DISCUSSION
        $logs = $unitStudentGateway->selectUnitStudentDiscussion($rowEnrol['freeLearningUnitStudentID'])->fetchAll();
        $form->addRow()->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
            'title' => __('Comments'),
            'discussion' => $logs
        ]));

        // ADD COMMENT
        if ($rowEnrol['enrolmentMethod'] != 'externalMentor') {
            $commentBox = $form->getFactory()->createColumn();
            $commentBox->addTextArea('addComment')
                ->placeholder(__m('Leave a comment'))
                ->setClass('flex w-full')
                ->setRows(3);
            $commentBox->addButton(__m('Add Comment'))
                ->onClick('$(this).prop("disabled", true).wrap("<span class=\"submitted\"></span>");document.getElementById("enrolComment").submit()')
                ->setClass('button rounded-sm right');

            $form->addRow()->addClass('-mt-4')->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'discussion' => [[
                    'surname' => $gibbon->session->get('surname'),
                    'preferredName' => $gibbon->session->get('preferredName'),
                    'image_240' => $gibbon->session->get('image_240'),
                    'comment' => $commentBox->getOutput(),
                ]]
            ]));
        }

        echo $form->getOutput();

        // EVIDENCE DETAILS
        $form = Form::create('enrol', '');
        $row = $form->addRow();
            $row->addLabel('statusLabel', __('Status'));
            $row->addTextField('status')->readonly()->setValue($values['status']);

        $row = $form->addRow();
            $row->addLabel('evidenceTypeLabel', __m('Evidence Type'));
            $row->addTextField('evidenceType')->readonly()->setValue($rowEnrol['evidenceType']);

        $row = $form->addRow();
            $row->addLabel('evidence', __m('Evidence'));
            $row->addContent(Format::link($evidenceLink, __m('View'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));

        echo $form->getOutput();

    } elseif ($rowEnrol['status'] == 'Complete - Approved') { 
        // Complete, show status and feedback from teacher.

        $logs = $unitStudentGateway->selectUnitStudentDiscussion($rowEnrol['freeLearningUnitStudentID'])->fetchAll();
        $logContent = $page->fetchFromTemplate('ui/discussion.twig.html', [
            'title' => __('Comments'),
            'discussion' => $logs
        ]);

        $form = Form::create('enrol', '');
        $form->setTitle(__m('Complete - Approved'));
        
        $form->setDescription(__m('Congratulations! Your evidence, shown below, has been accepted and approved by your teacher(s), and so you have successfully completed this unit. Please look below for your teacher\'s comment.') . $logContent );

        $evidenceLink = $rowEnrol['evidenceType'] == 'Link' ? $rowEnrol['evidenceLocation']: './'.$rowEnrol['evidenceLocation'];
        $certificateLink = './modules/Free Learning/units_browse_details_enrol_certificate.php?freeLearningUnitID='.$freeLearningUnitID;

        $row = $form->addRow();
            $row->addLabel('statusLabel', __('Status'));
            $row->addTextField('status')->readonly()->setValue($values['status']);

        $row = $form->addRow();
            $row->addLabel('evidenceTypeLabel', __m('Evidence Type'));
            $row->addTextField('evidenceType')->readonly()->setValue($rowEnrol['evidenceType']);

        $row = $form->addRow();
            $row->addLabel('evidence', __m('Evidence'));
            $row->addContent(Format::link($evidenceLink, __m('View'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));

        $row = $form->addRow();
            $row->addLabel('certificate', __m('Certificate of Completion'));
            $row->addContent(Format::link($certificateLink, __m('Print Certificate'), ['class' => 'w-full ml-2 underline', 'target' => '_blank']));

        echo $form->getOutput();
        
        
        
    } elseif ($rowEnrol['status'] == 'Exempt') { 
        // Exempt, let student know

        $form = Form::create('enrol', '');
        $form->addClass('blank');
        $form->setTitle(__m('Exempt'));
        $form->setDescription(__m('You are exempt from completing this unit, which means you get the status of completion, without needing to submit any evidence.'));

        echo $form->getOutput();
    }

}
