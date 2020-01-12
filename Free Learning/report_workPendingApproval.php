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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

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

    //List students whose status is Current or Complete - Pending
    try {
        $dataClass=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]);
        $sqlClass="(SELECT enrolmentMethod, freeLearningUnit.name AS unit, freeLearningUnit.freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS studentsurname, gibbonPerson.preferredName AS studentpreferredName, freeLearningUnitStudent.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonRole.category, NULL AS mentorsurname, NULL AS mentorpreferredName, gibbonPerson.fields
            FROM freeLearningUnit
                JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID
                AND (gibbonCourseClassPerson.role='Teacher' OR gibbonCourseClassPerson.role='Assistant')
                AND gibbonPerson.status='Full'
                AND freeLearningUnitStudent.status='Complete - Pending'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<='" . date("Y-m-d") . "')
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>='" . date("Y-m-d") . "')
                AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID)
            UNION
            (SELECT enrolmentMethod, freeLearningUnit.name AS unit, freeLearningUnit.freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS studentsurname, gibbonPerson.preferredName AS studentpreferredName, freeLearningUnitStudent.*, null AS course, null AS class, gibbonRole.category, mentor.surname AS mentorsurname, mentor.preferredName AS mentorpreferredName, gibbonPerson.fields
                FROM freeLearningUnit
                    JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                    JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                    LEFT JOIN gibbonPerson AS mentor ON (freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID)
                WHERE freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID2
                    AND gibbonPerson.status='Full'
                    AND freeLearningUnitStudent.status='Complete - Pending'
                    AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<='" . date("Y-m-d") . "')
                    AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>='" . date("Y-m-d") . "')
                    AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID)
            ORDER BY course, class, unit, studentsurname, studentpreferredName" ;
        $resultClass=$connection2->prepare($sqlClass);
        $resultClass->execute($dataClass);
    }
    catch(PDOException $e) {
        print "<div class='error'>" . $e->getMessage() . "</div>" ;
    }
    $count=0;
    $rowNum="odd" ;
    if ($resultClass->rowCount()<1) {
        print "<div class='success'>" ;
            print __($guid, "Well done, there is no work left to assess!") ;
        print "</div>" ;
    }
    else {
        ?>
        <table cellspacing='0' style="width: 100%">
            <tr class='head'>
                <th>
                    <?php print __($guid, 'Count') ?><br/>
                </th>
                <th>
                    <?php print __($guid, 'Enrolment Method', 'Free Learning') ?><br/>
                </th>
                <th>
                    <?php print __($guid, 'Class/Mentor', 'Free Learning') ?><br/>
                </th>
                <th>
                    <?php print __($guid, 'Unit') ?><br/>
                </th>
                <th>
                    <?php print __($guid, 'Student') ?><br/>
                </th>
                <th>
                    <?php print __($guid, 'Status') ?><br/>
                </th>
            </tr>
            <?php
            while ($rowClass=$resultClass->fetch()) {
                if ($count%2==0) {
                    $rowNum="even" ;
                }
                else {
                    $rowNum="odd" ;
                }
                $count++ ;

                print "<tr class=$rowNum>" ;
                    print '<td>';
                        echo $count;
                    print '</td>';
                    print '<td>';
                        print ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $rowClass["enrolmentMethod"])).'<br/>';
                    print '</td>';
                    print "<td>" ;
                        if ($rowClass['enrolmentMethod'] == 'class') {
                            if ($rowClass['course'] != '' and $rowClass['class'] != '') {
                                echo $rowClass['course'].'.'.$rowClass['class'];
                            } else {
                                echo '<i>'.__($guid, 'N/A').'</i>';
                            }
                        }
                        else if ($rowClass['enrolmentMethod'] == 'schoolMentor') {
                            echo formatName('', $rowClass['mentorpreferredName'], $rowClass['mentorsurname'], 'Student', false);
                        }
                        else if ($rowClass['enrolmentMethod'] == 'externalMentor') {
                            echo $rowClass['nameExternalMentor'];
                        }
                    print "</td>" ;
                    ?>
                    <td>
                        <?php
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=" . $rowClass["freeLearningUnitID"] . "&tab=2&sidebar=true'>" . $rowClass["unit"] . "</a>" ;
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($rowClass['category'] == 'Student') {
                            print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowClass["gibbonPersonID"] . "'>" . formatName("", $rowClass["studentpreferredName"], $rowClass["studentsurname"], "Student", true) . "</a>";
                        }
                        else {
                            print formatName("", $rowClass["studentpreferredName"], $rowClass["studentsurname"], "Student", true);
                        }
                        echo "<br/>";
                        $fields = unserialize($rowClass['fields']);
                        if (!empty($fields[$customField])) {
                            $value = $fields[$customField];
                            if ($value != '') {
                                echo "<span style='font-size: 85%; font-style: italic'>".$value.'</span>';
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?php print $rowClass["status"] ?><br/>
                    </td>
                <?php
                echo "</tr>" ;
            }
            ?>
        </table>
        <?php
    }
}
?>
