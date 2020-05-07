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
use Gibbon\Domain\System\SettingGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/settings_manage') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__('Manage Settings'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $settingGateway = $container->get(SettingGateway::class);

    // FORM
    $form = Form::create('settings', $gibbon->session->get('absoluteURL').'/modules/Free Learning/settings_manageProcess.php');

    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $form->addRow()->addHeading(__m('General Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'publicUnits', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'learningAreaRestriction', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'difficultyOptions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addTextField($setting['name'])->required()->setValue($setting['value'])->maxLength(50);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'unitOutlineTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8);

    $sql = "SELECT gibbonPersonFieldID as value, name FROM gibbonPersonField WHERE active='Y'";
    $setting = $settingGateway->getSettingByScope('Free Learning', 'customField', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromQuery($pdo, $sql)->selected($setting['value'])->placeholder();

    $form->addRow()->addHeading(__m('Enrolment Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'enableClassEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'enableSchoolMentorEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Free Learning', 'enableExternalMentorEnrolment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $form->addRow()->addHeading(__m('Submissions Settings'));

    $setting = $settingGateway->getSettingByScope('Free Learning', 'collaborativeAssessment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
