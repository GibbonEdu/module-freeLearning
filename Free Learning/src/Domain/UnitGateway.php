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

class UnitGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnit';
    private static $primaryKey = 'freeLearningUnitID';
    private static $searchableColumns = ['freeLearningUnit.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllUnits(QueryCriteria $criteria, $gibbonPersonID, $publicUnits = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['freeLearningUnit.*', "GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnitStudent.status', 
                "(SELECT SUM(freeLearningUnitBlock.length) FROM freeLearningUnitBlock WHERE freeLearningUnitBlock.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) as length",
                "FIND_IN_SET(freeLearningUnit.difficulty, :difficultyOptions) as difficultyOrder"])
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->leftJoin('freeLearningUnitStudent', "freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID")
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['freeLearningUnit.freeLearningUnitID']);

        $difficultyOptions = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='Free Learning' AND name='difficultyOptions'");
        $query->bindValue('difficultyOptions', $difficultyOptions);

        if ($publicUnits == 'Y') {
            $query->where("freeLearningUnit.sharedPublic='Y'")
                  ->where("freeLearningUnit.gibbonYearGroupIDMinimum IS NULL")
                  ->where("freeLearningUnit.active='Y'");
        }

        if (!$criteria->hasFilter('showInactive', 'Y')) {
            $query->where("freeLearningUnit.active='Y'");
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryUnitsByPrerequisites(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID, $roleCategory = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['freeLearningUnit.*', "GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as learningArea", 'freeLearningUnitStudent.status', 
                "(SELECT SUM(freeLearningUnitBlock.length) FROM freeLearningUnitBlock WHERE freeLearningUnitBlock.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) as length",
                "FIND_IN_SET(freeLearningUnit.difficulty, :difficultyOptions) as difficultyOrder"])
            ->leftJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->leftJoin('freeLearningUnitStudent', "(freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID)")
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['freeLearningUnit.freeLearningUnitID']);

        $difficultyOptions = $this->db()->selectOne("SELECT value FROM gibbonSetting WHERE scope='Free Learning' AND name='difficultyOptions'");
        $query->bindValue('difficultyOptions', $difficultyOptions);

        switch ($roleCategory) {
            case 'Student':
                $query->innerJoin('gibbonStudentEnrolment', 'gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
                      ->innerJoin('gibbonYearGroup as studentYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=studentYearGroup.gibbonYearGroupID')
                      ->leftJoin('gibbonYearGroup as minimumYearGroup', 'freeLearningUnit.gibbonYearGroupIDMinimum=minimumYearGroup.gibbonYearGroupID')
                      ->where('(minimumYearGroup.sequenceNumber IS NULL OR minimumYearGroup.sequenceNumber<=studentYearGroup.sequenceNumber)')
                      ->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableStudents='Y'")
                      ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
                break;

            case 'Parent':
                $query->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableParents='Y'");
                break;

            case 'Staff':
                $query->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableStaff='Y'");
                break;

            case 'Other':
                $query->where("freeLearningUnit.active='Y'")
                      ->where("freeLearningUnit.availableOther='Y'");
                break;
        }

        if (!$criteria->hasFilter('showInactive', 'Y')) {
            $query->where("freeLearningUnit.active='Y'");
        }

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function queryUnitsByLearningAreaStaff(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['freeLearningUnit.*', "GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as learningArea"])
            ->innerJoin('gibbonDepartment', "freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')")
            ->innerJoin('gibbonDepartmentStaff', 'gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID')
            ->where("(role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ")
            ->where('gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['freeLearningUnit.freeLearningUnitID']);

        $criteria->addFilterRules($this->getSharedFilterRules());

        return $this->runQuery($query, $criteria);
    }

    public function selectUnitPrerequisitesByPerson($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT freeLearningUnit.freeLearningUnitID as groupBy, prerequisite.name, freeLearningUnitStudent.status, 
                (CASE WHEN status='Complete - Approved' OR status='Complete - Pending' OR status='Exempt' THEN 'Y' ELSE 'N' END) as complete
                FROM freeLearningUnit
                JOIN freeLearningUnit as prerequisite ON (FIND_IN_SET(prerequisite.freeLearningUnitID, freeLearningUnit.freeLearningUnitIDPrerequisiteList))
                LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=prerequisite.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID)
                WHERE prerequisite.active='Y'
                ORDER BY prerequisite.name";
        return $this->db()->select($sql, $data);
    }

    public function selectUnitAuthors()
    {
        $sql = "SELECT freeLearningUnitID as groupBy, freeLearningUnitID,
                (CASE WHEN gibbonPerson.surname IS NOT NULL THEN gibbonPerson.surname ELSE freeLearningUnitAuthor.surname END) as surname, 
                (CASE WHEN gibbonPerson.preferredName IS NOT NULL THEN gibbonPerson.preferredName ELSE freeLearningUnitAuthor.preferredName END) as preferredName,
                (CASE WHEN gibbonPerson.gibbonPersonID IS NULL THEN freeLearningUnitAuthor.website END) as website
                FROM freeLearningUnitAuthor 
                LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=freeLearningUnitAuthor.gibbonPersonID)
                ORDER BY surname, preferredName";
        return $this->db()->select($sql);
    }

    public function selectLearningAreasAndCourses($gibbonPersonID = null)
    {
        if (!empty($gibbonPersonID)) {
            $data = ['gibbonPersonID' => $gibbonPersonID];
            $sql = "(SELECT gibbonDepartmentID as value, name, 'Learning Area' as groupBy 
                    FROM gibbonDepartment 
                    JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
                    WHERE type='Learning Area' AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID 
                    AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')
                    ORDER BY name)";
        } else {
            $data = [];
            $sql = "(SELECT gibbonDepartmentID as value, name, 'Learning Area' as groupBy 
                    FROM gibbonDepartment WHERE type='Learning Area' 
                    ORDER BY name)";
        }

        $sql .= " UNION ALL 
        (SELECT DISTINCT course as value, course as name, 'Course' as groupBy FROM freeLearningUnit WHERE active='Y' AND NOT course IS NULL AND NOT course='' ORDER BY course)
        ORDER BY groupBy DESC, name";

        return $this->db()->select($sql);
    }

    protected function getSharedFilterRules()
    {
        return [
            'active' => function ($query, $active) {
                return $query
                    ->where('freeLearningUnit.active = :active')
                    ->bindValue('active', $active);
            },
            'department' => function ($query, $department) {
                $department = is_numeric($department) ? str_pad($department, 4, '0', STR_PAD_LEFT): $department;
                return $query
                    ->where('(FIND_IN_SET(:department, freeLearningUnit.gibbonDepartmentIDList) OR course = :department)')
                    ->bindValue('department', $department);
            },
            'difficulty' => function ($query, $difficulty) {
                return $query
                    ->where('freeLearningUnit.difficulty = :difficulty')
                    ->bindValue('difficulty', $difficulty);
            },
            'access' => function ($query, $access) {
                switch ($access) {
                    case 'students': return $query->where("freeLearningUnit.availableStudents='Y'");
                    case 'staff': return $query->where("freeLearningUnit.availableStaff='Y'");
                    case 'parents': return $query->where("freeLearningUnit.availableParents='Y'");
                    case 'other': return $query->where("freeLearningUnit.availableOther='Y'");
                }
                return $query;
            },
        ];
    }
}
