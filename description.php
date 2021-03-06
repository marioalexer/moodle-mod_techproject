<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Prints a desciption of the project (heading).
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */
    
    include_once 'forms/form_description.class.php';
    
    $mform = new Description_Form($url, $project, $work);

    if ($work == 'doexport'){
    	    $heading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentGroupId));
    	    $projects[$heading->projectid] = $heading;
    	    include_once "xmllib.php";
    	    $xml = recordstoxml($projects, 'project', '', true, null);
    	    $escaped = str_replace('<', '&lt;', $xml);
    	    $escaped = str_replace('>', '&gt;', $escaped);
    	    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    	    echo $OUTPUT->box("<pre>$escaped</pre>");
            add_to_log($course->id, 'techproject', 'readdescription', "view.php?id={$cm->id}&amp;view=description&amp;group={$currentGroupId}", 'export', $cm->id);
            echo $OUTPUT->continue_button("view.php?view=description&amp;id=$cm->id");
            return;
    }

/// Header editing form ********************************************************
if ($work == 'edit'){

	if ($mform->is_cancelled()){
        redirect($url);
	}

    if ($heading = $mform->get_data()){
    	
        $heading->abstract = $heading->abstract_editor['text'];
        $heading->rationale = $heading->rationale_editor['text'];
        $heading->environment = $heading->environment_editor['text'];

		$abstract_draftid_editor = file_get_submitted_draft_itemid('abstract_editor');
		$heading->abstract = file_save_draft_area_files($abstract_draftid_editor, $context->id, 'mod_techproject', 'abstract', $heading->id, array('subdirs' => true), $heading->abstract);

		$rationale_draftid_editor = file_get_submitted_draft_itemid('rationale_editor');
		$heading->rationale = file_save_draft_area_files($rationale_draftid_editor, $context->id, 'mod_techproject', 'rationale', $heading->id, array('subdirs' => true), $heading->rationale);

		$environment_draftid_editor = file_get_submitted_draft_itemid('environment_editor');
		$heading->environment = file_save_draft_area_files($environment_draftid_editor, $context->id, 'mod_techproject', 'environment', $heading->id, array('subdirs' => true), $heading->environment);

        $heading->id = $heading->headingid;
        $heading->projectid = $project->id;
        $heading->groupid = $currentGroupId;
        $heading->title = $heading->title;
        $heading->organisation = $heading->organisation;
        $heading->department = $heading->department;

	    $heading = file_postupdate_standard_editor($heading, 'abstract', $mform->editoroptions, $context, 'mod_techproject', 'absract', $heading->id);
	    $heading = file_postupdate_standard_editor($heading, 'rationale', $mform->editoroptions, $context, 'mod_techproject', 'rationale', $heading->id);
	    $heading = file_postupdate_standard_editor($heading, 'environment', $mform->editoroptions, $context, 'mod_techproject', 'environment', $heading->id);

        $DB->update_record('techproject_heading', $heading);
        redirect($url);
    }

    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentGroupId));

	// Start ouptuting here
	echo $pagebuffer;
	echo $OUTPUT->heading(get_string('editheading', 'techproject'));
	$projectheading->headingid = $projectheading->id;
	$projectheading->id = $cm->id;
	$projectheading->format = FORMAT_HTML;
	$projectheading->projectid = $project->id;

	$mform->set_data($projectheading);
	$mform->display();

} else {
	// Start ouptuting here
	echo $pagebuffer;
    techproject_print_heading($project, $currentGroupId);
    echo "<center>";
    if ($USER->editmode == 'on') {
        echo "<br/><a href=\"view.php?work=edit&amp;id={$cm->id}\" >".get_string('editheading','techproject')."</a>";
        echo " - <a href=\"view.php?work=doexport&amp;id={$cm->id}\" >".get_string('exportheadingtoXML','techproject')."</a>";
    }
    echo "<br/><a href=\"xmlview.php?id={$cm->id}\" target=\"_blank\">".get_string('gettheprojectfulldocument','techproject')."</a>";
    echo "</center>";
}