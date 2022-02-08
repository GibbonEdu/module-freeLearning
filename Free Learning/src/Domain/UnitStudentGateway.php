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
                    ->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND (gibbonCourseClassPerson.role=\'Teacher\' OR gibbonCourseClassPerson.role=\'Assistant\')')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);

            }

        return $this->runQuery($query, $criteria);
    }

    public function queryUnitsByStudent(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'freeLearningUnit.freeLearningUnitID',
                'freeLearningUnitStudentID',
                'enrolmentMethod',
                'freeLearningUnit.name AS unit',
                "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea",
                'freeLearningUnit.course AS flCourse',
                'freeLearningUnitStudent.status',
                'gibbonSchoolYear.name AS schoolYear',
                'evidenceLocation',
                'evidenceType',
                'commentStudent',
                'commentApproval',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class',
                'timestampCompletePending',
                'timestampCompleteApproved',
                'timestampJoined',
            ])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonSchoolYear', 'freeLearningUnitStudent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID']);

        $criteria->addFilterRules([
            'department' => function ($query, $department) {
                return $query
                    ->where('gibbonDepartment.name = :department')
                    ->bindValue('department', ucwords($department));
            },

            'status' => function ($query, $status) {
                return $query
                    ->where('freeLearningUnitStudent.status = :status')
                    ->bindValue('status', ucwords($status));
            },
        ]);
        return $this->runQuery($query, $criteria);
    }

    public function queryEvidencePending(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnit.course AS flCourse', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'gibbonCourse.nameShort AS course', 'gibbonCourseClass.nameShort AS class', 'gibbonRole.category', 'NULL AS mentorsurname', 'NULL AS mentorpreferredName', 'gibbonPerson.fields'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->where('(gibbonCourseClassPerson.role=\'Teacher\' OR gibbonCourseClassPerson.role=\'Assistant\') AND gibbonPerson.status=\'Full\' AND freeLearningUnitStudent.status=\'Complete - Pending\' AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('date', date("Y-m-d"))
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID']);

        if (!is_null($gibbonPersonID)) {
            $query->where('gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $this->unionWithCriteria($query, $criteria)
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnit.course AS flCourse', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'null AS course', 'null AS class', 'gibbonRole.category', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'gibbonPerson.fields'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->innerJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->where('gibbonPerson.status=\'Full\' AND freeLearningUnitStudent.status=\'Complete - Pending\'  AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date) AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('date', date("Y-m-d"))
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID']);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryMentorship(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->cols(['enrolmentMethod', 'freeLearningUnit.name AS unit', 'freeLearningUnit.freeLearningUnitID', "GROUP_CONCAT(DISTINCT gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnit.course AS flCourse', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname AS studentsurname', 'gibbonPerson.preferredName AS studentpreferredName', 'freeLearningUnitStudent.*', 'gibbonRole.category', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'teacher.surname AS teachersurname', 'teacher.preferredName AS teacherpreferredName', 'teacher.gibbonPersonID AS teachergibbonPersonID', 'gibbonPerson.fields', "(CASE WHEN freeLearningUnitStudent.status='Current - Pending' THEN 1 ELSE 0 END) as statusSort", "(CASE WHEN timestampCompletePending IS NOT NULL THEN timestampCompletePending ELSE timestampJoined END) as timestamp", "ROUND(AVG(((UNIX_TIMESTAMP(timestampCompleteApproved)-UNIX_TIMESTAMP(timestampCompletePending))/(60*60*24))), 1) AS waitInDays"])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID')
            ->leftJoin('gibbonPerson AS mentor', 'freeLearningUnitStudent.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->leftJoin('gibbonCourseClass', 'freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourseClassPerson', 'role=\'teacher\' AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonPerson AS teacher', 'gibbonCourseClassPerson.gibbonPersonID=teacher.gibbonPersonID')
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->where('freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonPerson.status='Full'")
            ->where("(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)")
            ->bindValue('date', date("Y-m-d"))
            ->groupBy(['freeLearningUnitStudent.freeLearningUnitStudentID']);

        if (!is_null($gibbonPersonID)) {
            $query->where("(enrolmentMethod='schoolMentor' AND mentor.gibbonPersonID=:gibbonPersonID) OR (enrolmentMethod='class' AND teacher.gibbonPersonID=:gibbonPersonID)")
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('freeLearningUnitStudent.status = :status')
                    ->bindValue('status', ucwords($status));
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentProgressByStudent(QueryCriteria $criteria, $gibbonCourseClassID, $gibbonSchoolYearID, $gibbonDepartmentID)
    {
        $query = $this
            ->newSelect();
            if (empty($gibbonDepartmentID)) {
                $query->cols(['gibbonPerson.gibbonPersonID',
                    'surname',
                    'preferredName',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END) AS completeApprovedCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END) AS completePendingCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END) AS currentCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END) AS currentPendingCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END) AS evidenceNotYetApprovedCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) AS exemptCount',
                    'SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) as totalCount',
                ]);
            } else {
                $query->cols(['gibbonPerson.gibbonPersonID',
                    'surname',
                    'preferredName',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END) AS completeApprovedCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END) AS completePendingCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END) AS currentCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END) AS currentPendingCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END) AS evidenceNotYetApprovedCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) AS exemptCount',
                    'SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Complete - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Current - Pending\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Evidence Not Yet Approved\' THEN 1 ELSE 0 END)+SUM(CASE WHEN FIND_IN_SET(:gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList) AND freeLearningUnitStudent.status = \'Exempt\' THEN 1 ELSE 0 END) as totalCount'
                ])
                    ->bindValue('gibbonDepartmentID', $gibbonDepartmentID);
            }
        $query->from('gibbonPerson')
            ->innerJoin('gibbonCourseClassPerson', 'gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role=\'Student\'')
            ->leftJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID OR enrolmentMethod=\'schoolMentor\' OR enrolmentMethod=\'externalMentor\') AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('freeLearningUnit', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->where('gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID')
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->bindValue('gibbonCourseClassID', $gibbonCourseClassID)
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectEnrolmentPending($gibbonSchoolYearID, $gibbonPersonID = null, $mentorshipAcceptancePrompt = 31)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor', 'COUNT(DISTINCT freeLearningUnitStudentID) AS count'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where("freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID")
            ->where("freeLearningUnitStudent.enrolmentMethod='schoolMentor'")
            ->where("freeLearningUnitStudent.status='Current - Pending'")
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->where('freeLearningUnitStudent.timestampJoined < DATE_SUB(:today, INTERVAL :mentorshipAcceptancePrompt DAY)')
            ->bindValue('today', date('Y-m-d'))
            ->bindvalue('mentorshipAcceptancePrompt', $mentorshipAcceptancePrompt)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor']);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($query);
    }

    public function selectEvidencePending($gibbonSchoolYearID, $gibbonPersonID = null, $evidenceOutstandingPrompt = 31)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor', 'COUNT(DISTINCT freeLearningUnitStudentID) AS count'])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where("freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID")
            ->where("freeLearningUnitStudent.enrolmentMethod='schoolMentor'")
            ->where("freeLearningUnitStudent.status='Complete - Pending'")
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->where('freeLearningUnitStudent.timestampCompletePending < DATE_SUB(:today, INTERVAL :evidenceOutstandingPrompt DAY)')
            ->bindValue('today', date('Y-m-d'))
            ->bindvalue('evidenceOutstandingPrompt', $evidenceOutstandingPrompt)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningUnitStudent.gibbonPersonIDSchoolMentor']);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDSchoolMentor=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($query);
    }

    public function selectEvidenceNotSubmitted($gibbonSchoolYearID, $gibbonPersonID = null, $studentEvidencePrompt = 31)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.gibbonPersonIDStudent', "freeLearningUnit.name"])
            ->from('freeLearningUnit')
            ->innerJoin('freeLearningUnitStudent', 'freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where("freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID")
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today) AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->where("(
                (freeLearningUnitStudent.status='Current' AND freeLearningUnitStudent.timestampJoined < DATE_SUB(:today, INTERVAL :studentEvidencePrompt DAY))
                OR (freeLearningUnitStudent.status='Evidence Not Yet Approved' AND freeLearningUnitStudent.timestampCompletePending < DATE_SUB(:today, INTERVAL :studentEvidencePrompt DAY))
                )
            ")
            ->bindValue('today', date('Y-m-d'))
            ->bindValue('studentEvidencePrompt', $studentEvidencePrompt)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if (!is_null($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runSelect($query);
    }

    public function getUnitStudentDetailsByID($freeLearningUnitID, $gibbonPersonID = null, $freeLearningUnitStudentID = null)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnit.*','freeLearningUnitStudent.*', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.gender', 'gibbonPerson.image_240', '(SELECT count(*) FROM gibbonINPersonDescriptor WHERE gibbonINPersonDescriptor.gibbonPersonID=freeLearningUnitStudent.gibbonPersonIDStudent GROUP BY gibbonINPersonDescriptor.gibbonPersonID) AS inCount'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('freeLearningUnit', 'freeLearningUnit.freeLearningUnitID=freeLearningUnitStudent.freeLearningUnitID')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnitStudent.freeLearningUnitID = :freeLearningUnitID')
            ->bindValue('freeLearningUnitID', $freeLearningUnitID);

        if (!empty($gibbonPersonID)) {
            $query->where('freeLearningUnitStudent.gibbonPersonIDStudent = :gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        if (!empty($freeLearningUnitStudentID)) {
            $query->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID);
        }

        return $this->runSelect($query)->fetch();
    }

    public function selectUnitStudentDiscussion($freeLearningUnitStudentID)
    {
        $query = $this
            ->newSelect()
            ->cols(['gibbonDiscussion.comment', 'gibbonDiscussion.type', 'gibbonDiscussion.tag', 'gibbonDiscussion.attachmentType', 'gibbonDiscussion.attachmentLocation', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonPerson.username', 'gibbonPerson.email', 'gibbonRole.category', 'gibbonDiscussion.timestamp'])
            ->from('gibbonDiscussion')
            ->innerJoin('gibbonPerson', 'gibbonDiscussion.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->where('gibbonDiscussion.foreignTable = :foreignTable')
            ->bindValue('foreignTable', 'freeLearningUnitStudent')
            ->where('gibbonDiscussion.foreignTableID = :foreignTableID')
            ->bindValue('foreignTableID', $freeLearningUnitStudentID);

        $query->union()
            ->cols(['freeLearningUnitStudent.commentApproval as comment', 'freeLearningUnitStudent.status as type', "(CASE WHEN freeLearningUnitStudent.status = 'Complete - Pending' THEN 'pending' WHEN freeLearningUnitStudent.status = 'Evidence Not Yet Approved' THEN 'warning' WHEN freeLearningUnitStudent.status = 'Complete - Approved' THEN 'success' ELSE 'dull' END) as tag", 'freeLearningUnitStudent.evidenceType as attachmentType', 'freeLearningUnitStudent.evidenceLocation as attachmentLocation', 'freeLearningUnitStudent.gibbonPersonIDStudent as gibbonPersonID', "'' as title", 'nameExternalMentor as surname', "'' as preferredName", '"" as image_240', '"" as email', "'Staff' as category", '"" as username', 'timestampCompleteApproved as timestamp'])
            ->from('freeLearningUnitStudent')
            ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
            ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID)
            ->where("freeLearningUnitStudent.enrolmentMethod = 'externalMentor'")
            ->where('gibbonPersonIDApproval IS NULL')
            ->where('commentApproval IS NOT NULL')

        ->orderBy(['timestamp']);

        $result = $this->runSelect($query);

        if ($result->rowCount() == 0) {
            $query = $this
                ->newSelect()
                ->cols(['freeLearningUnitStudent.commentStudent as comment', "'Complete - Pending' as type", "'pending' as tag", 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240',  "'Student' as category",  'timestampCompletePending as timestamp'])
                ->from('freeLearningUnitStudent')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID)
                ->where('commentStudent IS NOT NULL');

            $query->union()
                ->cols(['freeLearningUnitStudent.commentApproval as comment', 'freeLearningUnitStudent.status as type', "(CASE WHEN freeLearningUnitStudent.status = 'Complete - Pending' THEN 'pending' WHEN freeLearningUnitStudent.status = 'Evidence Not Yet Approved' THEN 'warning' WHEN freeLearningUnitStudent.status = 'Complete - Approved' THEN 'success' ELSE 'dull' END) as tag", 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240',  "'Staff' as category",  'timestampCompleteApproved as timestamp'])
                ->from('freeLearningUnitStudent')
                ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDApproval=gibbonPerson.gibbonPersonID')
                ->where('freeLearningUnitStudent.freeLearningUnitStudentID = :freeLearningUnitStudentID')
                ->bindValue('freeLearningUnitStudentID', $freeLearningUnitStudentID)
                ->where('commentApproval IS NOT NULL');

            $result = $this->runSelect($query);
        }

        return $result;
    }

    public function selectLearningAreasByStudent($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT DISTINCT gibbonDepartment.gibbonDepartmentID as value, gibbonDepartment.name
                FROM freeLearningUnit
                JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                JOIN gibbonDepartment ON (FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, freeLearningUnit.gibbonDepartmentIDList))
                WHERE freeLearningUnitStudent.gibbonPersonIDStudent = :gibbonPersonID
                AND gibbonDepartment.type='Learning Area'
                GROUP BY gibbonDepartment.gibbonDepartmentID
                ORDER BY gibbonDepartment.name";

        return $this->db()->select($sql, $data);
    }

    public function selectCoursesByStudent($gibbonPersonIDStudent, $gibbonSchoolYearID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonIDStudent, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'groupBy' => __m('Enrolled Course')];
        $sql = "SELECT DISTINCT course as value, course as name, :groupBy as groupBy
            FROM freeLearningUnit
            JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
            WHERE active='Y'
            AND NOT course IS NULL
            AND NOT course=''
            AND freeLearningUnitStudent.gibbonSchoolYearID=:gibbonSchoolYearID
            AND freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID
            ORDER BY course";

        return $this->db()->select($sql, $data);
    }

    public function selectUnitCollaboratorsByKey($collaborationKey)
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningUnitStudent.*', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', '(SELECT count(*) FROM gibbonINPersonDescriptor WHERE gibbonINPersonDescriptor.gibbonPersonID=freeLearningUnitStudent.gibbonPersonIDStudent GROUP BY gibbonINPersonDescriptor.gibbonPersonID) AS inCount'])
            ->from('freeLearningUnitStudent')
            ->innerJoin('gibbonPerson', 'freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->where('freeLearningUnitStudent.collaborationKey = :collaborationKey')
            ->bindValue('collaborationKey', $collaborationKey);

        return $this->runSelect($query);
    }

    public function selectUnitMentors($freeLearningUnitID, $gibbonPersonID, $params = [])
    {
        $sql = [];
        $data = [];

        if (!empty($params['disableLearningAreaMentors']) && $params['disableLearningAreaMentors'] == 'N') {
            $data = ['freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $gibbonPersonID];
            $sql[] = "(SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                FROM gibbonPerson
                    JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN freeLearningUnit ON (freeLearningUnit.gibbonDepartmentIDList LIKE concat('%',gibbonDepartmentStaff.gibbonDepartmentID,'%'))
                WHERE gibbonPerson.status='Full'
                    AND freeLearningUnitID=:freeLearningUnitID
                    AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                )";
        }
        if (!empty($params['schoolMentorCompletors']) && $params['schoolMentorCompletors'] == 'Y') {
            $sql[] = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                    FROM gibbonPerson
                    LEFT JOIN freeLearningUnitAuthor ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID AND freeLearningUnitAuthor.freeLearningUnitID=:freeLearningUnitID)
                    LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID)
                    WHERE gibbonPerson.status='Full'
                        AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                        AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitAuthor.freeLearningUnitAuthorID IS NOT NULL)
                    GROUP BY gibbonPersonID)";
        }
        if (!empty($params['schoolMentorCustom'])) {
            $data['schoolMentorCustom'] = $params['schoolMentorCustom'];
            $sql[] = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                FROM gibbonPerson
                WHERE FIND_IN_SET(gibbonPersonID, :schoolMentorCustom)
                    AND status='Full')";
        }
        if (!empty($params['schoolMentorCustomRole'])) {
            $data['gibbonRoleID'] = $params['schoolMentorCustomRole'];
            $sql[] = "(SELECT gibbonPerson.gibbonPersonID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname
                FROM gibbonPerson
                    JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE gibbonRoleID=:gibbonRoleID
                    AND status='Full')";
        }

        if (count($sql) == 1) {
            return $this->db()->select($sql[0]." ORDER BY surname, preferredName", $data);
        }
        else if (count($sql) > 1) {
            return $this->db()->select(implode(" UNION DISTINCT ", $sql)." ORDER BY surname, preferredName", $data);
        }
        else {
            return false;
        }
    }

    public function selectPotentialCollaborators($gibbonSchoolYearID, $gibbonPersonID, $roleCategory, $prerequisiteCount, $params = [])
    {
        if ($roleCategory == 'Student') {
            $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupIDMinimum' => $params['gibbonYearGroupIDMinimum'], 'prerequisiteList' => $params['freeLearningUnitIDPrerequisiteList'], 'prerequisiteCount' => $prerequisiteCount, 'freeLearningUnitID' => $params['freeLearningUnitID']];
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonFormGroup.name AS FormGroup, prerequisites.count, currentUnit.completed
            FROM gibbonPerson
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
            LEFT JOIN (
                SELECT COUNT(*) as count, freeLearningUnitStudent.gibbonPersonIDStudent
                FROM freeLearningUnitStudent
                JOIN freeLearningUnit ON (freeLearningUnit.freeLearningUnitID=freeLearningUnitStudent.freeLearningUnitID)
                WHERE freeLearningUnit.active='Y'
                AND (:prerequisiteList = '' OR FIND_IN_SET(freeLearningUnit.freeLearningUnitID, :prerequisiteList))
                AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitStudent.status='Exempt')
                GROUP BY freeLearningUnitStudent.freeLearningUnitStudentID
            ) AS prerequisites ON (prerequisites.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
            LEFT JOIN (
                SELECT freeLearningUnitStudentID as completed, freeLearningUnitStudent.gibbonPersonIDStudent
                FROM freeLearningUnitStudent
                WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID
                AND (freeLearningUnitStudent.status='Complete - Approved' OR freeLearningUnitStudent.status='Exempt')
            ) as currentUnit ON (currentUnit.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            AND status='Full' AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
            AND (:gibbonYearGroupIDMinimum IS NULL OR gibbonStudentEnrolment.gibbonYearGroupID >= :gibbonYearGroupIDMinimum)
            HAVING (:prerequisiteCount = 0 OR prerequisites.count >= :prerequisiteCount) AND (currentUnit.completed IS NULL)
            ORDER BY surname, preferredName";
        } else if ($roleCategory == 'Staff') {
            $data = ['gibbonPersonID' => $gibbonPersonID];
            $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname
                FROM gibbonPerson
                JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE status='Full'
                    AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                ORDER BY surname, preferredName";
        } else if ($roleCategory == 'Parent') {
            $data = ['gibbonPersonID' => $gibbonPersonID];
            $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname
                FROM gibbonPerson
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID LIKE concat( '%', gibbonPerson.gibbonRoleIDAll, '%' ) AND category='Parent')
                WHERE status='Full'
                    AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                ORDER BY surname, preferredName";
        }

        return $this->db()->select($sql, $data);
    }
}
