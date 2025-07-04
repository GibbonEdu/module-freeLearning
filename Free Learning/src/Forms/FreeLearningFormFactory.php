<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Module\FreeLearning\Forms;

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;

/**
 * FreeLearningFormFactory
 *
 * @version v16
 * @since   v16
 */
class FreeLearningFormFactory extends DatabaseFormFactory
{
    /**
     * Create and return an instance of DatabaseFormFactory.
     * @return  object DatabaseFormFactory
     */
    public static function create(Connection $pdo = null)
    {
        return new FreeLearningFormFactory($pdo);
    }

    /**
     * Creates a fully-configured CustomBlocks input for Smart Blocks in the lesson planner.
     *
     * @param string $name
     * @param Session $session
     * @param string $guid
     * @return OutputableInterface
     */
    public function createFreeLearningSmartBlocks($name, $session, $guid, $settingGateway) : OutputableInterface
    {
        $blockTemplate = $this->createSmartBlockTemplate($guid, $settingGateway);

        // Create and initialize the Custom Blocks
        $customBlocks = $this->createCustomBlocks($name, $session)
            ->fromTemplate($blockTemplate)
            ->settings([
                'inputNameStrategy' => 'string',
                'addOnEvent'        => 'click',
                'sortable'          => true,
                'orderName'         => 'order',
            ])
            ->placeholder(__('Smart Blocks listed here...'))
            ->addBlockButton('quicksave', __('Quicksave'), $session->get('absoluteURL').'/modules/Free Learning/img/save.png', 'showHide')
            ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

        return $customBlocks;
    }

    /**
     * Creates a template for displaying Outcomes in a CustomBlocks input.
     *
     * @param string $guid
     * @return OutputableInterface
     */
    public function createSmartBlockTemplate($guid, $settingGateway) : OutputableInterface
    {
        $blockTemplate = $this->createTable()->setClass('blank w-full');
            $row = $blockTemplate->addRow();
            $row->addTextField('title')
                ->maxlength(100)
                ->setClass('w-3/4 title focus:bg-white')
                ->placeholder(__('Title'))
                ->append('<input type="hidden" id="freeLearningUnitBlockID" name="freeLearningUnitBlockID" value="">');

            $row = $blockTemplate->addRow()->addClass('w-3/4 flex justify-between mt-1');
                $row->addTextField('type')->placeholder(__('type (e.g. discussion, outcome)'))
                    ->maxlength(50)
                    ->setClass('w-full focus:bg-white mr-1');
                $row->addTextField('length')->placeholder(__('length (min)'))
                    ->maxlength(3)
                    ->setClass('w-24 focus:bg-white')->prepend('');

            $smartBlockTemplate = $settingGateway->getSettingByScope('Planner', 'smartBlockTemplate');
            $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
                $col->addLabel('contentsLabel', __('Block Contents'))->setClass('mt-3 -mb-2');
                $col->addTextArea('contents')->setRows(25)->addData('tinymce')->addData('media', '1')->setValue($smartBlockTemplate);

            $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
                $col->addLabel('teachersNotesLabel', __('Teacher\'s Notes'))->setClass('mt-3 -mb-2');
                $col->addTextArea('teachersNotes')->setRows(10)->addData('tinymce')->addData('media', '1');

        return $blockTemplate;
    }

    /**
     * Creates a fully-configured CustomBlocks input for Outcomes in the lesson planner.
     *
     * @param string $name
     * @param Session $session
     * @param string $gibbonDepartmentIDList
     * @param bool $allowOutcomeEditing
     * @return OutputableInterface
     */
    public function createFreeLearningOutcomeBlocks($name, $session, $gibbonDepartmentIDList, $allowOutcomeEditing = false) : OutputableInterface
    {
        $outcomeSelector = $this->createSelectOutcome('addOutcome', $gibbonDepartmentIDList);
        $blockTemplate = $this->createOutcomeBlockTemplate($allowOutcomeEditing);

        // Create and initialize the Custom Blocks
        $customBlocks = $this->createCustomBlocks($name, $session)
            ->fromTemplate($blockTemplate)
            ->settings([
                'inputNameStrategy' => 'string',
                'addOnEvent'        => 'change',
                'preventDuplicates' => true,
                'sortable'          => true,
                'orderName'         => 'outcomeorder',
            ])
            ->placeholder(__('Key outcomes listed here...'))
            ->addToolInput($outcomeSelector)
            ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

        // Add predefined block data (for creating new blocks, triggered with the outcome selector)
        $data = [];
        $sql = "SELECT gibbonOutcomeID as outcomegibbonOutcomeID, gibbonOutcome.name as outcometitle, category as outcomecategory, description as outcomecontents
                FROM gibbonOutcome";
        $outcomeData = $this->pdo->select($sql, $data)->fetchAll();

        foreach ($outcomeData as $outcome) {
            $customBlocks->addPredefinedBlock($outcome['outcomegibbonOutcomeID'], $outcome);
        }

        return $customBlocks;
    }

    /**
     * Creates a drop-down list of available outcomes by year group. Groups outcomes by school-wide and by department.
     *
     * @param string $name
     * @param string $gibbonDepartmentIDList
     * @return OutputableInterface
     */
    public function createSelectOutcome($name, $gibbonDepartmentIDList) : OutputableInterface
    {
        // Get School Outcomes
        $data = ['noCategory' => '['.__('No Category').']'];
        $sql = "SELECT (CASE WHEN category='' THEN :noCategory ELSE category END) AS groupBy, CONCAT('all ', category) as chainedTo, gibbonOutcomeID AS value, gibbonOutcome.name AS name
                FROM gibbonOutcome
                WHERE active='Y' AND scope='School'
                GROUP BY gibbonOutcome.gibbonOutcomeID
                ORDER BY groupBy, name";

        // Get Departmental Outcomes
        $data2 = ['gibbonDepartmentIDList' => $gibbonDepartmentIDList];
        $sql2 = "SELECT CONCAT(gibbonDepartment.name, ': ', category) AS groupBy, CONCAT('all ', category) as chainedTo, gibbonOutcomeID AS value, gibbonOutcome.name AS name
                FROM gibbonOutcome
                JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                WHERE active='Y' AND scope='Learning Area'
                AND FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, :gibbonDepartmentIDList)
                GROUP BY gibbonOutcome.gibbonOutcomeID
                ORDER BY groupBy, gibbonOutcome.name";

        $col = $this->createColumn($name.'Col')->setClass('');

        $col->addSelect($name)
            ->setClass('addBlock floatNone standardWidth')
            ->fromArray(['' => __('Choose an outcome to add it to this lesson')])
            ->fromArray([__('SCHOOL OUTCOMES') => []])
            ->fromQueryChained($this->pdo, $sql, $data, $name.'Filter', 'groupBy')
            ->fromArray([__('LEARNING AREAS') => []])
            ->fromQueryChained($this->pdo, $sql2, $data2, $name.'Filter', 'groupBy');

        // Get Categories by Year Group
        $data3 = ['noCategory' => '['.__('No Category').']'];
        $sql3 = "SELECT category as value, (CASE WHEN category='' THEN :noCategory ELSE category END) as name
                FROM gibbonOutcome
                WHERE active='Y'
                GROUP BY gibbonOutcome.category";

        $col->addSelect($name.'Filter')
            ->setClass('floatNone standardWidth mt-px')
            ->fromArray(['all' => __('View All')])
            ->fromQuery($this->pdo, $sql3, $data3);

        return $col;
    }

    /**
     * Creates a template for displaying Outcomes in a CustomBlocks input.
     *
     * @param string $allowOutcomeEditing
     * @return OutputableInterface
     */
    public function createOutcomeBlockTemplate($allowOutcomeEditing) : OutputableInterface
    {
        $blockTemplate = $this->createTable()->setClass('blank w-full');
            $row = $blockTemplate->addRow();
            $row->addTextField('outcometitle')
                ->setClass('w-3/4 title readonly')
                ->readonly()
                ->placeholder(__('Outcome Name'))
                ->append('<input type="hidden" id="outcomegibbonOutcomeID" name="outcomegibbonOutcomeID" value="">');

            $row = $blockTemplate->addRow();
            $row->addTextField('outcomecategory')
                ->setClass('w-3/4 readonly mt-1')
                ->readonly();

            $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
            if ($allowOutcomeEditing == 'Y') {
                $col->addTextArea('outcomecontents')->setRows(10)->addData('tinymce');
            } else {
                $col->addContent('')->wrap('<label for="outcomecontents" class="block pt-2">', '</label>')
                    ->append('<input type="hidden" id="outcomecontents" name="outcomecontents" value="">');
            }

        return $blockTemplate;
    }
}
