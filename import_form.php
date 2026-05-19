<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Import form for uploading a Turnitin RBC or CSV rubric file.
 */
class import_form extends moodleform {
    /**
     * Define form elements.
     */
    public function definition() {
        $mform = $this->_form;
        $sampleurl = new moodle_url('/local/turnitinrubricimporter/samplecsv.php');

        $layoutcss = <<<'HTML'
<style>
body.local_turnitinrubricimporter_import .activity-header,
body.local_turnitinrubricimporter_import [data-region="activity-information"],
body.local_turnitinrubricimporter_import .activity-information,
body.local_turnitinrubricimporter_import .activity-dates {
    display: none !important;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_import_intro {
    margin-top: 2rem;
    margin-bottom: 3rem;
    max-width: 920px;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_import_intro p {
    font-size: 1rem;
    line-height: 1.5;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_template_link {
    text-decoration: underline;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_csv_example {
    max-width: 900px;
    font-size: 1rem;
    line-height: 1.55;
    white-space: pre-wrap;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile {
    margin-top: 1.5rem;
    margin-bottom: 2rem;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .col-form-label,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .fitemtitle {
    display: none !important;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .felement,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .col-md-9,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .col-sm-9 {
    flex: 0 0 100%;
    max-width: 100%;
    width: 100%;
    margin-left: 0;
    padding-left: 0;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filepicker,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filemanager,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filepicker-container,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filemanager-container,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filepicker-filelist,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .fp-content,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .fm-empty-container {
    width: 100%;
    max-width: none;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filepicker-container,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filemanager-container {
    min-height: 150px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    background: #fff;
    border: 1px solid #b8bec6 !important;
    border-radius: 0.25rem;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .filepicker-filelist,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .fp-content,
body.local_turnitinrubricimporter_import #fitem_id_rubricfile .fm-empty-container {
    min-height: 150px;
    position: relative;
    border: 0 !important;
    background: transparent !important;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .dndupload-message {
    position: absolute !important;
    top: 0.75rem !important;
    right: 0.75rem !important;
    bottom: 0.75rem !important;
    left: 0.75rem !important;
    width: auto !important;
    height: auto !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 2px dashed #b8bec6 !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: transparent !important;
    cursor: pointer;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .dndupload-arrow {
    position: static !important;
    float: none !important;
    align-self: center;
    margin: 0 0 0.85rem 0 !important;
}

body.local_turnitinrubricimporter_import #fitem_id_rubricfile .dndupload-message * {
    text-align: center;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_clickable_filepicker .filemanager-container,
body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_clickable_filepicker .filepicker-container,
body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_clickable_filepicker .fm-empty-container,
body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_clickable_filepicker .dndupload-message,
body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_clickable_filepicker .fp-content,
body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_clickable_filepicker .filepicker-filelist {
    cursor: pointer;
}

body.local_turnitinrubricimporter_import .local_turnitinrubricimporter_hidden_choose {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 1px !important;
    height: 1px !important;
    min-width: 1px !important;
    min-height: 1px !important;
    overflow: hidden !important;
    opacity: 0 !important;
    clip: rect(0 0 0 0) !important;
    clip-path: inset(50%) !important;
    white-space: nowrap !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
}

body.local_turnitinrubricimporter_import .fp-restrictions,
body.local_turnitinrubricimporter_import .filepickeracceptedtypes,
body.local_turnitinrubricimporter_import .filemanageracceptedtypes,
body.local_turnitinrubricimporter_import .filetypes-descriptions,
body.local_turnitinrubricimporter_import .form-filetypes-descriptions,
body.local_turnitinrubricimporter_import .filepicker-filetypes,
body.local_turnitinrubricimporter_import .filemanager-filetypes {
    display: none !important;
}

body.local_turnitinrubricimporter_import #fgroup_id_buttonar .col-form-label,
body.local_turnitinrubricimporter_import .fitem_actionbuttons .col-form-label {
    display: none !important;
}

body.local_turnitinrubricimporter_import #fgroup_id_buttonar .felement,
body.local_turnitinrubricimporter_import #fgroup_id_buttonar .col-md-9,
body.local_turnitinrubricimporter_import .fitem_actionbuttons .felement,
body.local_turnitinrubricimporter_import .fitem_actionbuttons .col-md-9 {
    flex: 0 0 100%;
    max-width: 100%;
    margin-left: 0;
}

body.local_turnitinrubricimporter_import .mform .fdescription.required {
    display: none !important;
}
</style>
HTML;
        $mform->addElement('html', $layoutcss);

        $introtext = html_writer::tag(
            'strong',
            get_string('acceptedfiletypeslabel', 'local_turnitinrubricimporter')
        ) . ' ' .
            get_string('acceptedfiletypesmanual', 'local_turnitinrubricimporter') . ' ' .
            html_writer::link(
                $sampleurl,
                get_string('downloadtemplatecsvlink', 'local_turnitinrubricimporter'),
                ['class' => 'local_turnitinrubricimporter_template_link']
            ) . ' ' .
            get_string('csvformatshort', 'local_turnitinrubricimporter');

        $filehelp = html_writer::div(
            html_writer::tag('p', $introtext, ['class' => 'mb-2']) .
            html_writer::tag(
                'pre',
                'criterion,level,level_description,score' . PHP_EOL .
                '"Clarity","1","Clear and concise","10"' . PHP_EOL .
                '"Clarity","2","Mostly clear","5"' . PHP_EOL .
                '"Clarity","3","Lacks clarity","0"',
                ['class' => 'local_turnitinrubricimporter_csv_example mt-2 mb-2 p-3 bg-light border rounded']
            ),
            'local_turnitinrubricimporter_import_intro text-muted'
        );
        $mform->addElement('html', $filehelp);

        $mform->addElement(
            'filepicker',
            'rubricfile',
            get_string('chooserubricfile', 'local_turnitinrubricimporter'),
            null,
            [
                'accepted_types' => ['*'],
                'maxbytes' => 0,
                'subdirs' => 0,
            ]
        );
        $mform->addRule('rubricfile', null, 'required');

        $layoutjs = <<<'HTML'
<script>
(function() {
    var dropzonePrompt = 'Click here, or drag files here to upload them';

    function normaliseText(text) {
        return (text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function findRubricPickerContainer() {
        var container = document.getElementById('fitem_id_rubricfile');
        if (container) {
            return container;
        }

        var field = document.getElementById('id_rubricfile') || document.querySelector('[name="rubricfile"]');
        if (field && field.closest) {
            return field.closest('.fitem, .form-group, .mb-3, .form-item');
        }

        return null;
    }

    function hideAcceptedTypeNotes() {
        var selectors = [
            '.fp-restrictions',
            '.filepickeracceptedtypes',
            '.filemanageracceptedtypes',
            '.filetypes-descriptions',
            '.form-filetypes-descriptions',
            '.filepicker-filetypes',
            '.filemanager-filetypes'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var matches = document.querySelectorAll(selectors[i]);
            for (var j = 0; j < matches.length; j++) {
                matches[j].style.display = 'none';
            }
        }

        var candidates = document.querySelectorAll('.mform div, .mform p, .mform span, .mform small');
        for (var k = 0; k < candidates.length; k++) {
            if (candidates[k].closest && candidates[k].closest('.local_turnitinrubricimporter_import_intro')) {
                continue;
            }

            var text = normaliseText(candidates[k].textContent);
            if (text === 'accepted file types:' || (text.indexOf('accepted file types:') === 0 && text.length < 80)) {
                candidates[k].style.display = 'none';
            }
        }
    }

    function updateDropzonePrompt(container) {
        var messages = container.querySelectorAll('.dndupload-message');
        for (var i = 0; i < messages.length; i++) {
            var message = messages[i];
            var replaced = false;

            message.setAttribute('aria-label', dropzonePrompt);

            var textElements = message.querySelectorAll('.dndupload-message-text, .dndupload-text, p, span, div');
            for (var t = 0; t < textElements.length; t++) {
                if (textElements[t].classList && textElements[t].classList.contains('dndupload-arrow')) {
                    continue;
                }
                if (textElements[t].closest && textElements[t].closest('.dndupload-arrow')) {
                    continue;
                }
                if (textElements[t].querySelector && textElements[t].querySelector('.dndupload-arrow')) {
                    continue;
                }

                var elementText = normaliseText(textElements[t].textContent);
                if (
                    elementText.indexOf('drag') !== -1 ||
                    elementText.indexOf('files here') !== -1 ||
                    elementText.indexOf('add them') !== -1
                ) {
                    textElements[t].textContent = dropzonePrompt;
                    replaced = true;
                    break;
                }
            }

            if (!replaced && document.createTreeWalker && window.NodeFilter) {
                var walker = document.createTreeWalker(
                    message,
                    NodeFilter.SHOW_TEXT,
                    {
                        acceptNode: function(node) {
                            if (node.parentNode && node.parentNode.closest && node.parentNode.closest('.dndupload-arrow')) {
                                return NodeFilter.FILTER_REJECT;
                            }

                            var text = normaliseText(node.nodeValue);
                            if (
                                text.indexOf('drag') !== -1 ||
                                text.indexOf('files here') !== -1 ||
                                text.indexOf('add them') !== -1
                            ) {
                                return NodeFilter.FILTER_ACCEPT;
                            }

                            return NodeFilter.FILTER_REJECT;
                        }
                    }
                );

                var node = walker.nextNode();
                if (node) {
                    node.nodeValue = dropzonePrompt;
                    replaced = true;
                }
            }

            if (!replaced) {
                var fallback = message.querySelector('.local_turnitinrubricimporter_dropzone_prompt');
                if (!fallback) {
                    fallback = document.createElement('div');
                    fallback.className = 'local_turnitinrubricimporter_dropzone_prompt';
                    message.appendChild(fallback);
                }
                fallback.textContent = dropzonePrompt;
            }
        }
    }

    function isClickableControl(element) {
        if (!element || !element.tagName) {
            return false;
        }

        var tag = element.tagName.toLowerCase();
        return tag === 'a' || tag === 'button' || tag === 'input';
    }

    function findChooseControl(container) {
        var selectors = [
            'a.fp-btn-choose',
            'button.fp-btn-choose',
            '.fp-btn-choose',
            '.fp-btn-add',
            '[id^="filepicker-button"] a',
            '[id^="filepicker-button"] button',
            '.filepicker-toolbar button',
            '.filepicker-toolbar a',
            '.filemanager-toolbar button',
            '.filemanager-toolbar a'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var selected = container.querySelector(selectors[i]);
            if (selected) {
                if (isClickableControl(selected)) {
                    return selected;
                }

                var nested = selected.querySelector('a, button, input[type="button"]');
                if (nested) {
                    return nested;
                }
            }
        }

        var controls = container.querySelectorAll('button, a[role="button"], a.btn, input[type="button"]');
        for (var j = 0; j < controls.length; j++) {
            var label = normaliseText(
                controls[j].textContent ||
                controls[j].value ||
                controls[j].getAttribute('aria-label')
            );

            if (label.indexOf('choose a file') !== -1 || label.indexOf('add') !== -1) {
                return controls[j];
            }
        }

        return null;
    }

    function hideChooseControl(chooseControl) {
        chooseControl.classList.add('local_turnitinrubricimporter_hidden_choose');

        if (chooseControl.parentElement && normaliseText(chooseControl.parentElement.textContent).indexOf('choose a file') !== -1) {
            chooseControl.parentElement.classList.add('local_turnitinrubricimporter_hidden_choose');
        }

        if (chooseControl.closest) {
            var holder = chooseControl.closest('[id^="filepicker-button"]');
            if (holder) {
                holder.classList.add('local_turnitinrubricimporter_hidden_choose');
            }
        }
    }

    function triggerChooseControl(chooseControl) {
        if (!chooseControl) {
            return;
        }

        if (typeof chooseControl.click === 'function') {
            chooseControl.click();
            return;
        }

        if (typeof MouseEvent === 'function') {
            chooseControl.dispatchEvent(new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            }));
        }
    }

    function addClickableZone(zone, chooseControl) {
        if (!zone || zone.dataset.localCustomgradingformRendererClickable === '1') {
            return;
        }

        zone.dataset.localCustomgradingformRendererClickable = '1';
        zone.setAttribute('role', 'button');
        zone.setAttribute('tabindex', '0');
        zone.setAttribute('aria-label', 'Choose rubric file');

        var openChooser = function(event) {
            if (event.localCustomgradingformRendererHandled) {
                return;
            }

            if (
                event.target &&
                event.target.closest &&
                event.target.closest('a, button, input, select, textarea, [contenteditable="true"]')
            ) {
                return;
            }

            event.localCustomgradingformRendererHandled = true;
            event.preventDefault();
            event.stopPropagation();
            triggerChooseControl(chooseControl);
        };

        zone.addEventListener('click', openChooser);
        zone.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                openChooser(event);
            }
        });
    }

    function enhanceRubricPicker() {
        var container = findRubricPickerContainer();
        if (!container) {
            hideAcceptedTypeNotes();
            return;
        }

        container.classList.add('local_turnitinrubricimporter_clickable_filepicker');
        hideAcceptedTypeNotes();
        updateDropzonePrompt(container);

        var chooseControl = findChooseControl(container);
        if (!chooseControl) {
            return;
        }

        hideChooseControl(chooseControl);

        var zones = container.querySelectorAll(
            '.dndupload-message, .filepicker-container, .filemanager-container, .fm-empty-container, .fp-content, .filepicker-filelist'
        );
        for (var i = 0; i < zones.length; i++) {
            addClickableZone(zones[i], chooseControl);
        }

        if (!container.dataset.localCustomgradingformRendererObserved && typeof MutationObserver !== 'undefined') {
            container.dataset.localCustomgradingformRendererObserved = '1';

            var observer = new MutationObserver(function() {
                enhanceRubricPicker();
            });

            observer.observe(container, {
                childList: true,
                subtree: true
            });
        }
    }

    function startEnhancement() {
        enhanceRubricPicker();
        window.setTimeout(enhanceRubricPicker, 500);
        window.setTimeout(enhanceRubricPicker, 1500);
        window.setTimeout(enhanceRubricPicker, 3000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startEnhancement);
    } else {
        startEnhancement();
    }
})();
</script>
HTML;
        $mform->addElement('html', $layoutjs);

        $mform->addElement('hidden', 'areaid');
        $mform->setType('areaid', PARAM_INT);

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_action_buttons(true, get_string('submitfile', 'local_turnitinrubricimporter'));
    }
}
