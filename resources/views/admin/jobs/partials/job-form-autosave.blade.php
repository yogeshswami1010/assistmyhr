            const jobDraftKey = JSON.stringify([
                'assistmyhr.job-form-draft.v1', @json(auth()->id()), jobFormSaveUrl,
                window.location.pathname, window.location.search,
            ]);
            let jobDraftSubmitted = false;

            function jobDraftControls(selector = 'input, select, textarea') {
                return Array.from(document.querySelectorAll('#createForm input, #createForm select, #createForm textarea'))
                    .filter(control => control.matches(selector) &&
                        !['_token', '_method'].includes(control.name) &&
                        control.id !== 'job_requirement_hidden' &&
                        !['file', 'password', 'button', 'submit'].includes(control.type) &&
                        !control.classList.contains('js-pipeline-hidden') &&
                        (control.name || control.id === 'work_experience_new'));
            }

            function jobDraftFieldKey(control) {
                return control.id || JSON.stringify([
                    control.name, control.type,
                    ['checkbox', 'radio'].includes(control.type) ? control.value : '',
                ]);
            }

            function readJobDraft() {
                jobRichSyncAll();
                const fields = Object.fromEntries(jobDraftControls().map(control => {
                    let value = control.value;
                    if (['checkbox', 'radio'].includes(control.type)) {
                        value = control.checked;
                    } else if (control.multiple) {
                        value = Array.from(control.selectedOptions, option => option.value);
                    }
                    return [jobDraftFieldKey(control), value];
                }));
                const pipeline = [];
                document.getElementById('job-status-list')?.dispatchEvent(
                    new CustomEvent('job-draft-read', {detail: pipeline})
                );
                return {fields, pipeline};
            }

            function cleanJobDraftRichText(html) {
                const template = document.createElement('template');
                template.innerHTML = html;
                const allowedTags = new Set('A B BLOCKQUOTE BR CODE DIV EM FONT H1 H2 H3 H4 H5 H6 HR I LI OL P PRE S SPAN STRIKE STRONG SUB SUP U UL'.split(' '));
                for (const element of template.content.querySelectorAll('*')) {
                    if (element.namespaceURI !== 'http://www.w3.org/1999/xhtml' || !allowedTags.has(element.tagName)) {
                        if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'TEMPLATE'].includes(element.tagName)) {
                            element.remove();
                        } else {
                            element.replaceWith(...element.childNodes);
                        }
                        continue;
                    }
                    for (const attribute of Array.from(element.attributes)) {
                        const safeLink = element.tagName === 'A' && attribute.name === 'href' &&
                            /^(https?:\/\/|mailto:|tel:)/i.test(attribute.value.trim());
                        const fontSize = element.tagName === 'FONT' && attribute.name === 'size' && /^[1-7]$/.test(attribute.value);
                        if (!safeLink && !fontSize) {
                            element.removeAttribute(attribute.name);
                        }
                    }
                }
                return template.innerHTML;
            }

            function restoreJobDraftFields(fields, selector) {
                for (const control of jobDraftControls(selector)) {
                    const key = jobDraftFieldKey(control);
                    if (!Object.hasOwn(fields, key) || key === 'category_id' && selector === undefined) {
                        continue;
                    }
                    const value = fields[key];
                    if (['checkbox', 'radio'].includes(control.type) && typeof value === 'boolean') {
                        control.checked = value;
                        if ($(control).data('iCheck')) {
                            $(control).iCheck('update');
                        }
                    } else if (control.multiple && Array.isArray(value)) {
                        if (control.id === 'work_experience') {
                            for (const item of value) {
                                if (typeof item === 'string' && item.startsWith('new:') &&
                                    !Array.from(control.options).some(option => option.value === item)) {
                                    control.add(new Option(item.slice(4), item));
                                }
                            }
                        }
                        $(control).val(value).trigger('change.select2');
                    } else if (typeof value === 'string') {
                        if (control.tagName !== 'SELECT' || Array.from(control.options).some(option => option.value === value)) {
                            control.value = ['job_description', 'job_requirement'].includes(key) ? cleanJobDraftRichText(value) : value;
                            $(control).trigger('change.select2');
                        }
                    }
                }
            }

            function initJobDraftAutosave() {
                const status = document.getElementById('job-draft-autosave-status');
                const initial = readJobDraft();
                const initialPipeline = JSON.stringify(initial.pipeline);
                const category = document.getElementById('category_id');
                const different = (first, second) => JSON.stringify(first) !== JSON.stringify(second);
                let lastStoredDraft = null;
                try {
                    lastStoredDraft = localStorage.getItem(jobDraftKey);
                    const draft = JSON.parse(lastStoredDraft);
                    if (draft?.fields && typeof draft.fields === 'object') {
                        if (draft.fields.company_new) {
                            document.getElementById('add-new-company')?.click();
                        }
                        if (draft.fields.work_experience_new) {
                            document.getElementById('addWorkExperience')?.click();
                        }
                        restoreJobDraftFields(draft.fields);
                        if (category && typeof draft.fields.category_id === 'string' &&
                            Array.from(category.options).some(option => option.value === draft.fields.category_id) &&
                            category.value !== draft.fields.category_id) {
                            $(category).val(draft.fields.category_id).trigger('change', [draft.fields]);
                        }
                        if (Array.isArray(draft.pipeline)) {
                            document.getElementById('job-status-list')?.dispatchEvent(
                                new CustomEvent('job-draft-restore', {detail: draft.pipeline})
                            );
                        }
                        jobRichInitEditor('job_description_editor', 'job_description');
                        jobRichInitEditor('job_requirement_editor', 'job_requirement');
                        applyPayTypeVisibility();
                        jobFormPreviewRefresh();
                        status.textContent = @json(__('Restored your autosaved draft from this browser.'));
                    }
                } catch {
                    // Storage can be unavailable in restricted browser sessions.
                }

                function saveDraft() {
                    if (jobDraftSubmitted) {
                        return;
                    }
                    const current = readJobDraft();
                    const fields = Object.fromEntries(Object.entries(current.fields)
                        .filter(([key, value]) => different(value, initial.fields[key])));
                    const pipeline = JSON.stringify(current.pipeline) === initialPipeline ? null : current.pipeline;
                    try {
                        if (Object.keys(fields).length || pipeline) {
                            const draft = JSON.stringify({fields, pipeline});
                            localStorage.setItem(jobDraftKey, draft);
                            lastStoredDraft = draft;
                            status.textContent = @json(__('Draft autosaved in this browser.'));
                        } else if (lastStoredDraft && localStorage.getItem(jobDraftKey) === lastStoredDraft) {
                            localStorage.removeItem(jobDraftKey);
                            lastStoredDraft = null;
                            status.textContent = '';
                        }
                    } catch {
                        status.textContent = @json(__('Autosave is unavailable. Use Save to keep your changes.'));
                    }
                }

                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        saveDraft();
                    }
                });
                window.addEventListener('pagehide', saveDraft);
            }

            function clearJobDraftAutosave() {
                jobDraftSubmitted = true;
                try {
                    localStorage.removeItem(jobDraftKey);
                } catch {
                    // A successful server save still completes with browser storage disabled.
                }
            }
