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

use Gibbon\View\View;
use Gibbon\Forms\Form;

//Module includes
include "./modules/" . $gibbon->session->get('module') . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_learningActivity.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
        print __($guid, "You do not have access to this action.") ;
    print "</div>" ;
}
else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Learning Activity'));

    $timePeriod = $_GET['timePeriod'] ?? 'Last 30 Days';

    // FORM
	$form = Form::create('filter', $gibbon->session->get('absoluteURL').'/index.php', 'get');
	$form->setTitle(__('Filter'));

	$form->setClass('noIntBorder fullWidth');
	$form->addHiddenValue('q', '/modules/Free Learning/report_learningActivity.php');

    $timePeriods = [
        'Last 30 Days' => __m('Last 30 Days'),
        'Last 12 Months' => __m('Last 12 Months'),
    ];
    $row = $form->addRow();
        $row->addLabel('timePeriod', __m('Time Period'));
        $row->addSelect('timePeriod')->fromArray($timePeriods)->selected($timePeriod);

	$row = $form->addRow();
		$row->addSearchSubmit($gibbon->session, __('Clear Filters'));

	echo $form->getOutput();


    if ($timePeriod != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array();
            if ($timePeriod == 'Last 30 Days')
                $sql = 'SELECT timestampJoined, timestampCompleteApproved, status FROM freeLearningUnitStudent WHERE timestampCompleteApproved>=DATE_SUB(NOW(), INTERVAL 30 DAY) OR timestampJoined>=DATE_SUB(NOW(), INTERVAL 30 DAY)';
            else if ($timePeriod == 'Last 12 Months')
                $sql = 'SELECT timestampJoined, timestampCompleteApproved, status FROM freeLearningUnitStudent WHERE timestampCompleteApproved>=DATE_SUB(NOW(), INTERVAL 12 MONTH) OR timestampJoined>=DATE_SUB(NOW(), INTERVAL 12 MONTH)';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            $rows = $result->fetchAll();

            //CREATE LEGEND
            $templateView = new View($container->get('twig'));
            echo $templateView->fetchFromTemplate('activityLegend.twig.html');

            //PLOT DATA
            echo '<script type="text/javascript" src="'.$gibbon->session->get('absoluteURL').'/lib/Chart.js/Chart.min.js"></script>';
            echo "<p style='margin-top: 20px; margin-bottom: 5px'><b>".__($guid, 'Data').'</b></p>';
            echo '<div style="width:100%">';
            echo '<div>';
            echo '<canvas id="canvas"></canvas>';
            echo '</div>';
            echo '</div>';
            ?>
            <script>
                var randomScalingFactor = function(){ return Math.round(Math.random()*100)};
                var lineChartData = {
                    <?php
                    $countJoinedTotal = 0;
                    $countApprovedTotal = 0 ;
                    echo 'labels : [';
                        if ($timePeriod == 'Last 30 Days') {
                            $days = array();
                            for($i = 0; $i < 30; $i++) {
                                $countJoined = 0;
                                $countApproved = 0 ;
                                $d = date("d", strtotime('-'. $i .' days'));
                                $m = date("m", strtotime('-'. $i .' days'));
                                foreach ($rows as $row) {
                                    if (is_numeric(strpos($row['timestampJoined'], $m."-".$d))) {
                                        $countJoined++ ;
                                    }
                                    if (is_numeric(strpos($row['timestampCompleteApproved'], $m."-".$d)) && $row['status'] == 'Complete - Approved') {
                                        $countApproved++ ;
                                    }
                                }
                                $countJoinedTotal += $countJoined;
                                $countApprovedTotal += $countApproved;
                                if ($i == 0)
                                    array_unshift($days, array(0 => '(Today) '.$d.'/'.$m, 1 => $countJoined, 2 => $countApproved));
                                else
                                    array_unshift($days, array(0 => $d.'/'.$m, 1 => $countJoined, 2 => $countApproved));
                            }
                            $labels = '';
                            foreach ($days AS $day) {
                                $labels .= '"'.$day[0].'",';
                            }
                            echo substr($labels, 0, -1);
                        }
                        else if ($timePeriod == 'Last 12 Months') {
                            $months = array();
                            for($i = 0; $i < 12; $i++) {
                                $countJoined = 0;
                                $countApproved = 0 ;
                                $m = date("m", strtotime('-'. $i .' months'));
                                $Y = date("Y", strtotime('-'. $i .' months'));
                                foreach ($rows as $row) {
                                    if (is_numeric(strpos($row['timestampJoined'], $Y."-".$m))) {
                                        $countJoined++ ;
                                    }
                                    if (is_numeric(strpos($row['timestampCompleteApproved'], $Y."-".$m)) && $row['status'] == 'Complete - Approved') {
                                        $countApproved++ ;
                                    }
                                }
                                $countJoinedTotal += $countJoined;
                                $countApprovedTotal += $countApproved;
                                if ($i == 0)
                                    array_unshift($months, array(0 => '(Today) '.$m.'/'.$Y, 1 => $countJoined, 2 => $countApproved));
                                else
                                    array_unshift($months, array(0 => $m.'/'.$Y, 1 => $countJoined, 2 => $countApproved));
                            }
                            $labels = '';
                            foreach ($months AS $month) {
                                $labels .= '"'.$month[0].'",';
                            }
                            echo substr($labels, 0, -1);
                        }
                    echo '],';
                    ?>

                    datasets : [
                        {
                            label: "Units Joined",
                            fillColor : "rgba(253, 226, 255, 0.5)",
                            strokeColor : "rgba(169, 60, 179,1)",
                            pointColor : "rgba(169, 60, 179,1)",
                            pointStrokeColor : "#fff",
                            pointHighlightFill : "#fff",
                            pointHighlightStroke : "rgba(169, 60, 179,1)",
                            data : [
                                <?php
                                $data = '';
                                if ($timePeriod == 'Last 30 Days') {
                                    foreach ($days AS $day) {
                                        $data .= $day[1].',';
                                    }
                                }
                                else if ($timePeriod == 'Last 12 Months') {
                                    foreach ($months AS $month) {
                                        $data .= $month[1].',';
                                    }
                                }
                                echo substr($data, 0, -1);
                                ?>
                            ]
                        },
                        {
                            label: "Units Approved",
                            fillColor : "rgba(198, 246, 213,0.5",
                            strokeColor : "rgba(47, 133, 90,1)",
                            pointColor : "rgba(47, 133, 90,1)",
                            pointStrokeColor : "#fff",
                            pointHighlightFill : "#fff",
                            pointHighlightStroke : "rgba(47, 133, 90,1)",
                            data : [
                                <?php
                                $data = '';
                                if ($timePeriod == 'Last 30 Days') {
                                    foreach ($days AS $day) {
                                        $data .= $day[2].',';
                                    }
                                }
                                else if ($timePeriod == 'Last 12 Months') {
                                    foreach ($months AS $month) {
                                        $data .= $month[2].',';
                                    }
                                }
                                echo substr($data, 0, -1);
                                ?>
                            ]
                        }
                    ]
                }

                window.onload = function(){
                    var ctx = document.getElementById("canvas").getContext("2d");
                    window.myLine = new Chart(ctx).Line(lineChartData, {
                        responsive: true
                    });
                }
            </script>
            <?php
            echo "<div class='linkTop'>";
                echo '<b>'.__($guid, 'Total Units Joined', 'Free Learning').'</b>: '.$countJoinedTotal.'<br/>';
                echo '<b>'.__($guid, 'Total Units Approved', 'Free Learning').'</b>: '.$countApprovedTotal.'<br/>';
            echo "</div>";
        }
    }
}
?>
