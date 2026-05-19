<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Page callbacks for Turnitin Rubric Importer.
 *
 * @package   local_turnitinrubricimporter
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds a Turnitin or CSV import option on Moodle rubric grading pages.
 *
 * @return string HTML to add before the page footer
 */
function local_turnitinrubricimporter_before_footer() {
    global $PAGE, $DB;

    $path = $PAGE->url->get_path();

    $isrubricedit = ($path === '/grade/grading/form/rubric/edit.php');
    $isgradingmanage = ($path === '/grade/grading/manage.php');

    if (!$isrubricedit && !$isgradingmanage) {
        return '';
    }

    $areaid = optional_param('areaid', 0, PARAM_INT);
    $contextid = optional_param('contextid', 0, PARAM_INT);

    if (!$areaid && $contextid) {
        $component = optional_param('component', '', PARAM_COMPONENT);
        $area = optional_param('area', '', PARAM_ALPHANUMEXT);
        if ($component && $area) {
            $gradingarea = $DB->get_record('grading_areas', [
                'contextid' => $contextid,
                'component' => $component,
                'areaname' => $area,
            ]);
            if ($gradingarea) {
                $areaid = (int)$gradingarea->id;
            }
        }
    }

    if (!$areaid) {
        return '';
    }

    $gradingarea = $DB->get_record('grading_areas', ['id' => $areaid]);
    if (!$gradingarea) {
        return '';
    }

    $context = context::instance_by_id($gradingarea->contextid, IGNORE_MISSING);
    if (!$context) {
        return '';
    }

    if (!has_capability('moodle/grade:managegradingforms', $context)) {
        return '';
    }

    $returnurl = $PAGE->url->out(false);

    $url = new moodle_url('/local/turnitinrubricimporter/import.php', [
        'areaid' => $areaid,
        'contextid' => $context->id,
        'returnurl' => $returnurl,
    ]);

    $cardtext = get_string('importfromfile_card', 'local_turnitinrubricimporter');
    $cardlabel = get_string('importfromfile', 'local_turnitinrubricimporter');
    $helptext = get_string('filebuttonhelp', 'local_turnitinrubricimporter');

    $icon = html_writer::tag('i', '', [
        'class' => 'icon fa fa-upload fa-fw iconsize-big',
        'aria-hidden' => 'true',
    ]);

    $card = html_writer::link($url, $icon . html_writer::div($cardtext, 'action-text'), [
        'id' => 'local_turnitinrubricimporter_rbc_card',
        'class' => 'action btn btn-lg',
        'title' => $helptext,
        'aria-label' => $cardlabel,
    ]);

    $fallback = html_writer::div(
        html_writer::tag('strong', get_string('pluginname', 'local_turnitinrubricimporter'))
        . html_writer::tag('p', $helptext, ['class' => 'mb-2'])
        . html_writer::link($url, $cardlabel, ['class' => 'btn btn-secondary']),
        'local_turnitinrubricimporter_rbc_panel alert alert-info mt-3 mb-3',
        ['id' => 'local_turnitinrubricimporter_rbc_panel']
    );

    $html = html_writer::div($card . $fallback, 'local_turnitinrubricimporter_loaded_marker', [
        'id' => 'local_turnitinrubricimporter_rbc_holder',
        'data-plugin' => 'rubric-file-importer',
        'data-version' => '2026051212',
    ]);

    $html .= html_writer::tag('style', '
#local_turnitinrubricimporter_rbc_holder {
    display: none;
}
');

    $html .= html_writer::script("
        document.addEventListener('DOMContentLoaded', function() {
            var holder = document.getElementById('local_turnitinrubricimporter_rbc_holder');
            var card = document.getElementById('local_turnitinrubricimporter_rbc_card');
            var panel = document.getElementById('local_turnitinrubricimporter_rbc_panel');
            if (!holder || !card || !panel) {
                return;
            }

            var links = Array.prototype.slice.call(document.querySelectorAll('a'));
            var defineLink = null;
            var templateLink = null;
            var editLink = null;
            var deleteLink = null;

            links.forEach(function(link) {
                var text = (link.textContent || '').replace(/\\s+/g, ' ').trim().toLowerCase();
                if (!defineLink && text.indexOf('define new grading form from scratch') !== -1) {
                    defineLink = link;
                }
                if (!templateLink && text.indexOf('create new grading form from a template') !== -1) {
                    templateLink = link;
                }
                if (!editLink && text.indexOf('edit the current form definition') !== -1) {
                    editLink = link;
                }
                if (!deleteLink && text.indexOf('delete the currently defined form') !== -1) {
                    deleteLink = link;
                }
            });

            var targetLink = templateLink || defineLink || deleteLink || editLink;
            var actionParent = targetLink ? targetLink.parentElement : null;

            if (actionParent && actionParent.classList && actionParent.classList.contains('actions')) {
                panel.remove();
                actionParent.appendChild(card);
                holder.style.display = 'block';
                return;
            }

            if (actionParent && actionParent.querySelectorAll && actionParent.querySelectorAll('a.action').length > 0) {
                panel.remove();
                actionParent.appendChild(card);
                holder.style.display = 'block';
                return;
            }

            card.remove();
            panel.remove();
            holder.style.display = 'none';
        });
    ");

    return $html;
}
