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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//Module includes
include './modules/Free Learning/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/badges_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    	$page->breadcrumbs
        ->add(__('View Badges'));

    if (isModuleAccessible($guid, $connection2, '/modules/Badges/badges_manage.php') == false) {
        //Acess denied
        echo "<div class='error'>";
        echo __($guid, 'This functionality requires the Badges module to be installed, active and available.', 'Free Learning');
        echo '</div>';
    } else {
        //Set pagination variable
        $page = null;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        echo "<h2 class='top'>";
        echo 'View';
        echo '</h2>';

        try {
            $data = array();
            $sql = 'SELECT freeLearningBadge.*, name, category, logo, description
                FROM  freeLearningBadge
                    JOIN badgesBadge ON (freeLearningBadge.badgesBadgeID=badgesBadge.badgesBadgeID)
                ORDER BY unitsCompleteTotal, unitsCompleteDepartmentCount, name';
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) { echo "<div class='error'>".$e->getMessage().'</div>';
        }


        if ($result->rowCount() < 1) { echo "<div class='error'>";
            echo __($guid, 'There are no badges to display.', 'Free Learning');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top');
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo "<th style='width: 180px'>";
            echo __($guid, 'Logo', 'Free Learning');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Name').'<br/>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Category');
            echo '</th>';
            echo "<th>";
            echo __($guid, 'Description');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            try {
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($row = $resultPage->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                if ($row['active'] == 'N') {
                    $rowNum = 'error';
                }

    			//COLOR ROW BY STATUS!
    			echo "<tr class=$rowNum>";
                echo '<td>';
                if ($row['logo'] != '') {
                    echo "<img class='user' style='max-width: 150px' src='".$_SESSION[$guid]['absoluteURL'].'/'.$row['logo']."'/>";
                } else {
                    echo "<img class='user' style='max-width: 150px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_240_square.jpg'/>";
                }
                echo '</td>';
                echo '<td>';
                echo $row['name'];
                echo '</td>';
                echo '<td>';
                echo $row['category'];
                echo '</td>';
                echo "<td>";
                echo nl2brr($row['description']);
                echo '</td>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
            }
        }
    }
}
?>
