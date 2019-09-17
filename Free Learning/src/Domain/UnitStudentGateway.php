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

namespace Gibbon\Module\FreeLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class UnitStudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnitStudent';
    private static $primaryKey = 'freeLearningUnitStudentID';
    private static $searchableColumns = [];
    
    public function selectCurrentStudentsByUnit($gibbonSchoolYearID, $freeLearningUnitID)
    {
        $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.email, gibbonPerson.surname, gibbonPerson.preferredName, freeLearningUnitStudent.*, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, mentor.surname AS mentorsurname, mentor.preferredName AS mentorpreferredName, gibbonPerson.fields, freeLearningUnitStudent.freeLearningUnitStudentID
            FROM freeLearningUnitStudent
                INNER JOIN gibbonPerson ON freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                LEFT JOIN gibbonCourseClass ON (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                LEFT JOIN gibbonPerson AS mentor ON (freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID)
            WHERE freeLearningUnitID=:freeLearningUnitID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)
                AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID
            ORDER BY FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt'), surname, preferredName";

        return $this->db()->select($sql, $data);
    }
}
