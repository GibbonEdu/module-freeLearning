<?php
/*
Gibbon, Free & Open School System
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

namespace Gibbon\Module\FreeLearning\Tables;

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

/**
 * UnitHistory
 *
 * @version v5.13.18
 * @since   v5.13.18
 */
class UnitHistory
{
    protected $unitStudentGateway;

    public function __construct(UnitStudentGateway $unitStudentGateway, View $templateView)
    {
        $this->unitStudentGateway = $unitStudentGateway;
        $this->templateView = $templateView;
    }

    public function create($gibbonPersonID, $summary = false, $canBrowse = true)
    {
        $criteria = $this->unitStudentGateway->newQueryCriteria()
            ->sortBy(['freeLearningUnitStudent.timestampJoined', 'schoolYear'], 'DESC')
            ->sortBy(['freeLearningUnitStudent.status', 'freeLearningUnit.name'])
            ->fromPOST('unitHistory');

        if ($summary) {
            $criteria->pageSize(8);
        }

        $units = $this->unitStudentGateway->queryUnitsByStudent($criteria, $gibbonPersonID);

        $table = !$summary
            ? DataTable::createPaginated('unitHistory', $criteria)->withData($units)
            : DataTable::create('unitHistory')->withData($units);

        $filterOptions = [
            'status:current - pending'         => __('Status') .': '.__m('Current - Pending'),
            'status:current'                   => __('Status') .': '.__m('Current'),
            'status:complete - pending'        => __('Status') .': '.__m('Complete - Pending'),
            'status:evidence not yet approved' => __('Status') .': '.__m('Evidence Not Yet Approved'),
            'status:complete - approved'       => __('Status') .': '.__m('Complete - Approved'),
        ];

        $learningAreas = $this->unitStudentGateway->selectLearningAreasByStudent($gibbonPersonID)->fetchKeyPair();
        foreach ($learningAreas as $learningArea) {
            $filterOptions['department:' . $learningArea] = __('Learning Area') .': '.__($learningArea);
        }

        $table->addMetaData('filterOptions', $filterOptions);

        $table->addExpandableColumn('commentStudent')
            ->format(function ($values) {
                if ($values['status'] == 'Current' || $values['status'] == 'Current - Pending') return;
                if (empty($values['commentStudent']) && empty($values['commentApproval'])) return;

                $logs = $this->unitStudentGateway->selectUnitStudentDiscussion($values['freeLearningUnitStudentID'])->fetchAll();

                return $this->templateView->fetchFromTemplate('ui/discussion.twig.html', [
                    'discussion' => $logs
                ]);
            });

        $table->addColumn('timestampJoined', __('School Year'))->description(__('Date'))
            ->sortable(['freeLearningUnitStudent.timestampJoined', 'schoolYear'])
            ->format(function($values) {
                return $values['schoolYear'].'<br/>'.Format::small(Format::date($values['timestampJoined']));
            });

        $table->addColumn('unit', __('Unit'))
            ->description(__m('Learning Area').'/'.__m('Course'))
            ->format(function($values) use ($canBrowse) {
                if ($canBrowse) {
                    $url = './index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=' . $values['freeLearningUnitID'] . '&freeLearningUnitStudentID='.$values['freeLearningUnitStudentID'].'&sidebar=true';
                    $output = Format::link($url, $values['unit']);
                } else {
                    $output = $values['unit'];
                }

                if (!empty($values['learningArea'])) {
                    $output .= '<br/>'.Format::small($values['learningArea']);
                }
                if (!empty($values['flCourse'])) {
                    $output .= '<br/>'.Format::small($values['flCourse']);
                }

                return $output;
            });

        $table->addColumn('enrolmentMethod', __m('Enrolment Method'))
            ->format(function ($values) {
                return ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $values["enrolmentMethod"]));
            });

        $table->addColumn('class', __m('Class'))
            ->format(function ($values) {
                return !empty($values['class']) ? Format::courseClassName($values['course'], $values['class']) : Format::small(__('N/A'));
            });

        $table->addColumn('status', __('Status'));

        $table->addColumn('evidence', __('Evidence'))
            ->notSortable()
            ->width('10%')
            ->format(function ($values) {
                if (empty($values['evidenceLocation'])) return;

                $url = $values['evidenceType'] == 'Link'
                    ? $values['evidenceLocation']
                    : './'.$values['evidenceLocation'];

                return Format::link($url, __('View'), ['target' => '_blank']);
            });

        return $table;
    }
}
