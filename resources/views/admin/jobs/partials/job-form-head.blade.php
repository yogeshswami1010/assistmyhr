<link rel="stylesheet" href="{{ asset('assets/node_modules_files/select2/dist/css/select2.css') }}">
<link rel="stylesheet" href="{{ asset('assets/node_modules_files/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}">
<style>
    #createForm .job-rich-editor-editor ol { list-style-type: decimal; padding-left: 1.5rem; }
    #createForm .job-rich-editor-editor ul { list-style-type: disc; padding-left: 1.5rem; }
    /* Rich editor placeholder */
    #createForm .job-rich-editor-editor:empty::before {
        content: attr(data-placeholder);
        color: #C4CBD4;
        pointer-events: none;
    }

    /* Native <select> — override global .form-control / select rules */
    .job-form-page #createForm select.job-form-sel.form-control:not([multiple]) {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-color: #fff !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239CA3AF' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 12px center !important;
        background-size: 12px 7px !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        box-shadow: none !important;
        min-height: 2.75rem;
        padding: 0.5rem 2.25rem 0.5rem 0.875rem !important;
        font-size: 13px !important;
        line-height: 1.35;
        color: #0f1f3d !important;
    }
    .job-form-page #createForm select.job-form-sel.form-control:not([multiple]):focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
        outline: none !important;
    }

    /* Date inputs — same footprint as selects */
    .job-form-page #createForm #date-start.form-control,
    .job-form-page #createForm #date-end.form-control {
        min-height: 2.75rem;
        border: 1px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        box-shadow: none !important;
        font-size: 13px !important;
        color: #0f1f3d !important;
    }
    .job-form-page #createForm #date-start.form-control:focus,
    .job-form-page #createForm #date-end.form-control:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
    }

    /* Select2 multiple — locations & skills */
    .job-form-page #createForm .select2-container {
        width: 100% !important;
        vertical-align: top;
    }
    .job-form-page #createForm .select2-container--default .select2-selection--multiple {
        min-height: 2.75rem;
        border-radius: 0.75rem !important;
        border: 1px solid #e5e7eb !important;
        background: #fff !important;
        padding: 4px 8px 6px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .job-form-page #createForm .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
    }
    .job-form-page #createForm .select2-container--default .select2-selection--multiple .select2-selection__choice {
        border: none !important;
        border-radius: 0.375rem;
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        font-size: 12px;
        font-weight: 600;
        padding: 3px 8px;
        margin: 4px 6px 0 0;
        line-height: 1.35;
    }
    .job-form-page #createForm .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #64748b !important;
        margin-right: 4px;
        font-weight: 700;
        border-right: none !important;
        padding-right: 4px;
    }
    .job-form-page #createForm .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #0f1f3d !important;
        background: transparent !important;
    }
    .job-form-page #createForm .select2-container--default .select2-search--inline .select2-search__field {
        margin-top: 6px !important;
        font-size: 13px !important;
        min-height: 1.5rem;
        color: #0f1f3d;
    }
    .job-form-page #createForm .select2-container--default .select2-selection--multiple .select2-selection__clear {
        margin-top: 6px;
        color: #8892a0;
    }

    /* Select2 dropdown is portaled to body */
    .job-form-select2-dd {
        border-radius: 0.75rem !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 10px 40px rgba(15, 31, 61, 0.12) !important;
        overflow: hidden;
    }
    .job-form-select2-dd .select2-results__option {
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        color: #0f1f3d;
    }
    .job-form-select2-dd .select2-results__option--highlighted {
        background: #2563eb !important;
        color: #fff !important;
    }
    .job-form-select2-dd .select2-search--dropdown .select2-search__field {
        border-radius: 0.5rem !important;
        border: 1px solid #e5e7eb !important;
        font-size: 13px;
        padding: 0.4rem 0.65rem;
        margin: 0.5rem;
        width: calc(100% - 1rem);
    }

    /*
     * Global custom.css hides [type=checkbox] (position:absolute; left:-9999px) for input+label siblings.
     * Job form wraps checkboxes inside <label>; reset here so they are visible, clickable, and show state.
     */
    .job-form-page #createForm label input[type="checkbox"] {
        position: static !important;
        left: auto !important;
        width: 1rem !important;
        height: 1rem !important;
        min-width: 1rem;
        min-height: 1rem;
        flex-shrink: 0;
        margin: 0;
        cursor: pointer;
        accent-color: #2563eb;
        -webkit-appearance: auto;
        appearance: auto;
    }

    .job-form-page #createForm label:has(input[type="checkbox"]:checked) {
        border-color: #93c5fd !important;
        background: #eff6ff !important;
        color: #1d4ed8 !important;
    }
</style>
