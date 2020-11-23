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
use Gibbon\Forms\DatabaseFormFactory;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_unitHistory_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs
             ->add(__m('Unit History By Student'));

        echo '<h2>';
        echo __($guid, 'Choose Student', 'Free Learning');
        echo '</h2>';

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;

        // FORM
        $form = Form::create('filter', $gibbon->session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');
        $form->addHiddenValue('q', '/modules/Free Learning/report_unitHistory_byStudent.php');

        if ($highestAction == 'Unit History By Student_all') {
			$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, username, surname, preferredName FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY surname, preferredName";
		} else {
			$data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonFamily.name as familyName, child.surname, child.preferredName, child.gibbonPersonID
					FROM gibbonFamilyAdult
					JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
					LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
					LEFT JOIN gibbonPerson AS child ON (gibbonFamilyChild.gibbonPersonID=child.gibbonPersonID)
					WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
					AND gibbonFamilyAdult.childDataAccess='Y' AND child.status='Full'
					ORDER BY gibbonFamily.name, child.surname, child.preferredName";
		}

		$result = $pdo->executeQuery($data, $sql);
		$resultSet = ($result && $result->rowCount() > 0)? $result->fetchAll() : array();
		$people = array_reduce($resultSet, function($carry, $person) use ($highestAction) {
			$value = $person['gibbonPersonID'];
			$carry[$value] = Format::name('', htmlPrep($person['preferredName']), htmlPrep($person['surname']), 'Student', true);
			if ($highestAction == 'Unit History By Student_all') {
				$carry[$value] .= ' ('.$person['username'].')';
			}
			return $carry;
		}, array());

		$row = $form->addRow();
			$row->addLabel('gibbonPersonID', __('Person'));
			$row->addSelect('gibbonPersonID')
                ->fromArray($people)
                ->required()
                ->selected($gibbonPersonID)
				->placeholder();

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

        echo $form->getOutput();


        if ($gibbonPersonID != '') {
            $output = '';
            echo '<h2>';
            echo __($guid, 'Report Data');
            echo '</h2>';

            //Check access for parents
            if ($highestAction == 'Unit History By Student_myChildren') {
                try {
                    $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $gibbon->session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'));
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyAdult.childDataAccess='Y' AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                } catch (PDOException $e) {
                }

                if ($resultChild->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'An error occurred.');
                    echo '</div>';
                } else {
                    echo getStudentHistory($connection2, $guid, $gibbonPersonID);
                }
            } else {
                echo getStudentHistory($connection2, $guid, $gibbonPersonID);
            }
        }
    }
}
?>
