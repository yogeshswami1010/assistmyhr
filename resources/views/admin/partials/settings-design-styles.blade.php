{{-- Shared form + card styling for admin settings pages (matches business settings). --}}
<style>
    .bs-f-input { width: 100%; border: 1.5px solid #E2DED8; border-radius: 11px; padding: 10px 14px; font-size: 13.5px; color: #1A1E2E; background: #fff; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
    .bs-f-input.bs-f-input-with-icon { padding-left: 2.5rem; }
    .bs-f-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .bs-f-input::placeholder { color: #C4CBD4; }
    .bs-f-input.bs-readonly { background: #F8F9FB; color: #5A6478; }
    .bs-f-sel { width: 100%; border: 1.5px solid #E2DED8; border-radius: 11px; padding: 10px 32px 10px 14px; font-size: 13.5px; color: #1A1E2E; background: #fff; outline: none; appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239CA3AF' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; transition: border-color 0.2s, box-shadow 0.2s; }
    .bs-f-sel:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .bs-f-textarea { width: 100%; border: 1.5px solid #E2DED8; border-radius: 11px; padding: 10px 14px; font-size: 13.5px; color: #1A1E2E; background: #fff; outline: none; resize: vertical; min-height: 90px; transition: border-color 0.2s, box-shadow 0.2s; }
    .bs-f-textarea:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .bs-upload-zone { border: 1.5px dashed #D1D9E8; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px; cursor: pointer; background: #F8FAFF; transition: border-color 0.2s, background 0.2s; }
    .bs-upload-zone:hover { border-color: #2563EB; background: #EFF6FF; }
    .bs-set-card { overflow: hidden; border-radius: 18px; border: 1px solid #E8E6E1; background: #fff; }
    .bs-set-card--rich { overflow: visible; }
    .bs-set-card--rich .bs-set-card-hd { position: relative; z-index: 2; }
    .bs-set-card-hd { display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #F0EEE9; padding: 16px 24px; }
    .bs-set-card-ic { display: flex; height: 36px; width: 36px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 10px; background: #EFF6FF; }
    .bs-set-lbl { margin-bottom: 6px; display: block; font-size: 11.5px; font-weight: 700; letter-spacing: 0.02em; color: #5A6478; }
    .bs-upload-preview { width: 52px; height: 52px; border-radius: 10px; object-fit: cover; border: 2px solid #E2DED8; background: #F1F3F7; flex-shrink: 0; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .bs-upload-preview img { width: 100%; height: 100%; object-fit: cover; }
    .bs-toggle-track { width: 38px; height: 21px; border-radius: 999px; background: #D1D9E8; position: relative; flex-shrink: 0; transition: background 0.2s; }
    .peer:checked ~ .bs-toggle-track { background: #2563EB; }
    .bs-toggle-thumb { position: absolute; top: 2.5px; left: 2.5px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: left 0.2s; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15); }
    .peer:checked ~ .bs-toggle-track .bs-toggle-thumb { left: 19.5px; }
    #gmap_geocoding { min-height: 220px; border-radius: 14px; overflow: hidden; }
</style>
