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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Free Learning/report_learningActivity.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"]), 'Free Learning') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Learning Activity', 'Free Learning') . "</div>" ;
	print "</div>" ;

	echo '<h2>';
    echo __($guid, 'Choose Time Frame', 'Free Learning');
    echo '</h2>';

    $timePeriod = null;
    if (isset($_GET['timePeriod'])) {
        $timePeriod = $_GET['timePeriod'];
    }

    ?>

	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">
			<tr>
				<td style='width: 275px'>
					<b><?php echo __($guid, 'Time Period') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="timePeriod">
						<?php
                        echo "<option value=''></option>";
						$selected = '';
						if ($timePeriod == 'Last 30 Days')
							$selected = 'selected';
						echo "<option $selected value='Last 30 Days'>".__($guid, 'Last 30 Days', 'Free Learning').'</option>';
						$selected = '';
						if ($timePeriod == 'Last 12 Months')
							$selected = 'selected';
						echo "<option $selected value='Last 12 Months'>".__($guid, 'Last 12 Months', 'Free Learning').'</option>';

						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_learningActivity.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($timePeriod != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $data = array();
			if ($timePeriod == 'Last 30 Days')
				$sql = 'SELECT timestampJoined, timestampCompleteApproved FROM freeLearningUnitStudent WHERE timestampCompleteApproved>=DATE_SUB(NOW(), INTERVAL 30 DAY) OR timestampJoined>=DATE_SUB(NOW(), INTERVAL 30 DAY)';
			else if ($timePeriod == 'Last 12 Months')
				$sql = 'SELECT timestampJoined, timestampCompleteApproved FROM freeLearningUnitStudent WHERE timestampCompleteApproved>=DATE_SUB(NOW(), INTERVAL 12 MONTH) OR timestampJoined>=DATE_SUB(NOW(), INTERVAL 12 MONTH)';
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
			echo "<p style='margin-top: 20px; margin-bottom: 5px'><b>".__($guid, 'Legend').'</b></p>';
			echo "<table class='noIntBorder' style='width: 100%;  border-spacing: 0; border-collapse: collapse;'>";
				echo '<tr>';
					echo "<td style='vertical-align: middle!important; height: 35px; width: 25%'></td>";
					echo "<td style='vertical-align: middle!important; height: 35px; width: 25%'></td>";
					echo "<td style='vertical-align: middle!important; height: 35px; width: 25px; border-right-width: 0px!important'>";
						echo "<div style='width: 25px; height: 25px; border: 2px solid rgba(220,220,220,1); background-color: rgba(220,220,220,0.8) '></div>";
					echo '</td>';
					echo "<td style='vertical-align: middle!important; height: 35px; width: 25%'>";
						echo '<b>'.__($guid, 'Units Joined', 'Free Learning').'</b>';
					echo '</td>';
					echo "<td style='vertical-align: middle!important; height: 35px; width: 25px; border-right-width: 0px!important'>";
						echo "<div style='width: 25px; height: 25px; border: 2px solid rgba(151,187,205,1); background-color: rgba(151,187,205,0.8) '></div>";
					echo '</td>';
					echo "<td style='vertical-align: middle!important; height: 35px; width: 25%'>";
						echo '<b>'.__($guid, 'Units Approved', 'Free Learning').'</b>';
					echo '</td>';
					echo "<td style='vertical-align: middle!important; height: 35px; width: 25px; border-right-width: 0px!important'></td>";
				echo '</tr>';
			echo '</table>';

			//PLOT DATA
			echo '<script type="text/javascript" src="'.$_SESSION[$guid]['absoluteURL'].'/lib/Chart.js/Chart.min.js"></script>';
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
									if (is_numeric(strpos($row['timestampCompleteApproved'], $m."-".$d))) {
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
									if (is_numeric(strpos($row['timestampCompleteApproved'], $Y."-".$m))) {
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
							fillColor : "rgba(220,220,220,0.2)",
							strokeColor : "rgba(220,220,220,1)",
							pointColor : "rgba(220,220,220,1)",
							pointStrokeColor : "#fff",
							pointHighlightFill : "#fff",
							pointHighlightStroke : "rgba(220,220,220,1)",
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
							fillColor : "rgba(151,187,205,0.2)",
							strokeColor : "rgba(151,187,205,1)",
							pointColor : "rgba(151,187,205,1)",
							pointStrokeColor : "#fff",
							pointHighlightFill : "#fff",
							pointHighlightStroke : "rgba(151,187,205,1)",
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
