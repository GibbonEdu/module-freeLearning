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

echo "<div class='trail'>";
echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Free Learning Mentor Feedback', 'Free Learning').'</div>';
echo '</div>';

$block = false;
if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully. Thank you for your time. The learner you are helping has been notified of your positive feedback.', 'success1' => 'Your request was completed successfully. Thank you for your time. The learner you are helping has been notified of your feedback and will resubmit their work in due course, at which point your input will be requested once again: in the meanwhile, no further action is required on your part.'));
    if ($_GET['return'] == 'success0' or $_GET['return'] == 'success1') {
        $block = true;
    }
}

if (!$block) {
    //Get params
    $freeLearningUnitStudentID = null;
    if (isset($_GET['freeLearningUnitStudentID'])) {
        $freeLearningUnitStudentID = $_GET['freeLearningUnitStudentID'];
    }
    $confirmationKey = null;
    if (isset($_GET['confirmationKey'])) {
        $confirmationKey = $_GET['confirmationKey'];
    }

    if ($freeLearningUnitStudentID == '' or $confirmationKey == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        //Check student & confirmation key
        try {
            $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'confirmationKey' => $confirmationKey) ;
            $sql = 'SELECT freeLearningUnitStudent.*, freeLearningUnit.name AS unit, surname, preferredName
                FROM freeLearningUnitStudent
                    JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                    JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID
                    AND confirmationKey=:confirmationKey
                    AND freeLearningUnitStudent.status=\'Complete - Pending\'';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed due to a database error.');
            echo '</div>';
        }

        if ($result->rowCount()!=1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        }
        else {
            $row = $result->fetch() ;
            $freeLearningUnitID = $row['freeLearningUnitID'];

            echo '<p>';
                echo __($guid, 'This screen allows you to give feedback on the submitted work. Immediately below you can browse the contents of the unit, which will tell you what has been learned. Following that you can view and feedback on the submitted work.', 'Free Learning');
            echo '</p>';

            //Show unit content
            echo '<h3>';
                echo __($guid, 'Unit Content', 'Free Learning');
            echo '</h3>';
            try {
                $dataBlocks = array('freeLearningUnitID' => $row['freeLearningUnitID']);
                $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';
                $resultBlocks = $connection2->prepare($sqlBlocks);
                $resultBlocks->execute($dataBlocks);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultBlocks->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                while ($rowBlocks = $resultBlocks->fetch()) {
                    echo displayBlockContent($guid, $connection2, $rowBlocks['title'], $rowBlocks['type'], $rowBlocks['length'], $rowBlocks['contents'], $rowBlocks['teachersNotes']);
                }
            }

            //Show feedback form
            echo '<h3>';
                echo __($guid, 'Feedback Form', 'Free Learning');
            echo '</h3>';
            echo '<p>';
            echo __($guid, 'Use the table below to indicate student completion, based on the evidence shown on the previous page. Leave the student a comment in way of feedback.', 'Free Learning');
            echo '</p>';
            ?>
            <form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_mentor_approvalProcess.php' ?>">
                <table class='smallIntBorder' cellspacing='0' style="width: 100%">
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Student') ?> *</b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'This value cannot be changed.') ?></i></span>
                        </td>
                        <td class="right">
                            <?php echo "<input readonly value='".formatName('', $row['preferredName'], $row['surname'], 'Student', false)."' type='text' style='width: 300px'>";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Status') ?> *</b><br/>
                        </td>
                        <td class="right">
                            <select style="width: 302px" name="status" id="status">
                                <option <?php if ($row['status'] == 'Complete - Approved') { echo 'selected'; } ?> value='Complete - Approved'>Complete - Approved</option>
                                <option <?php if ($row['status'] == 'Evidence Not Approved') { echo 'selected'; } ?> value='Evidence Not Approved'>Evidence Not Approved</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Submission', 'Free Learning') ?> *</b><br/>
                        </td>
                        <td class="right">
                            <?php
                            if ($row['evidenceLocation'] != '') {
                                if ($row['evidenceType'] == 'Link') {
                                    echo "<a target='_blank' href='".$row['evidenceLocation']."'>".__($guid, 'View Student Work', 'Free Learning').'</a>';
                                } else {
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['evidenceLocation']."'>".__($guid, 'View Student Work', 'Free Learning').'</a>';
                                }
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2>
                            <b><?php echo __($guid, 'Student Comment', 'Free Learning') ?> *</b><br/>
                            <p>
                                <?php
                                    echo $row['commentStudent'];
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td colspan=2>
                            <b><?php echo __($guid, 'Mentor Comment', 'Free Learning') ?> *</b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'Leave a comment on the student\'s progress.', 'Free Learning') ?></i></span>
                            <?php echo getEditor($guid,  true, 'commentApproval', $row['commentApproval'], 15, false, true) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="right" colspan=2>
                            <input type="hidden" name="freeLearningUnitStudentID" value="<?php echo $row['freeLearningUnitStudentID'] ?>">
                            <input type="hidden" name="freeLearningUnitID" value="<?php echo $freeLearningUnitID ?>">
                            <input type="hidden" name="confirmationKey" value="<?php echo $confirmationKey ?>">
                            <input type="submit" id="submit" value="Submit">
                        </td>
                    </tr>
                    <tr>
                        <td class="right" colspan=2>
                            <span style="font-size: 90%"><i>* <?php echo __($guid, 'denotes a required field'); ?></i></span>
                        </td>
                    </tr>
                </table>
            </form>
            <?php
        }
    }
}
?>
