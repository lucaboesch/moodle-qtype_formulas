<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the editing form for the formulas question type.
 *
 * @copyright &copy; 2010-2011 Hon Wai, Lau
 * @author Hon Wai, Lau <lau65536@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * @package qtype_formulas
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/edit_question_form.php');

/**
 * coodinate question type editing form definition.
 */
class qtype_formulas_edit_form extends question_edit_form {

    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {
        global $PAGE;
        $PAGE->requires->js('/question/type/formulas/script/editing.js');
        $PAGE->requires->js('/question/type/formulas/script/formatcheck.js');
        $PAGE->requires->css('/question/type/formulas/styles.css');
        // Hide the unused form fields.
        $mform->removeElement('defaultmark');
        $mform->addElement('hidden', 'defaultmark');
        $mform->setType('defaultmark', PARAM_RAW);

        $mform->addElement('hidden', 'jsvars');     // Used to keep the values during page submission.

        $mform->addHelpButton('questiontext', 'questiontext', 'qtype_formulas');

        // Random and global variables and main question.
        $mform->insertElementBefore($mform->createElement('header', 'globalvarshdr', get_string('globalvarshdr', 'qtype_formulas'),
            ''), 'questiontext');

        $mform->insertElementBefore($mform->createElement('textarea', 'varsrandom', get_string('varsrandom', 'qtype_formulas'),
            array('cols' => 80, 'rows' => 1)) , 'questiontext');
        $mform->addHelpButton('varsrandom', 'varsrandom', 'qtype_formulas');

        $mform->insertElementBefore($mform->createElement('textarea', 'varsglobal', get_string('varsglobal', 'qtype_formulas'),
            array('cols' => 80, 'rows'  => 1)) , 'questiontext');
        $mform->addHelpButton('varsglobal', 'varsglobal', 'qtype_formulas');

        $mform->insertElementBefore($mform->createElement('header', 'mainq', get_string('mainq', 'qtype_formulas'),
            ''), 'questiontext');
        // Subquestion's answers.
        $creategrades = get_grade_options();
        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_formulas', '{no}'),
            question_bank::fraction_options(), 1, 2);

        // Display options, flow options and global subquestion's options.
        $mform->addElement('header', 'subqoptions', get_string('subqoptions', 'qtype_formulas'));

        $mform->addElement('text', 'globalunitpenalty',
                get_string('globaloptions', 'qtype_formulas') . get_string('unitpenalty', 'qtype_formulas'),
            array('size' => 3));
        $mform->addHelpButton('globalunitpenalty', 'unitpenalty', 'qtype_formulas');
        $mform->setDefault('globalunitpenalty', 1);

        $conversionrules = new unit_conversion_rules;
        $allrules = $conversionrules->allrules();
        foreach ($allrules as $id => $entry) {
            $default_rule_choice[$id] = $entry[0];
        }
        $mform->addElement('select', 'globalruleid',
                get_string('globaloptions', 'qtype_formulas') . get_string('ruleid', 'qtype_formulas'),
            $default_rule_choice);
        $mform->setDefault('globalruleid', 1);
         $mform->addHelpButton('globalruleid', 'ruleid', 'qtype_formulas');

        // Embed the current plugin url, which will be used by the javascript.
        global $CFG;
        $fbaseurl = '<script type="text/javascript">var formulasbaseurl='
                .json_encode($CFG->wwwroot . '/question/type/formulas').';</script>';   // Temporary hack.

        // Allow instantiate random variables and display the data for instantiated variables.
        $mform->addElement('header', 'checkvarshdr', get_string('checkvarshdr', 'qtype_formulas'));
        $mform->addElement('static', 'numdataset', get_string('numdataset', 'qtype_formulas'),
            '<div id="numdataset_option"></div>'.$fbaseurl);
        $mform->addElement('static', 'qtextpreview', get_string('qtextpreview', 'qtype_formulas'),
            '<div id="qtextpreview_controls"></div>'
            .'<div id="qtextpreview_display"></div>');
        $mform->addElement('static', 'varsstatistics', get_string('varsstatistics', 'qtype_formulas'),
            '<div id="varsstatistics_controls"></div>'
            .'<div id="varsstatistics_display"></div>');
        $mform->addElement('static', 'varsdata', get_string('varsdata', 'qtype_formulas'),
            '<div id="varsdata_controls"></div>'
            .'<div id="varsdata_display"></div>');
        $mform->closeHeaderBefore('instantiatevars');

        $this->add_combined_feedback_fields(true);
        $this->add_interactive_settings(true, true);
    }


    /**
     * Add the answer field for a particular subquestion labelled by placeholder.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $config = get_config('qtype_formulas');
        $repeated = array();
        $repeated[] = $mform->createElement('header', 'answerhdr', $label);

        $repeated[] = $mform->createElement('text', 'answermark', get_string('answermark', 'qtype_formulas'),
            array('size' => 3));
        $repeatedoptions['answermark']['helpbutton'] = array('answermark', 'qtype_formulas');
        $repeatedoptions['answermark']['default'] = $config->defaultanswermark;
        $repeated[] = $mform->createElement('hidden', 'numbox', '', '');   // Exact value will be computed during validation.
        $repeated[] = $mform->createElement('textarea', 'vars1', get_string('vars1', 'qtype_formulas'),
            array('cols' => 80, 'rows' => 1));
        $repeatedoptions['vars1']['helpbutton'] = array('vars1', 'qtype_formulas');
        $repeated[] = $mform->createElement('select', 'answertype', get_string('answertype', 'qtype_formulas'),
                array(0 => get_string('number', 'qtype_formulas'), 10 => get_string('numeric', 'qtype_formulas'),
                        100 => get_string('numerical_formula', 'qtype_formulas'),
                        1000 => get_string('algebraic_formula', 'qtype_formulas')));;
        $repeatedoptions['answertype']['default'] = $config->defaultanswertype;
        $repeatedoptions['answertype']['helpbutton'] = array('answertype', 'qtype_formulas');
        $repeated[] = $mform->createElement('text', 'answer', get_string('answer', 'qtype_formulas'),
            array('size' => 80));
        $repeatedoptions['answer']['helpbutton'] = array('answer', 'qtype_formulas');
        $repeated[] = $mform->createElement('textarea', 'vars2', get_string('vars2', 'qtype_formulas'),
            array('cols' => 80, 'rows' => 1));
        $repeatedoptions['vars2']['helpbutton'] = array('vars2', 'qtype_formulas');
        $repeated[] = $mform->createElement('text', 'correctness', get_string('correctness', 'qtype_formulas'),
            array('size' => 60));
        $repeatedoptions['correctness']['default'] = $config->defaultcorrectness;
        $repeatedoptions['correctness']['helpbutton'] = array('correctness', 'qtype_formulas');
        $repeated[] = $mform->createElement('text', 'unitpenalty', get_string('unitpenalty', 'qtype_formulas'),
            array('size' => 3));
        $repeatedoptions['unitpenalty']['default'] = $config->defaultunitpenalty;
        $repeatedoptions['unitpenalty']['helpbutton'] = array('unitpenalty', 'qtype_formulas');
        $repeated[] = $mform->createElement('text', 'postunit', get_string('postunit', 'qtype_formulas'),
            array('size' => 60, 'class' => 'formulas_editing_unit'));
        $repeatedoptions['postunit']['helpbutton'] = array('postunit', 'qtype_formulas');
        $conversionrules = new unit_conversion_rules;
        $allrules = $conversionrules->allrules();
        foreach ($allrules as $id => $entry) {
            $default_rule_choice[$id] = $entry[0];
        }
        $repeated[] = $mform->createElement('select', 'ruleid', get_string('ruleid', 'qtype_formulas'),
            $default_rule_choice);
        $repeatedoptions['ruleid']['default'] = 1;
        $repeated[] = $mform->createElement('textarea', 'otherrule', get_string('otherrule', 'qtype_formulas'),
            array('cols' => 80, 'rows' => 1));
        $repeatedoptions['otherrule']['helpbutton'] = array('otherrule', 'qtype_formulas');
        $repeated[] = $mform->createElement('static', '', '<hr class="formulas_seperator2" />', '<hr />');
        $repeated[] = $mform->createElement('text', 'placeholder', get_string('placeholder', 'qtype_formulas'),
            array('size' => 20));
        $repeatedoptions['placeholder']['helpbutton'] = array('placeholder', 'qtype_formulas');
        $repeated[] = $mform->createElement('editor', 'subqtext', get_string('subqtext', 'qtype_formulas'),
            array('rows' => 3), $this->editoroptions);
        $repeatedoptions['subqtext']['helpbutton'] = array('subqtext', 'qtype_formulas');
        $repeated[] = $mform->createElement('editor', 'feedback', get_string('feedback', 'qtype_formulas'),
            array('rows' => 3), $this->editoroptions);
        $repeatedoptions['feedback']['helpbutton'] = array('feedback', 'qtype_formulas');
        $answersoption = 'answers';
        return $repeated;
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);
        if (isset($question->options)) {
            if (count($question->options->answers)) {
                $tags = question_bank::get_qtype($question->qtype)->subquestion_answer_tags();
                foreach ($question->options->answers as $key => $answer) {

                    foreach ($tags as $tag) {
                        $default_values[$tag.'['.$key.']'] = $answer->$tag;
                    }
                    // Prepare subquestion's text.
                    $subqtid = file_get_submitted_draft_itemid('subqtext['.$key.']');
                    $subqt = file_prepare_draft_area($subqtid, $this->context->id, 'qtype_formulas',
                            'answersubqtext', empty($answer->id)?null:(int)$answer->id, $this->fileoptions, $answer->subqtext);
                    $default_values['subqtext['.$key.']'] = array('text' => $subqt,
                            'format' => $answer->subqtextformat, 'itemid' => $subqtid);
                    $subqfbid = file_get_submitted_draft_itemid('feedback['.$key.']');
                    $subqfb = file_prepare_draft_area($subqfbid, $this->context->id, 'qtype_formulas',
                            'answerfeedback', empty($answer->id)?null:(int)$answer->id,
                            $this->fileoptions, $answer->feedback);
                    $default_values['feedback['.$key.']'] = array('text'=>$subqfb,
                            'format'=>$answer->feedbackformat, 'itemid'=>$subqfbid);
                }
            }

            $question = (object)((array)$question + $default_values);
        }
        return $question;
    }
    /**
     * Validating the data returning from the form.
     *
     * The check the basic error as well as the formula error by evaluating one instantiation.
     */
    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        // Use the validation defined in the question type, check by instantiating one variable set.
        $data = (object)$fromform;
        $instantiation_result = question_bank::get_qtype($this->qtype())->validate($data);
        if (isset($instantiation_result->errors)) {
            $errors = array_merge($errors, $instantiation_result->errors);
        }
        // Forward the (first) local error of the options to the global one.
        $global_tags = array('unitpenalty', 'ruleid');
        foreach ($global_tags as $gtag) {
            if (array_key_exists($gtag.'[0]', $errors)) {
                $errors['global'.$gtag] = $errors[$gtag.'[0]'];
            }
        }
        return $errors;
    }

    public function qtype() {
        return 'formulas';
    }
}
