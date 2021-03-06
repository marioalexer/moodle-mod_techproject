<?php

require_once($CFG->libdir.'/formslib.php');

class Requirement_Form extends moodleform {

	var $mode;
	var $project;
	var $current;
	var $descriptionoptions;

	function __construct($action, &$project, $mode, $reqid){
		global $DB;
		
		$this->mode = $mode;
		$this->project = $project;
		if ($reqid){
			$this->current = $DB->get_record('techproject_requirement', array('id' => $reqid));
		}
		parent::__construct($action);
	}
    	
	function definition(){
		global $COURSE, $DB;

    	$mform = $this->_form;

    	$modcontext = context_module::instance($this->project->cmid);

		$maxfiles = 99;                // TODO: add some setting
		$maxbytes = $COURSE->maxbytes; // TODO: add some setting	
		$this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);
    	
    	$currentGroup = 0 + groups_get_course_group($COURSE);

    	$mform->addElement('hidden', 'id');
    	$mform->addElement('hidden', 'fatherid');
    	$mform->addElement('hidden', 'reqid');
    	$mform->addElement('hidden', 'work');
    	$mform->setDefault('work', $this->mode);
    	
    	$mform->addElement('text', 'abstract', get_string('requirementtitle', 'techproject'), array('size' => "100%"));

        $strengthes = techproject_get_options('strength', $this->project->id);
        $strengthoptions = array();
        foreach($strengthes as $aStrength){
            $strengthoptions[$aStrength->code] = '['. $aStrength->code . '] ' . $aStrength->label;
        }
    	$mform->addElement('select', 'strength', get_string('strength', 'techproject'), $strengthoptions);
		$mform->addHelpButton('strength', 'strength', 'techproject');

        $heavynesses = techproject_get_options('heavyness', $this->project->id);
        $heavynessoptions = array();
        foreach($heavynesses as $aHeavyness){
            $heavynessoptions[$aHeavyness->code] = '['. $aHeavyness->code . '] ' . $aHeavyness->label;
        }
    	$mform->addElement('select', 'heavyness', get_string('heavyness', 'techproject'), $heavynessoptions);
		$mform->addHelpButton('heavyness', 'heavyness', 'techproject');

    	$mform->addElement('editor', 'description_editor', get_string('description', 'techproject'), null, $this->descriptionoptions);		    	

		if ($this->project->projectusesspecs && $this->mode == 'update'){
	        $specifications = techproject_get_tree_options('techproject_specification', $this->project->id, $currentGroup);
	        $selection = $DB->get_records_select_menu('techproject_spec_to_req', "reqid = {$this->current->id}", array(''), 'specid, reqid');
	        $reqs = array();
	        if (!empty($specifications)){
	            foreach($specifications as $aSpecification){
	                $linkedspecs[$aSpecification->id] = $aSpecification->ordering .' - '.shorten_text(format_string($aSpecification->abstract), 90);
	            }
	        }
			$select = &$mform->addElement('select', 'spectoreq', get_string('assignedspecs', 'techproject'), $linkedspecs, array('size' => 8));
			$select->setMultiple(true);
			$mform->addHelpButton('spectoreq', 'spec_to_req', 'techproject');
		}
		
		$this->add_action_buttons(true);
    }

    function set_data($defaults){

		$context = context_module::instance($this->project->cmid);

		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_techproject', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
		$defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_techproject', 'requirementdescription', $defaults->id);
		$defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

    	parent::set_data($defaults);
    }
}
