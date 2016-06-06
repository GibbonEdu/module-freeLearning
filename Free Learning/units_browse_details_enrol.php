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

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check enrolment status
    $enrolCheckFail = false;
    try {
        $dataEnrol = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlEnrol = 'SELECT * FROM freeLearningUnitStudent WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID';
        $resultEnrol = $connection2->prepare($sqlEnrol);
        $resultEnrol->execute($dataEnrol);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
        $enrolCheckFail = true;
    }

    if ($enrolCheckFail == false) {
        if ($resultEnrol->rowCount()==0) { //ENROL NOW
            echo '<h3>';
            echo __($guid, 'Enrol Now');
            echo '</h3>';

            ?>
            <form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_browse_details_enrolProcess.php?address='.$_GET['q'] ?>">
                <table class='smallIntBorder' cellspacing='0' style="width: 100%">
                    <script type="text/javascript">
                        /* Subbmission type control */
                        $(document).ready(function(){
                            <?php

                            if ($schoolType == 'Physical' and $roleCategory == 'Student') {
                                $checked = '';
                                print '$(".schoolMentor").css("display","none");';
                                print 'gibbonPersonIDSchoolMentor.disable();' ;
                            }
                            else {
                                $checked = 'checked';
                                print '$(".class").css("display","none");';
                                print 'gibbonCourseClassID.disable();';
                            }
                            print '$(".externalMentor").css("display","none");';
                            print 'emailExternalMentor.disable();';
                            print 'nameExternalMentor.disable();';
                            ?>
                            $(".enrolmentMethod").click(function(){
                                if ($('input[name=enrolmentMethod]:checked').val()=="class" ) {
                                    $(".class").slideDown("fast", $(".class").css("display","table-row"));
                                    $(".schoolMentor").css("display","none");
                                    $(".externalMentor").css("display","none");
                                    gibbonCourseClassID.enable();
                                    gibbonPersonIDSchoolMentor.disable();
                                    emailExternalMentor.disable();
                                    nameExternalMentor.disable();
                                } else if ($('input[name=enrolmentMethod]:checked').val()=="schoolMentor" ) {
                                    $(".class").css("display","none");
                                    $(".schoolMentor").slideDown("fast", $(".schoolMentor").css("display","table-row"));
                                    $(".externalMentor").css("display","none");
                                    gibbonCourseClassID.disable();
                                    gibbonPersonIDSchoolMentor.enable();
                                    emailExternalMentor.disable();
                                    nameExternalMentor.disable();
                                } else {
                                    $(".class").css("display","none");
                                    $(".schoolMentor").css("display","none");
                                    $(".externalMentor").slideDown("fast", $(".externalMentor").css("display","table-row"));
                                    gibbonCourseClassID.disable();
                                    gibbonPersonIDSchoolMentor.disable();
                                    emailExternalMentor.enable();
                                    nameExternalMentor.enable();
                                }
                             });
                        });
                    </script>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Enrolment Method') ?> * </b><br/>
                            <span style="font-size: 90%"><i></i></span>
                        </td>
                        <td class="right">
                            <?php
                            if ($schoolType == 'Physical' and $roleCategory == 'Student') {
                                ?>
                                <?php echo __($guid, 'Timetable Class') ?> <input checked type="radio" name="enrolmentMethod" class="enrolmentMethod" value="class" /><br/>
                                <?php
                            }
                            ?>
                            <?php echo __($guid, 'School Mentor') ?> <input <?php echo $checked ?> type="radio" name="enrolmentMethod" class="enrolmentMethod" value="schoolMentor" /><br/>
                            <?php echo __($guid, 'External Mentor') ?> <input type="radio" name="enrolmentMethod" class="enrolmentMethod" value="externalMentor" /><br/>
                        </td>
                    </tr>
                    <tr class='class'>
                        <td>
                            <b><?php echo __($guid, 'Class') ?> *</b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'Which class are you enroling for?') ?></i></span>
                        </td>
                        <td class="right">
                            <?php
                            try {
                                $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlClasses = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' ORDER BY course, class";
                                $resultClasses = $connection2->prepare($sqlClasses);
                                $resultClasses->execute($dataClasses);
                            } catch (PDOException $e) {
                            }
                            ?>
                            <select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
                                <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                <?php
                                while ($rowClasses = $resultClasses->fetch()) {
                                    echo "<option value='".$rowClasses['gibbonCourseClassID']."'>".$rowClasses['course'].'.'.$rowClasses['class'].'</option>';
                                }
                                ?>
                            </select>
                            <script type="text/javascript">
                                var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
                                gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                             </script>
                        </td>
                    </tr>
                    <tr class='schoolMentor'>
                        <td>
                            <b><?php echo __($guid, 'School Mentor') ?> *</b><br/>
                            <span style="font-size: 90%"><i></i></span>
                        </td>
                        <td class="right">
                            <select name="gibbonPersonIDSchoolMentor" id="gibbonPersonIDSchoolMentor" style="width: 302px">
                                <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                <?php
                                    try {
                                        $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlSelect = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname
                                            FROM gibbonPerson
                                            JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                            WHERE status='Full'
                                                AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                                            ORDER BY surname, preferredName";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) { }
                                    while ($rowSelect = $resultSelect->fetch()) {
                                        echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
                                    }
                                ?>
                            </select>
                            <script type="text/javascript">
                                var gibbonPersonIDSchoolMentor=new LiveValidation('gibbonPersonIDSchoolMentor');
                                gibbonPersonIDSchoolMentor.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                            </script>
                        </td>
                    </tr>
                    <tr class='externalMentor'>
                        <td>
                            <b><?php echo __($guid, 'External Mentor Name') ?> *</b><br/>
                            <span style="font-size: 90%"><i></i></span>
                        </td>
                        <td class="right">
                            <input name="nameExternalMentor" id="nameExternalMentor" maxlength=255 value="" type="text" class="standardWidth">
        					<script type="text/javascript">
        						var nameExternalMentor=new LiveValidation('nameExternalMentor');
        						nameExternalMentor.add(Validate.Presence);
        					</script>
                        </td>
                    </tr>
                    <tr class='externalMentor'>
                        <td>
                            <b><?php echo __($guid, 'External Mentor Email') ?> *</b><br/>
                            <span style="font-size: 90%"><i></i></span>
                        </td>
                        <td class="right">
                            <input name="emailExternalMentor" id="emailExternalMentor" maxlength=255 value="" type="text" class="standardWidth">
        					<script type="text/javascript">
        						var emailExternalMentor=new LiveValidation('emailExternalMentor');
        						emailExternalMentor.add(Validate.Presence);
                                emailExternalMentor.add(Validate.Email);
        					</script>
                        </td>
                    </tr>
                    <tr>
                        <td style='width: 275px'>
                            <b><?php echo __($guid, 'Grouping') ?> *</b><br/>
                            <span style="font-size: 90%"><i><?php echo __($guid, 'How do you want to study this unit?') ?></i></span>
                        </td>
                        <td class="right">
                            <select name="grouping" id="grouping" style="width: 302px">
                                <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                <?php
                                $group = false;
                                $extraSlots = 0;
                                if (strpos($row['grouping'], 'Individual') !== false) {
                                    echo '<option value="Individual">Individual</option>';
                                }
                                if (strpos($row['grouping'], 'Pairs') !== false) {
                                    echo '<option value="Pairs">Pair</option>';
                                    $group = true;
                                    $extraSlots = 1;
                                }
                                if (strpos($row['grouping'], 'Threes') !== false) {
                                    echo '<option value="Threes">Three</option>';
                                    $group = true;
                                    $extraSlots = 2;
                                }
                                if (strpos($row['grouping'], 'Fours') !== false) {
                                    echo '<option value="Fours">Four</option>';
                                    $group = true;
                                    $extraSlots = 3;
                                }
                                if (strpos($row['grouping'], 'Fives') !== false) {
                                    echo '<option value="Fives">Five</option>';
                                    $group = true;
                                    $extraSlots = 4;
                                }
                                ?>
                            </select>
                            <script type="text/javascript">
                                var grouping=new LiveValidation('grouping');
                                grouping.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                             </script>
                        </td>
                    </tr>
                    <?php
                    if ($group) {
                        //Get array of possible collaborators, dependent on role category
                        $students = array();
                        $studentCount = 0;
                        if ($roleCategory == 'Student') {
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) { }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $students[$studentCount] = "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.$rowSelect['rollGroup'].')</option>';
                                ++$studentCount;
                            }
                        } elseif ($roleCategory == 'Staff') {
                            try {
                                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlSelect = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname
                                    FROM gibbonPerson
                                    JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                    WHERE status='Full'
                                        AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                                    ORDER BY surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) { }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $students[$studentCount] = "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
                                ++$studentCount;
                            }
                        } elseif ($roleCategory == 'Parent') {
                            try {
                                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sqlSelect = "SELECT DISTINCT gibbonPerson.gibbonPersonID, preferredName, surname
                                    FROM gibbonPerson
                                    JOIN gibbonRole ON (gibbonRole.gibbonRoleID LIKE concat( '%', gibbonPerson.gibbonRoleIDAll, '%' ) AND category='Parent')
                                    WHERE status='Full'
                                        AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID
                                    ORDER BY surname, preferredName";
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) { }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $students[$studentCount] = "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).'</option>';
                                ++$studentCount;
                            }
                        }

                        //Controls for lists
                        ?>
                        <script type='text/javascript'>
                            $(document).ready(function(){
                            $('tr.collaborator').css('display','none');
                            <?php
                            for ($i = 1; $i <= $extraSlots; ++$i) {
                                echo 'collaborator'.$i.'.disable();';
                            }
                            ?>
                            $('#grouping').change(function(){
                                if ($('select#grouping option:selected').val()=='Individual') {
                                    $('#trCollaborator1').css('display','none');
                                    collaborator1.disable() ;
                                    $('#trCollaborator2').css('display','none');
                                    collaborator2.disable() ;
                                    $('#trCollaborator3').css('display','none');
                                    collaborator3.disable() ;
                                    $('#trCollaborator4').css('display','none');
                                    collaborator4.disable() ;
                                }
                                else if ($('select#grouping option:selected').val()=='Pairs') {
                                    $('#trCollaborator1').css('display','table-row');
                                    collaborator1.enable() ;
                                    $('#trCollaborator2').css('display','none');
                                    collaborator2.disable() ;
                                    $('#trCollaborator3').css('display','none');
                                    collaborator3.disable() ;
                                    $('#trCollaborator4').css('display','none');
                                    collaborator4.disable() ;
                                }
                                else if ($('select#grouping option:selected').val()=='Threes') {
                                    $('#trCollaborator1').css('display','table-row');
                                    collaborator1.enable() ;
                                    $('#trCollaborator2').css('display','table-row');
                                    collaborator2.enable() ;
                                    $('#trCollaborator3').css('display','none');
                                    collaborator3.disable() ;
                                    $('#trCollaborator4').css('display','none');
                                    collaborator4.disable() ;
                                }
                                else if ($('select#grouping option:selected').val()=='Fours') {
                                    $('#trCollaborator1').css('display','table-row');
                                    collaborator1.enable() ;
                                    $('#trCollaborator2').css('display','table-row');
                                    collaborator2.enable() ;
                                    $('#trCollaborator3').css('display','table-row');
                                    collaborator3.enable() ;
                                    $('#trCollaborator4').css('display','none');
                                    collaborator4.disable() ;
                                }
                                else if ($('select#grouping option:selected').val()=='Fives') {
                                    $('#trCollaborator1').css('display','table-row');
                                    collaborator1.enable() ;
                                    $('#trCollaborator2').css('display','table-row');
                                    collaborator2.enable() ;
                                    $('#trCollaborator3').css('display','table-row');
                                    collaborator3.enable() ;
                                    $('#trCollaborator4').css('display','table-row');
                                    collaborator4.enable() ;
                                }
                                });
                            });
                        </script>

                        <?php
                        //Output select lists
                        for ($i = 1; $i <= $extraSlots; ++$i) {
                            ?>
                            <tr class='collaborator' id='<?php echo "trCollaborator$i" ?>'>
                                <td style='width: 275px'>
                                    <b><?php echo sprintf(__($guid, 'Collaborator %1$s'), $i) ?> *</b><br/>
                                </td>
                                <td class="right">
                                    <select name="collaborators[]" id="collaborator<?php echo $i ?>" style="width: 302px">
                                        <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                        <?php
                                        foreach ($students as $student) {
                                            echo $student;
                                        }
                                        ?>
                                    </select>
                                    <script type="text/javascript">
                                        var collaborator<?php echo $i ?>=new LiveValidation('collaborator<?php echo $i ?>');
                                        collaborator<?php echo $i ?>.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                                    </script>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    <tr>
                        <td class="right" colspan=2>
                            <input type="hidden" name="freeLearningUnitID" value="<?php echo $freeLearningUnitID ?>">
                            <input type="submit" id="submit" value="Enrol Now">
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
        if ($resultEnrol->rowCount() == 1) { //Already enroled, deal with different statuses
            $rowEnrol = $resultEnrol->fetch();
            if ($rowEnrol['status'] == 'Current' or $rowEnrol['status'] == 'Evidence Not Approved') { //Currently enroled, allow to set status to complete and submit feedback...or previously submitted evidence not accepted
                echo '<h4>';
                echo __($guid, 'Currently Enroled');
                echo '</h4>';
                if ($schoolType == 'Physical') {
                    if ($rowEnrol['status'] == 'Current') {
                        echo '<p>';
                        echo sprintf(__($guid, 'You are currently enroled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your class teacher or mentor will be notified, and will approve your unit completion in due course.'), $row['name']);
                        echo '</p>';
                    } elseif ($rowEnrol['status'] == 'Evidence Not Approved') {
                        echo "<div class='warning'>";
                        echo __($guid, 'Your evidence has not been approved. Please read the feedback below, adjust your evidence, and submit again:').'<br/><br/>';
                        echo '<b>'.$rowEnrol['commentApproval'].'</b>';
                        echo '</div>';
                    }
                } else {
                    if ($rowEnrol['status'] == 'Current') {
                        echo '<p>';
                        echo sprintf(__($guid, 'You are currently enroled in %1$s: when you are ready, use the form to submit evidence that you have completed the unit. Your unit completion will be automatically approved, and you can move onto the next unit.'), $row['name']);
                        echo '</p>';
                    }
                }

                ?>
                <form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/units_browse_details_completePendingProcess.php?address='.$_GET['q'] ?>" enctype="multipart/form-data">
                    <table class='smallIntBorder' cellspacing='0' style="width: 100%">
                        <tr>
                            <td>
                                <b><?php echo __($guid, 'Status') ?> *</b><br/>
                                <span style="font-size: 90%"><i><?php echo __($guid, 'This value cannot be changed.') ?></i></span>
                            </td>
                            <td class="right">
                                <input readonly style='width: 300px' type='text' value='Complete - Pending' />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <b><?php echo __($guid, 'Comment') ?> *</b><br/>
                                <span style="font-size: 90%"><i>
                                    <?php
                                    echo __($guid, 'Leave a brief reflective comment on this unit<br/>and what you learned.');
                                    if ($rowEnrol['status'] == 'Evidence Not Approved') {
                                        echo '<br/><br/>'.__($guid, 'Your previous comment is shown here, for you to edit.');
                                    }
                                    ?>
                                </i></span>
                            </td>
                            <td class="right">
                                <script type='text/javascript'>
                                    $(document).ready(function(){
                                        autosize($('textarea'));
                                    });
                                </script>
                                <textarea name="commentStudent" id="commentStudent" rows=8 style="width: 300px"><?php
                                if ($rowEnrol['status'] == 'Evidence Not Approved') {
                                    echo $rowEnrol['commentStudent'];
                                }
                                ?></textarea>
                                <script type="text/javascript">
                                    var commentStudent=new LiveValidation('commentStudent');
                                    commentStudent.add(Validate.Presence);
                                </script>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <b><?php echo __($guid, 'Type') ?> *</b><br/>
                            </td>
                            <td class="right">
                                <input checked type="radio" id="type" name="type" class="type" value="Link" /> Link
                                <input type="radio" id="type" name="type" class="type" value="File" /> File
                            </td>
                        </tr>
                        <script type="text/javascript">
                            /* Subbmission type control */
                            $(document).ready(function(){
                                $("#fileRow").css("display","none");
                                $("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row"));

                                $(".type").click(function(){
                                    if ($('input[name=type]:checked').val()=="Link" ) {
                                        $("#fileRow").css("display","none");
                                        $("#linkRow").slideDown("fast", $("#linkRow").css("display","table-row"));
                                    } else {
                                        $("#linkRow").css("display","none");
                                        $("#fileRow").slideDown("fast", $("#fileRow").css("display","table-row"));
                                    }
                                 });
                            });
                        </script>

                        <tr id="fileRow">
                            <td>
                                <b><?php echo __($guid, 'Submit File') ?> *</b><br/>
                            </td>
                            <td class="right">
                                <input type="file" name="file" id="file"><br/><br/>
                                <?php
                                echo getMaxUpload();

                                //Get list of acceptable file extensions
                                try {
                                    $dataExt = array();
                                    $sqlExt = 'SELECT * FROM gibbonFileExtension';
                                    $resultExt = $connection2->prepare($sqlExt);
                                    $resultExt->execute($dataExt);
                                } catch (PDOException $e) {
                                }
                                $ext = '';
                                while ($rowExt = $resultExt->fetch()) {
                                    $ext = $ext."'.".$rowExt['extension']."',";
                                }
                                ?>

                                <script type="text/javascript">
                                    var file=new LiveValidation('file');
                                    file.add( Validate.Inclusion, { within: [<?php echo $ext; ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
                                </script>
                            </td>
                        </tr>
                        <tr id="linkRow">
                            <td>
                                <b><?php echo __($guid, 'Submit Link') ?> *</b><br/>
                            </td>
                            <td class="right">
                                <input name="link" id="link" maxlength=255 value="" type="text" style="width: 300px">
                                <script type="text/javascript">
                                    var link=new LiveValidation('link');
                                    link.add( Validate.Inclusion, { within: ['http://', 'https://'], failureMessage: "Address must start with http:// or https://", partialMatch: true } );
                                </script>
                            </td>
                        </tr>
                        <tr>
                            <td class="right" colspan=2>
                                <input type="hidden" name="freeLearningUnitStudentID" value="<?php echo $rowEnrol['freeLearningUnitStudentID'] ?>">
                                <input type="hidden" name="freeLearningUnitID" value="<?php echo $freeLearningUnitID ?>">
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

            } elseif ($rowEnrol['status'] == 'Complete - Pending') { //Waiting for teacher feedback
                echo '<h4>';
                echo __($guid, 'Complete - Pending Approval');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'Your evidence, shown below, has been submitted to your teacher/mentor for approval. This screen will show a teacher comment, once approval has been given.');
                echo '</p>';
                ?>
                <table class='smallIntBorder' cellspacing='0' style="width: 100%">
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Status') ?></b><br/>
                        </td>
                        <td class="right">
                            <input readonly style='width: 300px' type='text' value='Complete - Pending' />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Evidence Type') ?></b><br/>
                        </td>
                        <td class="right">
                            <input readonly style='width: 300px' type='text' value='<?php echo $rowEnrol['evidenceType'] ?>' />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Evidence') ?></b><br/>
                        </td>
                        <td class="right">
                            <div style='width: 300px; float: right; text-align: left; font-size: 115%; height: 24px; padding-top: 5px'>
                                <?php
                                if ($rowEnrol['evidenceType'] == 'Link') {
                                    echo "<a target='_blank' href='".$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
                                } else {
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php
                echo '<h4>';
                echo __($guid, 'Student Comment');
                echo '</h4>';
                echo '<p>';
                echo $rowEnrol['commentStudent'];
                echo '</p>';
            } elseif ($rowEnrol['status'] == 'Complete - Approved') { //Complete, show status and feedback from teacher.
                if ($schoolType == 'Physical') {
                    echo '<h4>';
                    echo __($guid, 'Complete - Approved');
                    echo '</h4>';
                    echo '<p>';
                    echo __($guid, 'Congralutations! Your evidence, shown below, has been accepted and approved by your teacher(s), and so you have successfully completed this unit. Please look below for your teacher\'s comment.');
                    echo '</p>';
                } else {
                    echo '<h4>';
                    echo __($guid, 'Complete');
                    echo '</h4>';
                    echo '<p>';
                    echo __($guid, 'Congralutations! You have submitted your evidence, shown below, and so the unit is complete. Feel free to move on to another unit.');
                    echo '</p>';
                }
                ?>
                <table class='smallIntBorder' cellspacing='0' style="width: 100%">
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Status') ?></b><br/>
                        </td>
                        <td class="right">
                            <input readonly style='width: 300px' type='text' value='Complete - Approved' />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Evidence Type') ?></b><br/>
                        </td>
                        <td class="right">
                            <input readonly style='width: 300px' type='text' value='<?php echo $rowEnrol['evidenceType'] ?>' />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b><?php echo __($guid, 'Evidence') ?></b><br/>
                        </td>
                        <td class="right">
                            <div style='width: 300px; float: right; text-align: left; font-size: 115%; height: 24px; padding-top: 5px'>
                                <?php
                                if ($rowEnrol['evidenceType'] == 'Link') {
                                    echo "<a target='_blank' href='".$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
                                } else {
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEnrol['evidenceLocation']."'>".__($guid, 'View').'</>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php
                if ($schoolType == 'Physical') {
                    echo '<h4>';
                    echo __($guid, 'Teacher Comment');
                    echo '</h4>';
                    echo '<p>';
                    echo $rowEnrol['commentApproval'];
                    echo '</p>';
                }

                echo '<h4>';
                echo __($guid, 'Student Comment');
                echo '</h4>';
                echo '<p>';
                echo $rowEnrol['commentStudent'];
                echo '</p>';
            } elseif ($rowEnrol['status'] == 'Exempt') { //Exempt, let student know
                echo '<h4>';
                echo __($guid, 'Exempt');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'You are exempt from completing this unit, which means you get the status of completion, without needing to submit any evidence.');
                echo '</p>';
            }
        }
    }
}
?>
