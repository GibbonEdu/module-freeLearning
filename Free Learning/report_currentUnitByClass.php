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
use Gibbon\Forms\DatabaseFormFactory;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_currentUnitByClass.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Current Unit by Class'));

    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $sort = $_GET['sort'] ?? 'unit';

    $form = Form::create('filter', $gibbon->session->get('absoluteURL') . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');
    $form->setTitle(__m('Choose Class'));

    $form->addHiddenValue('q', '/modules/' . $gibbon->session->get('module') . '/report_currentUnitByClass.php');

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelectClass('gibbonCourseClassID', $gibbon->session->get('gibbonSchoolYearID'), $gibbon->session->get('gibbonPersonID'))
            ->required()
            ->selected($gibbonCourseClassID)
            ->placeholder();

    $sortOptions = ['unit' => __('Unit'), 'student' => __('Student')];
    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray($sortOptions)->selected($sort);

    $row = $form->addRow();
    $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();


    if ($gibbonCourseClassID != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort as class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            echo "<p style='margin-bottom: 0px'><b>".__('Class').'</b>: '.$row['course'].'.'.$row['class'].'</p>';

            //Get data on blocks in an efficient manner
            $blocks = getBlocksArray($connection2);

            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "SELECT
                        gibbonPerson.gibbonPersonID, surname, preferredName, freeLearningUnit.freeLearningUnitID, freeLearningUnit.name AS unitName, timestampJoined, collaborationKey, freeLearningUnitStudent.status, enrolmentMethod, fields
                    FROM
                        gibbonPerson
                        JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role='Student')
                        LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID OR enrolmentMethod='schoolMentor' OR enrolmentMethod='externalMentor') AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Current - Pending' OR freeLearningUnitStudent.status='Complete - Pending' OR freeLearningUnitStudent.status='Evidence Not Yet Approved'))
                        LEFT JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    WHERE
                        gibbonPerson.status='Full'
                        AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."')
                        AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."')
                        AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID";
                if ($sort == 'student') {
                    $sql .= " ORDER BY surname, preferredName, unitName";
                } else {
                    $sql .= " ORDER BY unitName, collaborationKey, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            //Check for custom field
            $customField = getSettingByScope($connection2, 'Free Learning', 'customField');

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __('Number');
            echo '</th>';
            echo '<th>';
            echo __('Student');
            echo '</th>';
            echo '<th>';
            echo __m('Group');
            echo '</th>';
            echo '<th>';
            echo __('Unit').'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".__m('Status').'</span>';
            echo '</th>';
            echo '<th>';
            echo __m('Date Started');
            echo '</th>';
            echo '<th>';
            echo __m('Days Since Started');
            echo '</th>';
            echo '<th>';
            echo __m('Length').'</br>';
            echo "<span style='font-size: 85%; font-style: italic'>".__('Minutes').'</span>';
            echo '</th>';
            echo '<th>';
            echo __m('Time Spent').'</br>';
            echo "<span style='font-size: 85%; font-style: italic'>".__m('Minutes By Day\'s End').'</span>';
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            $group = 0;
            $collaborationKeys = array();
            while ($row = $result->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $count;
                echo '</td>';
                echo '<td>';
                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."'>".formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</a><br/>';
                $fields = json_decode($row['fields'], true);
                if (!empty($fields[$customField])) {
                    $value = $fields[$customField];
                    if ($value != '') {
                        echo "<span style='font-size: 85%; font-style: italic'>".$value.'</span>';
                    }
                }
                echo '</td>';
                echo '<td>';
                if ($row['collaborationKey'] != '') {
                    if (isset($collaborationKeys[$row['collaborationKey']]) == false) {
                        ++$group;
                        $collaborationKeys[$row['collaborationKey']] = $group;
                    }
                    echo $collaborationKeys[$row['collaborationKey']];
                }
                echo '</td>';
                echo '<td>';
                if ($row['enrolmentMethod'] == "schoolMentor" || $row['enrolmentMethod'] == "externalMentor") {
                    echo "<span class=\"float-right tag message border border-blue-300 ml-2\">".__m(ucfirst(preg_replace('/(?<!\ )[A-Z]/', ' $0', $row['enrolmentMethod'])))."</span>";
                }
                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&tab=2&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=&difficulty=&name='>".htmlPrep($row['unitName']).'</a>';
                echo "<br/><span style='font-size: 85%; font-style: italic'>".__m($row['status']).'</span>';
                echo '</td>';
                echo '<td>';
                if ($row['timestampJoined'] != '') {
                    echo dateConvertBack($guid, substr($row['timestampJoined'], 0, 10));
                }
                echo '</td>';
                echo '<td>';
                if ($row['timestampJoined'] != '') {
                    echo round((time() - strtotime($row['timestampJoined'])) / (60 * 60 * 24));
                }
                echo '</td>';
                echo '<td>';
                    if ($row['timestampJoined'] != '') {
                        $timing = null;
                        if ($blocks != false) {
                            foreach ($blocks as $block) {
                                if ($block[0] == $row['freeLearningUnitID']) {
                                    if (is_numeric($block[2])) {
                                        $timing += $block[2];
                                    }
                                }
                            }
                        }
                        if (is_null($timing)) {
                            echo '<i>'.__('N/A').'</i>';
                        } else {
                            echo $timing;
                        }
                    }
                echo '</td>';
                echo '<td>';
                    $spent = 0;
                    if ($row['timestampJoined'] != '') {
                        try {
                            $dataLessons = array('gibbonCourseClassID' => $gibbonCourseClassID, "dateJoined" => substr($row['timestampJoined'], 0, 10), "today" => date('Y-m-d'));
                            $sqlLessons = "SELECT date, timeStart, timeEnd FROM gibbonPlannerEntry WHERE name LIKE '%Free Learning%' AND gibbonCourseClassID=:gibbonCourseClassID AND date>=:dateJoined AND date<=:today";
                            $resultLessons = $connection2->prepare($sqlLessons);
                            $resultLessons->execute($dataLessons);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultLessons->rowCount() < 1) {
                            echo $spent;
                        }
                        else {
                            while ($rowLessons = $resultLessons->fetch()) {
                                $start_date = new DateTime($rowLessons['date'].' '.$rowLessons['timeStart']);
                                $since_start = $start_date->diff(new DateTime($rowLessons['date'].' '.$rowLessons['timeEnd']));
                                $spent += (60*$since_start->h) + $since_start->i;
                            }

                            if (is_null($timing)) { //No length to compare to, so just spit out answer
                                echo $spent;
                            }
                            else if ($spent<=$timing) { //OK for time, spit out in green
                                echo "<span style='font-weight: bold; color: #390;'>$spent</span>";
                            }
                            else { //Over time, spit out in orange
                                echo "<span style='font-weight: bold; color: #D65602;'>$spent</span>";
                            }

                        }
                    }
                echo '</td>';
                echo '</tr>';
            }
            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=3>';
                echo __('There are no records to display.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>
