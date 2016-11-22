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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/settings_manage') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']), 'Free Learning')."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Settings', 'Free Learning').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/settings_manageProcess.php' ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">
            <tr class='break'>
                <td colspan=2>
                	<h3><?php echo __($guid, 'General Settings', 'Free Learning') ?></h3>
                </td>
            </tr>
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='schoolType'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description']); } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" style="width: 302px">
						<?php
                        $selected = '';
						if ($row['value'] == 'Physical') {
							$selected = 'selected';
						}
						echo "<option $selected value='Physical'>Physical</option>";
						$selected = '';
						if ($row['value'] == 'Online') {
							$selected = 'selected';
						}
						echo "<option $selected value='Online'>Online</option>";
						?>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='publicUnits'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay'], 'Free Learning') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Free Learning'); } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" style="width: 302px">
						<?php
                        $selected = '';
						if ($row['value'] == 'Y') {
							$selected = 'selected';
						}
						echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
						$selected = '';
						if ($row['value'] == 'N') {
							$selected = 'selected';
						}
						echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
						?>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='learningAreaRestriction'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay'], 'Free Learning') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Free Learning'); } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" style="width: 302px">
						<?php
                        $selected = '';
						if ($row['value'] == 'Y') {
							$selected = 'selected';
						}
						echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
						$selected = '';
						if ($row['value'] == 'N') {
							$selected = 'selected';
						}
						echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
						?>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='difficultyOptions'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay'], 'Free Learning') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Free Learning'); } ?></i></span>
				</td>
				<td stclass="right">
					<input name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" maxlength=50 value="<?php echo htmlPrep($row['value']) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='unitOutlineTemplate'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay'], 'Free Learning') ?></b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Free Learning'); } ?></i></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" rows=8 style="width: 300px"><?php echo htmlPrep($row['value']) ?></textarea>
				</td>
			</tr>
            <tr class='break'>
                <td colspan=2>
                	<h3><?php echo __($guid, 'Enrolment Settings', 'Free Learning') ?></h3>
                </td>
            </tr>
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='enableClassEnrolment'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay'], 'Free Learning') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Free Learning'); } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" style="width: 302px">
						<?php
                        $selected = '';
						if ($row['value'] == 'Y') {
							$selected = 'selected';
						}
						echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
						$selected = '';
						if ($row['value'] == 'N') {
							$selected = 'selected';
						}
						echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
						?>
					</select>
				</td>
			</tr>
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='enableSchoolMentorEnrolment'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay'], 'Free Learning') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Free Learning'); } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" style="width: 302px">
						<?php
                        $selected = '';
						if ($row['value'] == 'Y') {
							$selected = 'selected';
						}
						echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
						$selected = '';
						if ($row['value'] == 'N') {
							$selected = 'selected';
						}
						echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
						?>
					</select>
				</td>
			</tr>
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Free Learning' AND name='enableExternalMentorEnrolment'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
				$row = $result->fetch();
				?>
				<td>
					<b><?php echo __($guid, $row['nameDisplay'], 'Free Learning') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Free Learning'); } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" style="width: 302px">
						<?php
                        $selected = '';
						if ($row['value'] == 'Y') {
							$selected = 'selected';
						}
						echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
						$selected = '';
						if ($row['value'] == 'N') {
							$selected = 'selected';
						}
						echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
						?>
					</select>
				</td>
			</tr>
            <tr>
				<td>
					<span style="font-size: 90%"><i>* <?php echo __($guid, 'denotes a required field'); ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
<?php

}
?>
