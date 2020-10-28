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

namespace Gibbon\Module\FreeLearning;

use ZipArchive;
use Gibbon\Contracts\Services\Session;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitBlockGateway;
use Gibbon\Module\FreeLearning\Domain\UnitAuthorGateway;

class UnitImporter 
{
    protected $gibbonDepartmentIDList;
    protected $course;

    protected $files;

    protected $unitGateway;
    protected $unitBlockGateway;
    protected $unitAuthorGateway;
    protected $session;

    public function __construct(UnitGateway $unitGateway, UnitBlockGateway $unitBlockGateway, UnitAuthorGateway $unitAuthorGateway, Session $session)
    {
        $this->unitGateway = $unitGateway;
        $this->unitBlockGateway = $unitBlockGateway;
        $this->unitAuthorGateway = $unitAuthorGateway;
        $this->session = $session;
    }

    public function setDefaults($gibbonDepartmentIDList = null, $course = null)
    {
        $this->gibbonDepartmentIDList = $gibbonDepartmentIDList;
        $this->course = $course;
    }

    public function importFromFile($filePath) : bool
    {
        $zip = new ZipArchive();
        $zip->open($filePath);

        $json = $zip->getFromName('Units/data.json');

        $data = json_decode($json, true);
        $files = [];

        if (empty($data['units'])) return false;

        // Upload Files
        foreach ($data['files'] as $filename) {
            $uploadsFolder = 'uploads/'.date('Y').'/'.date('m');
            $destinationPath = $this->session->get('absolutePath').'/'.$uploadsFolder.'/'.$filename;

            if (@copy('zip://'.$filePath.'#Units/files/'.$filename, $destinationPath)) {
                $files[$filename] = $this->session->get('absoluteURL').'/'.$uploadsFolder.'/'.$filename;
            }
        }

        // Import Units
        foreach ($data['units'] as $unit) {
            $existingUnit = $this->unitGateway->selectBy(['name' => $unit['name']])->fetch();

            // Apply default values
            if (!empty($this->gibbonDepartmentIDList)) $unit['unit']['gibbonDepartmentIDList'] = $this->gibbonDepartmentIDList;
            if (!empty($this->course)) $unit['unit']['course'] = $this->course;
            $unit['unit']['gibbonPersonIDCreator'] = $this->session->get('gibbonPersonID');

            // Get the uploaded logo URL
            if (!empty($unit['unit']['logo']) && !empty($files[$unit['unit']['logo']])) {
                $unit['unit']['logo'] = $files[$unit['unit']['logo']] ?? '';
            }

            // Add or update the unit
            if (!empty($existingUnit)) {
                $freeLearningUnitID = $existingUnit['freeLearningUnitID'];
                $this->unitGateway->update($freeLearningUnitID, $unit['unit']);
            } else {
                $freeLearningUnitID = $this->unitGateway->insert($unit['unit']);
            }

            // Add Blocks
            foreach ($unit['blocks'] as $block) {
                $block['freeLearningUnitID'] = $freeLearningUnitID;
                if (!empty($existingUnit)) {
                    $existingBlock = $this->unitBlockGateway->selectBy([
                        'freeLearningUnitID' => $existingUnit['freeLearningUnitID'],
                        'title' => $block['title'],
                    ])->fetch();
                }

                if (!empty($existingBlock)) {
                    $this->unitBlockGateway->update($existingBlock['freeLearningUnitBlockID'], $block);
                } else {
                    $this->unitBlockGateway->insert($block);
                }
            }

            // Add Authors
            foreach ($unit['authors'] as $author) {
                $author['freeLearningUnitID'] = $freeLearningUnitID;
                $author['gibbonPersonID'] = null;

                if (!empty($existingUnit)) {
                    $existingAuthor = $this->unitAuthorGateway->selectBy([
                        'freeLearningUnitID' => $existingUnit['freeLearningUnitID'],
                        'surname' => $author['surname'],
                        'preferredName' => $author['preferredName'],
                    ])->fetch();
                }

                if (!empty($existingAuthor)) {
                    $this->unitAuthorGateway->update($existingAuthor['freeLearningUnitAuthorID'], $author);
                } else {
                    $this->unitAuthorGateway->insert($author);
                }
            }
        }

        // Connect Unit Prerequisites
        foreach ($data['units'] as $unit) {
            if (empty($unit['prerequisites'])) continue;

            $existingUnit = $this->unitGateway->selectBy(['name' => $unit['name']])->fetch();

            $prerequisiteList = $this->unitGateway->selectPrerequisiteIDsByNames($unit['prerequisites'])->fetchAll(\PDO::FETCH_COLUMN);
            $this->unitGateway->update($freeLearningUnitID, ['freeLearningUnitIDPrerequisiteList' => implode(',', $prerequisiteList)]);
        }

        $zip->close();

        unlink($filePath);

        return true;
    }

}
