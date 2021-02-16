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

class MentorshipGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningMentorship';
    private static $primaryKey = 'freeLearningMentorshipID';
    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryMentorship(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['freeLearningMentorship.gibbonPersonIDStudent', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonRollGroup.name as rollGroup', "COUNT(DISTINCT freeLearningMentorship.gibbonPersonIDSchoolMentor) AS mentors"])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=freeLearningMentorship.gibbonPersonIDStudent')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonRollGroup', "gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID")
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['freeLearningMentorship.gibbonPersonIDStudent']);

        return $this->runQuery($query, $criteria);
    }

    public function selectMentorsByStudent($gibbonPersonIDStudent)
    {
        $data = ['gibbonPersonIDStudent' => $gibbonPersonIDStudent];
        $sql = "SELECT gibbonPersonIDSchoolMentor, gibbonPersonID, title, surname, preferredName, freeLearningMentorshipID
                FROM freeLearningMentorship
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonPersonIDSchoolMentor)
                WHERE freeLearningMentorship.gibbonPersonIDStudent=:gibbonPersonIDStudent
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

}
