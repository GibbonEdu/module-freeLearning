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

$returnInt = null;

//Only include module include if it is not already included (which it may be been on the index page)
$included = false;
$includes = get_included_files();
foreach ($includes as $include) {
    if ($include == $_SESSION[$guid]['absolutePath'].'/modules/Free Learning/moduleFunctions.php') {
        $included = true;
    }
}
if ($included == false) {
    include './modules/Free Learning/moduleFunctions.php';
}

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_unitHistory_my.php') == false) {
    //Acess denied
    $returnInt .= "<div class='error'>";
    $returnInt .= __($guid, 'You do not have access to this action.');
    $returnInt .= '</div>';
} else {
    $returnInt .= "<div class='linkTop'>";
    $returnInt .= sprintf(__($guid, '%1$sView Showcase of Student Work%2$s', 'Free Learning'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/showcase.php'>", '</a>')." | ";
    $returnInt .= sprintf(__($guid, '%1$sBrowse Units%2$s', 'Free Learning'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Free Learning/units_browse.php'>", '</a>');
    $returnInt .= '</div>';
    $returnInt .= "<p style='margin-top: 20px'>";
    $returnInt .= __($guid, 'This table shows your recent results and enrolment for Free Learning:', 'Free Learning');
    $returnInt .= '</p>';
    $returnInt .= getStudentHistory($connection2, $guid, $gibbonPersonID, true);
}

return $returnInt;
