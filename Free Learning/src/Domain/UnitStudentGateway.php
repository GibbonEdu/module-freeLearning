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

    public function queryCurrentStudentsByUnit($criteria, $gibbonSchoolYearID, $freeLearningUnitID, $gibbonPersonID, $manageAll)
    {
        if ($manageAll) {
            $query = $this
                ->newQuery()
                ->distinct()
                ->from($this->getTableName())
                ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.email', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'freeLearningUnitStudent.*', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'gibbonPerson.fields', 'freeLearningUnitStudent.freeLearningUnitStudentID', "FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt') as statusSort"], 'confirmationKey', 'collaborationKey')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
                ->leftJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
                ->where('freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID')
                ->bindValue('freeLearningUnitID', $freeLearningUnitID)
                ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->where("gibbonPerson.status='Full'")
                ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
                ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
                ->bindValue('today', date('Y-m-d'));
            }
            else {
                $query = $this
                    ->newQuery()
                    ->distinct()
                    ->from($this->getTableName())
                    ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.email', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'freeLearningUnitStudent.*', 'null AS course', 'null AS class', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'gibbonPerson.fields', 'freeLearningUnitStudent.freeLearningUnitStudentID', "FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt') as statusSort"], 'confirmationKey', 'collaborationKey')
                    ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                    ->innerJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
                    ->where('freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID')
                    ->bindValue('freeLearningUnitID', $freeLearningUnitID)
                    ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
                    ->where("gibbonPerson.status='Full'")
                    ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
                    ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
                    ->bindValue('today', date('Y-m-d'))
                    ->where('mentor.gibbonPersonID=:gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);

                $this->unionAllWithCriteria($query, $criteria)
                    ->distinct()
                    ->from($this->getTableName())
                    ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.email', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'freeLearningUnitStudent.*', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'null AS mentorsurname', 'null AS mentorpreferredName', 'gibbonPerson.fields', 'freeLearningUnitStudent.freeLearningUnitStudentID', "FIELD(freeLearningUnitStudent.status,'Complete - Pending','Evidence Not Yet Approved','Current','Complete - Approved','Exempt') as statusSort"], 'confirmationKey', 'collaborationKey')
                    ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                    ->innerJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                    ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
                    ->innerJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
                    ->where('freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID')
                    ->bindValue('freeLearningUnitID', $freeLearningUnitID)
                    ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
                    ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
                    ->where("gibbonPerson.status='Full'")
                    ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
                    ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
                    ->bindValue('today', date('Y-m-d'))
                    ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassPerson.role=\'Teacher\'')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);

            }

        return $this->runQuery($query, $criteria);
    }

    public function queryEvidencePending(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'gibbonRole.category', 'NULL AS mentorsurname', 'NULL AS mentorpreferredName', 'gibbonPerson.fields'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->where('(gibbonCourseClassPerson.role=\'Teacher\' OR gibbonCourseClassPerson.role=\'Assistant\') AND gibbonPerson.status=\'Full\' AND freeLearningUnitStudent.status=\'Complete - Pending\' AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('date', date("Y-m-d"))
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if (!is_null($gibbonPersonID)) {
            $query->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $this->unionWithCriteria($query, $criteria)
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'null AS course', 'null AS class', 'gibbonRole.category', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'gibbonPerson.fields'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->innerJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->where('gibbonPerson.status=\'Full\' AND freeLearningUnitStudent.status=\'Complete - Pending\'  AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('date', date("Y-m-d"))
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function getUnitStudentDetailsByID($freeLearningUnitID, $freeLearningUnitStudentID)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnit.*','freeLearningUnitStudent.*', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnit.freeLearningUnitID=freeLearningUnitStudent.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnitStudent.freeLearningUnitID = :freeLearningUnitID')
            ->bindValue('freeLearningUnitID', $freeLearningUnitID)
            ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
            ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);

        return $this->runSelect($query)->fetch();
    }

    public function selectUnitStudentDiscussion($freeLearningUnitStudentID)
    {
        $query = $this
            ->newSelect()
            ->cols(['gibbonDiscussion.*', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonPerson.username', 'gibbonPerson.email'])
            ->from('gibbonDiscussion')
            ->innerJoin('gibbonPerson', 'gibbonDiscussion.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonDiscussion.foreignTable = :foreignTable')
            ->bindValue('foreignTable', 'freeLearningUnitStudent')
            ->where('gibbonDiscussion.foreignTableID = :foreignTableID')
            ->bindValue('foreignTableID', $freeLearningUnitStudentID)
            ->orderBy(['gibbonDiscussion.timestamp']);

        $result = $this->runSelect($query);

        if ($result->rowCount() == 0) {
            $query = $this
                ->newSelect()
                ->cols(['freeLearningUnitStudent.commentStudent as comment', "'Complete - Pending' as type", "'pending' as tag", 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240',  'timestampCompletePending as timestamp'])
                ->from('freeLearningUnitStudent')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);

            $query->union()
                ->cols(['freeLearningUnitStudent.commentApproval as comment', 'freeLearningUnitStudent.status as type', "(CASE WHEN freeLearningUnitStudent.status = 'Complete - Pending' THEN 'pending' WHEN freeLearningUnitStudent.status = 'Evidence Not Yet Approved' THEN 'warning' WHEN freeLearningUnitStudent.status = 'Complete - Approved' THEN 'success' ELSE 'dull' END) as tag", 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240',  'timestampCompleteApproved as timestamp'])
                ->from('freeLearningUnitStudent')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDApproval=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);

            $result = $this->runSelect($query);
        }

        return $result;
    }

    public function selectUnitCollaboratorsByKey($collaborationKey)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.*', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnitStudent.collaborationKey = :collaborationKey')
            ->bindValue('collaborationKey', $collaborationKey);

        return $this->runSelect($query);
    }
}
