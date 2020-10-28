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

class UnitExporter 
{
    protected $filename = 'FreeLearningUnits';
    protected $data = [];
    protected $files = [];

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

    public function addUnitToExport($freeLearningUnitID)
    {
        $unit = $this->unitGateway->getByID($freeLearningUnitID);

        if (empty($unit)) return;

        if (!empty($unit['logo'])) {
            $logoPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($unit['logo'], PHP_URL_PATH);
            if (file_exists($logoPath)) {
                $this->files[] = $logoPath;
                $unit['logo'] = basename($logoPath);
            }
        }
        
        $this->data['units'][] = [
            'name' => $unit['name'],
            'unit' => $unit,
            'prerequisites' => $this->unitGateway->selectPrerequisiteNamesByIDs($unit['freeLearningUnitIDPrerequisiteList'])->fetchAll(\PDO::FETCH_COLUMN),
            'blocks' => $this->unitBlockGateway->selectBlocksByUnit($freeLearningUnitID)->fetchAll(),
            'authors' => $this->unitAuthorGateway->selectAuthorsByUnit($freeLearningUnitID)->fetchAll(),
        ];
    }

    public function output()
    {
        // Create the zip archive and add contents
        $filepath = tempnam(sys_get_temp_dir(), 'freelearning');
        $zip = new ZipArchive();
        $zip->open($filepath, ZipArchive::CREATE);

        // Add Files
        foreach ($this->files as $filePath) {
            if (!file_exists($filePath)) continue;

            $zip->addFile($filePath, 'Units/files/'.basename($filePath));
            $this->data['files'][] = basename($filePath);
        }

        // Add Data
        $zip->addFromString('Units/data.json', json_encode($this->data, JSON_PRETTY_PRINT));

        $zip->close();

        // Stream the zip archive for downloading
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.htmlentities($this->filename).'.zip"');
        header('Content-Transfer-Encoding: base64');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        echo file_get_contents($filepath);

        unlink($filepath);
    }
}
