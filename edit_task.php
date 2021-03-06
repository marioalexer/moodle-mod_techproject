<?php

/**
*
*
*
*/

	require_once($CFG->dirroot."/mod/techproject/forms/form_task.class.php");
	$PAGE->requires->js('/mod/techproject/js/js.js');
	
	$taskid = optional_param('taskid', '', PARAM_INT);
	
	$mode = ($taskid) ? 'update' : 'add' ;
	
	$url = $CFG->wwwroot.'/mod/techproject/view.php?id='.$id.'#node'.$taskid;
	$project->cm = $cm;
	$mform = new Task_Form($url, $project, $mode, $taskid);
	
	if ($mform->is_cancelled()){
		redirect($url);
	}
	
	if ($data = $mform->get_data()){
		$data->groupid = $currentGroupId;
		$data->projectid = $project->id;	
		$data->userid = $USER->id;
		$data->modified = time();
		$data->descriptionformat = $data->description_editor['format'];
		$data->description = $data->description_editor['text'];
		$data->lastuserid = $USER->id;

		if ($data->taskstart) $data->taskstartenable = 1;
		if ($data->taskend) $data->taskendenable = 1;

		// editors pre save processing
		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$data->description = file_save_draft_area_files($draftid_editor, $context->id, 'mod_techproject', 'taskdescription', $data->id, array('subdirs' => true), $data->description);
	    $data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_techproject', 'taskdescription', $data->id);

		if ($data->taskid) {
			$data->id = $data->taskid; // id is course module id
   			$oldAssigneeId = $DB->get_field('techproject_task', 'assignee', array('id' => $data->id));
			$DB->update_record('techproject_task', $data);
            add_to_log($course->id, 'techproject', 'changetask', "view.php?id=$cm->id&view=tasks&group={$currentGroupId}", 'update', $cm->id);

    		$tasktospec = optional_param_array('taskospec', null, PARAM_INT);
    		if (count($tasktospec) > 0){
    		    // removes previous mapping
    		    $DB->delete_records('techproject_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'taskid' => $data->id));
    		    // stores new mapping
        		foreach($tasktospec as $aSpec){
        		    $amap->id = 0;
        		    $amap->projectid = $project->id;
        		    $amap->groupid = $currentGroupId;
        		    $amap->specid = $aSpec;
        		    $amap->taskid = $data->id;
        		    $res = $DB->insert_record('techproject_task_to_spec', $amap);
        		}
        	}

			// todo a function ? 
    		$mapped = optional_param_array('tasktodeliv', null, PARAM_INT);
    		if (count($mapped) > 0){
    		    // removes previous mapping
    		    $DB->delete_records('techproject_task_to_deliv', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'taskid' => $data->id));
    		    // stores new mapping
        		foreach($mapped as $mappedid){
        		    $amap->id = 0;
        		    $amap->projectid = $project->id;
        		    $amap->groupid = $currentGroupId;
        		    $amap->delivid = $mappedid;
        		    $amap->taskid = $data->id;
        		    $res = $DB->insert_record('techproject_task_to_deliv', $amap);
        		}
        	}

    		$mapped = optional_param_array('taskdependency', null, PARAM_INT);
		    // removes previous mapping
		    $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'slave' => $data->id));
		    $DB->delete_records('techproject_task_dependency', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'master' => $data->id));
    		if (count($mapped) > 0){
    		    // stores new mapping
        		foreach($mapped as $mappedid){
        		    $amap->id = 0;
        		    $amap->projectid = $project->id;
        		    $amap->groupid = $currentGroupId;
        		    $amap->master = $mappedid;
        		    $amap->slave = $data->id;
        		    $res = $DB->insert_record('techproject_task_dependency', $amap);
        		}

           		// if notifications allowed and previous assignee exists (and is not the new assignee) notify previous assignee
           		if( $project->allownotifications && !empty($oldAssigneeId) && $data->assignee != $oldAssigneeId){
                    techproject_notify_task_unassign($project, $data, $oldAssigneeId, $currentGroupId);
               	}
        	}
		} else {
			$data->created = time();
    		$data->ordering = techproject_tree_get_max_ordering($project->id, $currentGroupId, 'techproject_task', true, $data->fatherid) + 1;
			unset($data->id); // id is course module id

			$data->id = $DB->insert_record('techproject_task', $data);

        	add_to_log($course->id, 'techproject', 'addtask', "view.php?id=$cm->id&view=tasks&group={$currentGroupId}", 'add', $cm->id);

       		if( $project->allownotifications){
       		    techproject_notify_new_task($project, $cm->id, $data, $currentGroupId);
           	}
		}

   		// if subtask, force dependency upon father
   		if ($data->fatherid != 0){
            $aDependency->id = 0;
   		    $aDependency->projectid = $project->id;
   		    $aDependency->groupid = $currentGroupId;
   		    $aDependency->slave = $data->fatherid;
   		    $aDependency->master = $data->id;
   		    $DB->insert_record('techproject_task_dependency', $aDependency);
   		}
   		// if subtask, calculate branch propagation
   		if ($data->fatherid != 0){
   		    techproject_tree_propagate_up('techproject_task', 'done', $data->id, '~');
   		    techproject_tree_propagate_up('techproject_task', 'planned', $data->id, '+');
   		    techproject_tree_propagate_up('techproject_task', 'used', $data->id, '+');
   		    techproject_tree_propagate_up('techproject_task', 'quoted', $data->id, '+');
   		    techproject_tree_propagate_up('techproject_task', 'spent', $data->id, '+');
   		}

   		// if notifications allowed and assignee set notify assignee
   		if( $project->allownotifications && !empty($data->assignee)){
   		    techproject_notify_task_assign($project, $data, $currentGroupId);
       	}

		redirect($url);
	}

	echo $pagebuffer;
	if ($mode == 'add'){
		$task->fatherid = required_param('fatherid', PARAM_INT);
		$tasktitle = ($task->fatherid) ? 'addsubtask' : 'addtask';
		$task->id = $cm->id;
		$task->projectid = $project->id;
		$task->descriptionformat = FORMAT_HTML;
		$task->description = '';

		echo $OUTPUT->heading(get_string($tasktitle, 'techproject'));
	} else {
		if(! $task = $DB->get_record('techproject_task', array('id' => $taskid))){
			print_error('errortask','techproject');
		}
		$task->taskid = $task->id;
		$task->id = $cm->id;
		
		echo $OUTPUT->heading(get_string('updatetask','techproject'));
	}

	$mform->set_data($task);
	$mform->display();	
		
	