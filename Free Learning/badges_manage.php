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

@session_start();

//Module includes
include './modules/Free Learning/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/badges_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid,'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > </div><div class='trailEnd'><?php echo __($guid, 'Manage Badges'); ?></div>";
    echo '</div>';

    if (isModuleAccessible($guid, $connection2, '/modules/Badges/badges_manage.php') == false) {
        //Acess denied
        echo "<div class='error'>";
        echo __($guid, 'This functionality requires the Badges module to be installed, active and available.', 'Free Learning');
        echo '</div>';
    } else {
        //Acess denied
        echo "<div class='success'>";
        echo __($guid, 'The Badges module is installed, active and available, so you can access this functionality.', 'Free Learning');
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Set pagination variable
        $page = null;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        echo "<h2 class='top'>";
        echo __($guid, 'Search');
        echo '</h2>';
        ?>
    	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
    		<table class='smallIntBorder' cellspacing='0' style="width: 100%">
    			<tr>
    				<td>
    					<b><?php echo __($guid, 'Search For'); ?></b><br/>
    					<span style="font-size: 90%"><i><?php echo __($guid, 'Name, Category') ?></i></span>
    				</td>
    				<td class="right">
    					<input name="search" id="search" maxlength=20 value="<?php echo $search ?>" type="text" style="width: 300px">
    				</td>
    			</tr>
    			<tr>
    				<td colspan=2 class="right">
    					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/badges_manage.php">
    					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
    					<?php
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/badges_manage.php'>Clear Search</a> "; ?>
    					<input type="submit" value="Submit">
    				</td>
    			</tr>
    		</table>
    	</form>

    	<?php
        echo "<h2 class='top'>";
        echo __($guid, 'View');
        echo '</h2>';

        try {
            $data = array();
            $sql = 'SELECT freeLearningBadge.*, name, category, logo, description
                FROM  freeLearningBadge
                    JOIN badgesBadge ON (freeLearningBadge.badgesBadgeID=badgesBadge.badgesBadgeID)
                ORDER BY category, name';
            if ($search != '') {
                $data = array('search1' => "%$search%", 'search2' => "%$search%");
                $sql = 'SELECT freeLearningBadge.*, name, category, logo, description
                    FROM  freeLearningBadge
                        JOIN badgesBadge ON (freeLearningBadge.badgesBadgeID=badgesBadge.badgesBadgeID)
                    WHERE (badgesBadge.name LIKE :search1 OR badgesBadge.category LIKE :search2)
                    ORDER BY category, name';
            }
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) { echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/badges_manage_add.php&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) { echo "<div class='error'>";
            echo __($guid, 'There are no badges to display.', 'Free Learning');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
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
            echo "<th style='width: 120px'>";
            echo __($guid, 'Actions');
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
                echo '<td>';
                echo "<script type='text/javascript'>";
                echo '$(document).ready(function(){';
                echo "\$(\".comment-$count\").hide();";
                echo "\$(\".show_hide-$count\").fadeIn(1000);";
                echo "\$(\".show_hide-$count\").click(function(){";
                echo "\$(\".comment-$count\").fadeToggle(1000);";
                echo '});';
                echo '});';
                echo '</script>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/badges_manage_edit.php&freeLearningBadgeID='.$row['freeLearningBadgeID']."&search=$search'><img title='Edit' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                if ($row['description'] != '') {
                    echo "<a class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' title='Show Description' onclick='return false;' /></a>";
                }
                echo '</td>';
                echo '</tr>';
                if ($row['description'] != '') {
                    echo "<tr class='comment-$count' id='comment-$count'>";
                    echo "<td style='background-color: #fff' colspan=5>";
                    echo nl2brr($row['description']);
                    echo '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
            }
        }
    }
}
?>
