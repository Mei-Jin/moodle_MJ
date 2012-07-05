<?php

require_once("{$CFG->libdir}/gradelib.php");
require_once($CFG->dirroot . '/grade/report/lib.php');
require_once $CFG->dirroot.'/grade/report/overview/lib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once ("{$CFG->libdir}/tablelib.php");



class display extends flexible_table {

    
	public function set_conent() {
		global $DB, $USER;
 
		$this->content  =  new stdClass;
        $html;
 
		$userid=$USER->id; // hard-coding to ASmith, student
		$courseid=3; // hard-coding to Colour Theory
        
     /**
     * A flexitable to hold the data.
     * @var object $table
     */
        $table = $this->set_table();   
 
		/// return tracking object
		$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'overview', 'courseid'=>$courseid, 'userid'=>$userid));
 
		// Create a report instance
		$context = get_context_instance(CONTEXT_COURSE, $courseid);
		$report = new grade_report_overview($userid, $gpr, $context);
        ob_start();
        $this->table->wrap_html_start();
        $this->grade_data($report);
        $this->table->wrap_html_finish();
        $html = ob_get_clean();
		return $this->content->text = $html;
	}



	public function grade_data($report) {
		global $CFG, $DB, $OUTPUT;
        

		// MDL-11679, only show user's courses instead of all courses
		if ($courses = enrol_get_users_courses($report->user->id, false, 'id, shortname, showgrades')) {
			$numusers = $report->get_numusers(false);

			foreach ($courses as $course) {
				//echo("this course id is {$course->id}");
				if (!$course->showgrades) {
					continue;
				}

				$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

				if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
					// The course is hidden and the user isn't allowed to see it
					continue;
				}

				$courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
				$courselink = html_writer::link(new moodle_url('/grade/report/user/index.php', array('id' => $course->id, 'userid' => $report->user->id)), $courseshortname);
				$canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);

				// Get course grade_item
				$course_item = grade_item::fetch_course_item($course->id);

				// Get the stored grade
				$course_grade = new grade_grade(array('itemid'=>$course_item->id, 'userid'=>$report->user->id));
				$course_grade->grade_item =& $course_item;
				$finalgrade = $course_grade->finalgrade;
      
				if (!$report->showrank) {
					//nothing to do

				} else if (!is_null($finalgrade)) {
					/// find the number of users with a higher grade
					/// please note this can not work if hidden grades involved :-( to be fixed in 2.0
					$params = array($finalgrade, $course_item->id);
					$sql = "SELECT COUNT(DISTINCT(userid))
							  FROM {grade_grades}
							 WHERE finalgrade IS NOT NULL AND finalgrade > ?
								   AND itemid = ?";
					$rank = $DB->count_records_sql($sql, $params) + 1;

					$data = "$rank/$numusers";

				} else {
					// no grade, no rank
					$data = '-';
				}
                
                // fill table  -- 4th July
                $this->table->add_data(array(
                $course->shortname,
                $finalgrade,
                $data));
                // -- 4th July
			}
            $this->table->finish_output();
			return true;

		} else {
			return $OUTPUT->notification(get_string('nocourses', 'grades'));
		}
	}
    
     public function set_table()
        {
        /*  
        * Table has 3 columns
        *| course  | final grade | rank (optional) |
        * -- edited at 4th July
        */  
        // setting up table headers
        $tablecolumns = array(
            'coursename',
            'grade',
            'rank');
        $tableheaders = array(
            'coursename',
            'grade',
            'rank');
        $this->table = new flexible_table('mygrades-' . $this->user->id);
        $this->table->define_columns($tablecolumns);
        $this->table->define_headers($tableheaders);
        $this->table->define_baseurl($this->baseurl);

        $this->table->set_attribute('cellspacing', '0');
        $this->table->set_attribute('id', 'overview-grade');
        $this->table->set_attribute('class', 'boxaligncenter generaltable');

        $this->table->setup();
        // -- 4th July
        }    
           /**
     * Hook that can be overridden in child classes to wrap a table in a form
     * for example. Called only when there is data to display and not
     * downloading.
     */
    function wrap_html_start() {
        $html = '<div>';
    }

    /**
     * Hook that can be overridden in child classes to wrap a table in a form
     * for example. Called only when there is data to display and not
     * downloading.
     */
    function wrap_html_finish() {
        $html .= '</div>';
    }
}

