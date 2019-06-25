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

use Gibbon\Forms\Form;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Tables\View\GridView;
use Gibbon\View\View;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$publicUnits = getSettingByScope($connection2, 'Free Learning', 'publicUnits');

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == true or ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false))) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
        $highestAction = 'Browse Units_all';
        $roleCategory = null ;
    } else {
        $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
    }
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Breadcrumbs
        $page->breadcrumbs
             ->add(__m('Browse Units'));

        if ($publicUnits == 'Y' and isset($_SESSION[$guid]['username']) == false) {
            echo "<div class='linkTop'>";
                echo "<a class='button' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/showcase.php&sidebar=false'>".__($guid, 'View Our Free Learning Showcase', 'Free Learning')."</a>";
            echo '</div>';
        }

        //Get params
        $canManage = false;
        if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
            $canManage = true;
        }
        $showInactive = $canManage && isset($_GET['showInactive'])
            ? $_GET['showInactive']
            : 'N';
        $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';

        $view = $_GET['view'] ?? 'list';
        if ($view != 'grid' and $view != 'map') {
            $view = 'list';
        }

        $gibbonPersonID = $gibbon->session->get('gibbonPersonID');
        
        if ($canManage && !empty($_GET['gibbonPersonID'])) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
            $canManage = false;
        }
        
        //Get data on learning areas, authors and blocks in an efficient manner
        $learningAreaArray = getLearningAreaArray($connection2);
        $authors = getAuthorsArray($connection2);
        $blocks = getBlocksArray($connection2);

        // CRITERIA
        $unitGateway = $container->get(UnitGateway::class);
        $criteria = $unitGateway->newQueryCriteria()
            ->searchBy($unitGateway->getSearchableColumns(), $name)
            ->sortBy(['difficultyOrder', 'name'])
            ->filterBy('showInactive', $showInactive)
            ->filterBy('department', $gibbonDepartmentID)
            ->filterBy('difficulty', $difficulty)
            ->pageSize($view == 'list' ? 25 : 0)
            ->fromPOST();

        // FORM
        $form = Form::create('filter', $gibbon->session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->setClass('noIntBorder fullWidth');
        $form->addHiddenValue('q', '/modules/Free Learning/units_browse.php');
        $form->addHiddenValue('view', $view);
    
        $learningAreas = $unitGateway->selectLearningAreasAndCourses();
        $row = $form->addRow();
            $row->addLabel('gibbonDepartmentID', __('Learning Area & Course'));
            $row->addSelect('gibbonDepartmentID')->fromResults($learningAreas, 'groupBy')->selected($gibbonDepartmentID)->placeholder();

        $difficultyOptions = getSettingByScope($connection2, 'Free Learning', 'difficultyOptions');
        $difficulties = array_map('trim', explode(',', $difficultyOptions));
        $row = $form->addRow();
            $row->addLabel('difficulty', __('Difficulty'));
            $row->addSelect('difficulty')->fromArray($difficulties)->selected($difficulty)->placeholder();

        $row = $form->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')->setValue($criteria->getSearchText());

        if ($canManage) {
            $row = $form->addRow();
                $row->addLabel('showInactive', __m('Show Inactive Units?'));
                $row->addYesNo('showInactive')->selected($showInactive);

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __m('View As'));
                $row->addSelectUsers('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], ['includeStudents' => true])->selected($gibbonPersonID);
        }

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'));
        
        echo $form->getOutput();

        

        $learningAreas = getLearningAreas($connection2, $guid);
        $courses = getCourses($connection2);
        $difficulties = getSettingByScope($connection2, 'Free Learning', 'difficultyOptions');
             

        echo "<div class='linkTop' style='margin-top: 40px; margin-bottom: -35px'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=list'>".__($guid, 'List', 'Free Learning')." <img style='margin-bottom: -5px' title='".__($guid, 'List', 'Free Learning')."' src='./modules/Free Learning/img/iconList.png'/></a> ";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=grid'>".__($guid, 'Grid', 'Free Learning')." <img style='margin-bottom: -5px' title='".__($guid, 'Grid', 'Free Learning')."' src='./modules/Free Learning/img/iconGrid.png'/></a> ";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/units_browse.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=map'>".__($guid, 'Map', 'Free Learning')." <img style='margin-bottom: -5px' title='".__($guid, 'Map', 'Free Learning')."' src='./modules/Free Learning/img/iconMap.png'/></a> ";
        echo '</div>';

        // QUERY
        if ($highestAction == 'Browse Units_all' && $gibbonPersonID == $gibbon->session->get('gibbonPersonID')) {
            $units = $unitGateway->queryAllUnits($criteria, $gibbonPersonID, $publicUnits);
        } else {
            $units = $unitGateway->queryUnitsByPrerequisites($criteria, $gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, $roleCategory);
        }

        // Join a set of author data per unit
        $unitAuthors = $unitGateway->selectUnitAuthors()->fetchGrouped();
        $units->joinColumn('freeLearningUnitID', 'authors', $unitAuthors);

        // Join a set of prerequisite data per unit
        $unitPrereq = $unitGateway->selectUnitPrerequisitesByPerson($gibbonPersonID)->fetchGrouped();
        $units->joinColumn('freeLearningUnitID', 'prerequisites', $unitPrereq);

        if ($highestAction == 'Browse Units_prerequisites') {
            $units->transform(function (&$unit) {
                $prerequisitesMet= count(array_filter($unit['prerequisites'] ?? [], function ($prereq) {
                    return $prereq['complete'] == 'Y';
                })) >= count($unit['prerequisites']);

                $unit['prerequisitesMet'] = $prerequisitesMet? 'Y' : 'N';
            });
        }

        //Set pagination variable
        $pagination = 1;
        if (isset($_GET['page'])) {
            $pagination = $_GET['page'];
        }
        if ((!is_numeric($pagination)) or $pagination < 1) {
            $pagination = 1;
        }

        //Search with filters applied
        try {
            $unitList = getUnitList($connection2, $guid, $gibbonPersonID, $roleCategory, $highestAction, $gibbonDepartmentID, $difficulty, $name, $showInactive, $publicUnits, null, $difficulties);
            $data = $unitList[0];
            $sql = $unitList[1];
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($pagination - 1) * $_SESSION[$guid]['pagination']);
        $defaultImage = $gibbon->session->get('absoluteURL').'/themes/'.$gibbon->session->get('gibbonThemeName').'/img/anonymous_125.jpg';

        $viewUnitURL = "./index.php?q=/modules/Free Learning/units_browse_details.php&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view&sidebar=true";

        if ($units->getResultCount() == 0) {
            echo Format::alert(__('There are no records to display.'), 'error');
        } else {
            if ($view == 'list') {

                // DATA TABLE
                $table = DataTable::createPaginated('browseUnitsList', $criteria);
                $table->setTitle(__('Units'));

                $table->modifyRows(function ($unit, $row) {
                    if ($unit['active'] != 'Y') $row->addClass('error');
                    if ($unit['status'] == 'Complete - Approved' || $unit['status'] == 'Exempt') $row->addClass('success');
                    if ($unit['status'] == 'Current' or $unit['status'] == 'Evidence Not Yet Approved' or $unit['status'] == 'Complete - Pending') $row->addClass('warning');
                    return $row;
                });

                $table->addColumn('name', __('Name'))
                    ->description(__('Status'))
                    ->context('primary')
                    ->width('15%')
                    ->format(function ($unit) use ($defaultImage) {
                        $imageClass = 'w-20 h-20 sm:w-32 sm:h-32 p-1 block mx-auto shadow bg-white border border-gray-600';
                        
                        $output = '<div class="text-sm sm:text-base font-bold text-center mt-1 mb-2">'.$unit['name'].'</div>';
                        $output .= sprintf('<img class="%1$s" src="%2$s">', $imageClass, $unit['logo'] ?? $defaultImage);
                        $output .= !empty($unit['status']) ? '<div class="text-sm text-center mt-2">'.$unit['status'].'</div>' : '';

                        return $output;
                    });

                $table->addColumn('learningArea', __('Learning Areas'))
                    ->description(__('Authors'))
                    ->context('secondary')
                    ->sortable(['learningArea'])
                    ->width('12%')
                    ->format(function ($unit) {
                        $unit['authors'] = array_map(function ($author) use (&$unit) {
                            $name = $author['preferredName'].' '.$author['surname'];
                            return !empty($author['website'])
                                ? Format::link($author['website'], $name)
                                : $name;
                        }, $unit['authors'] ?? []);

                        $output = !empty($unit['learningArea']) ? '<div class="text-xs mb-2">'.$unit['learningArea'].'</div>' : '';
                        $output .= '<div class="text-xxs">'.implode('<br/>', $unit['authors']).'</div>';
                        return $output;
                    });

                $table->addColumn('difficultyOrder', __m('Difficulty'))
                    ->description(__m('Blurb'))
                    ->width('40%')
                    ->format(function ($unit) {
                        $output = '<div class="text-xs font-bold mb-1">'.$unit['difficulty'].'</div>';
                        $output .= '<div class="text-xs">'.$unit['blurb'].'</div>';

                        return $output;
                    });

                $table->addColumn('length', __m('Length'))
                    ->format(function ($unit) {
                        $minutes = intval($unit['length']);
                        $relativeTime = __n('{count} min', '{count} mins', $minutes);
                        if ($minutes > 60) {
                            $hours = round($minutes / 60, 1);
                            $relativeTime = Format::tooltip(__n('{count} hr', '{count} hrs', ceil($minutes / 60), ['count' => $hours]), $relativeTime);
                        }

                        return !empty($unit['length'])
                            ? $relativeTime
                            : Format::small(__('N/A'));
                    });

                $table->addColumn('grouping', __m('Grouping'))
                    ->format(function ($unit) {
                        return implode('<br/>', explode(',', $unit['grouping'] ?? []));
                    });

                $table->addColumn('prerequisites', __m('Prerequisites'))
                    ->context('primary')
                    ->sortable('freeLearningUnitIDPrerequisiteList')
                    ->format(function ($unit) use (&$viewUnitURL, &$highestAction) {
                        $output = '';
                        $prerequisiteList = array_map(function ($prereq) use (&$unit, &$viewUnitURL) {
                            $url = $viewUnitURL.'&freeLearningUnitID='.$unit['freeLearningUnitID'];
                            return Format::link($url, $prereq['name']);
                        }, $unit['prerequisites'] ?? []);

                        if ($highestAction == 'Browse Units_prerequisites' && !empty($unit['prerequisites'])) {
                            if ($unit['prerequisitesMet'] == 'Y') {
                                $output = '<span class="tag inline-block success mb-2">'.__m('OK!').'</span><br/>';
                            } else {
                                $output = '<span class="tag inline-block error mb-2">'.__m('Not Met').'</span><br/>';
                            }
                        }
                        
                        $output .= !empty($prerequisiteList)
                            ? implode('<br/>', $prerequisiteList)
                            : Format::small(__('None'));

                        return $output;
                    });

                // ACTIONS
                $table->addActionColumn()
                    ->addParam('gibbonDepartmentID', $gibbonDepartmentID)
                    ->addParam('difficulty', $difficulty)
                    ->addParam('name', $name)
                    ->addParam('freeLearningUnitID')
                    ->format(function ($unit, $actions) use ($highestAction) {
                        if ($highestAction == 'Browse Units_all') {
                            $actions->addAction('view', __('View'))
                                ->addParam('sidebar', 'true')
                                ->addParam('showInactive', 'Y')
                                ->setURL('/modules/Free Learning/units_browse_details.php');
                        }

                        if ($highestAction == 'Browse Units_prerequisites' && ($unit['prerequisitesMet'] == 'Y' || empty($unit['prerequisites']))) {
                            $actions->addAction('view', __('View'))
                                ->addParam('sidebar', 'true')
                                ->addParam('showInactive', 'Y')
                                ->setURL('/modules/Free Learning/units_browse_details.php');
                        }
                    });

                echo $table->render($units);

            } elseif ($view == 'grid') {

                $gridRenderer = new GridView($container->get('twig'));
                $table = $container->get(DataTable::class)->setRenderer($gridRenderer);
                $table->setTitle(__('Units'));

                $table->addMetaData('gridClass', 'flex items-stretch -mx-4');
                $table->addMetaData('gridItemClass', 'foo');

                $cardView = new View($container->get('twig'));

                $table->addColumn('logo')
                    ->setClass('h-full pb-8')
                    ->format(function ($unit) use (&$cardView, &$defaultImage, &$viewUnitURL) {
                        return $cardView->fetchFromTemplate(
                            'unitCard.twig.html',
                            $unit + ['defaultImage' => $defaultImage, 'viewUnitURL' => $viewUnitURL]
                        );
                    });

                echo $table->render($units);

            } elseif ($view == 'map') {
                echo '<p>';
                echo __($guid, 'The map below shows all units selected by the filters above. Lines between units represent prerequisites. Units without prerequisites, which make good starting units, are highlighted by a blue border.', 'Free Learning');
                echo '</p>'; ?>
                <script type="text/javascript" src="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/vis/dist/vis.js"></script>
                <link href="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/lib/vis/dist/vis.css" rel="stylesheet" type="text/css" />

                <style type="text/css">
                    div#map {
                        height: 800px;
                        /* background-color: #ddd; */
                    }
                </style>

                <div id="map" class="w-full border rounded shadow-inner mb-4"></div>

                <?php
                //PREP NODE AND EDGE ARRAYS DATA
                $nodeArray = array();
                $edgeArray = array();
                $nodeList = '';
                $edgeList = '';
                $idList = '';
                $countNodes = 0;
                foreach ($units as $row) {
                    if ($units->getResultCount() <= 125) {
                        if ($row['logo'] != '') {
                            $image = $row['logo'];
                        } else {
                            $image = $_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/anonymous_240_square.jpg';
                        }
                    }
                    else {
                        $image = 'undefined';
                    }
                    $titleTemp = $string = trim(preg_replace('/\s\s+/', ' ', $row['blurb']));
                    $title = '<div class="text-base font-bold">'.addSlashes($row['name']).'</div>';
                    $title .= '<div class="text-xs text-gray-600 italic mb-2">'.addSlashes($row['difficulty']).'</div>';

                    if (strlen($row['blurb']) > 250) {
                        $title .= addSlashes(substr($titleTemp, 0, 250)).'...';
                    } else {
                        $title .= addSlashes($titleTemp);
                    }

                    if ($row['status'] == 'Complete - Approved' or $row['status'] == 'Exempt') {
                        $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'#390', background:'#D4F6DC'}, borderWidth: 2},";
                    } elseif ($row['status'] == 'Current') {
                        $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'#D65602', background:'#FFD2A9'}, borderWidth: 2},";
                    } elseif ($row['status'] == 'Evidence Not Yet Approved') {
                        $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'#FF0000', background:'#FF8485'}, borderWidth: 2},";
                    } elseif ($row['status'] == 'Complete - Pending') {
                        $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'#CA4AFF', background:'#F8A3FF'}, borderWidth: 2},";
                    }
                    else {
                        if ($row['freeLearningUnitIDPrerequisiteList'] == '') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: '$image', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'blue'}, borderWidth: 7},"; //#2b7ce9
                        } else {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: '$image', label: '".addSlashes($row['name'])."', title: '".$title."', color: {border:'#555555'}, borderWidth: 3},";
                        }
                    }

                    $nodeArray[$row['freeLearningUnitID']][0] = $countNodes;
                    $nodeArray[$row['freeLearningUnitID']][1] = $row['freeLearningUnitID'];
                    $nodeArray[$row['freeLearningUnitID']][2] = $row['freeLearningUnitIDPrerequisiteList'];
                    $idList .= "'".$row['freeLearningUnitID']."',";
                    ++$countNodes;
                }
                if ($nodeList != '') {
                    $nodeList = substr($nodeList, 0, -1);
                }
                if ($idList != '') {
                    $idList = substr($idList, 0, -1);
                }

                foreach ($nodeArray as $node) {
                    if (isset($node[2])) {
                        $edgeExplode = explode(',', $node[2]);
                        foreach ($edgeExplode as $edge) {
                            if (isset($nodeArray[$edge][0])===true) {
                                if (is_numeric($nodeArray[$edge][0])) {
                                    $edgeList .= '{from: '.$nodeArray[$node[1]][0].', to: '.$nodeArray[$edge][0].", arrows:'from'},";
                                }
                            }
                        }
                    }
                }
                if ($edgeList != '') {
                    $edgeList = substr($edgeList, 0, -1);
                }

                ?>
                <script type="text/javascript">
                    //CREATE NODE ARRAY
                    var nodes = new vis.DataSet([<?php echo $nodeList; ?>]);

                    //CREATE EDGE ARRAY
                    var edges = new vis.DataSet([<?php echo $edgeList ?>]);

                    //CREATE NODE TO freeLearningUnitID ARRAY
                    var ids = new Array(<?php echo $idList ?>);

                    //CREATE NETWORK
                    var container = document.getElementById('map');
                    var data = {
                    nodes: nodes,
                    edges: edges
                    };
                    var options = {
                        nodes: {
                            borderWidth:4,
                            size:30,
                            color: {
                                border: '#222222',
                                background: '#eeeeee'
                            },
                            font:{
                                color:'#333',
                                // size: 14,
                            },
                            shadow:true
                        },
                        edges: {
                            width:3,
                            selectionWidth: 3,
                            color: {
                                color: '#bbbbbb',
                                inherit: false
                            },
                            shadow:false,
                            arrows: {
                                from: {
                                    enabled: true,
                                    scaleFactor: 0.6
                                }
                            },
                        },
                        
                        interaction:{
                            navigationButtons: true,
                            zoomView: false
                        },
                        layout: {
                            randomSeed: 0.5,
                            improvedLayout:true
                        }
                    };
                    var network = new vis.Network(container, data, options);

                    //CLICK LISTENER
                    network.on( 'click', function(properties) {
                        var nodeNo = properties.nodes ;
                        if (nodeNo != '') {
                            window.location = '<?php echo $_SESSION[$guid]['absoluteURL'] ?>/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID=' + ids[nodeNo] + '&gibbonDepartmentID=<?php echo $gibbonDepartmentID ?>&difficulty=<?php echo $difficulty ?>&name=<?php echo $name ?>&view=<?php echo $view ?>';
                        }
                    });
                </script>
                <?php

            }
        }
    }
}
?>
