<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Candidate Registration | AssistMyHR</title>
<style>*{box-sizing:border-box}body{margin:0;background:#f6f8fb;color:#172033;font-family:Inter,Arial,sans-serif}.wrap{max-width:1120px;margin:auto;padding:24px}.card{background:#fff;border:1px solid #e3e8ef;border-radius:22px;box-shadow:0 16px 45px rgba(15,31,61,.08);overflow:hidden}.head{padding:28px 32px;color:#fff;background:linear-gradient(135deg,#075f4d,#079b73 58%,#17b890)}.head h1{margin:0 0 7px;font-size:27px}.head p{margin:0;color:#dbeafe;font-size:14px}.body{padding:28px 32px}.section{padding:22px 0;border-bottom:1px solid #edf0f4}.section:first-child{padding-top:0}.section h2{font-size:17px;margin:0 0 5px}.section>p{font-size:12px;color:#7b8798;margin:0 0 17px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.full{grid-column:1/-1}label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#3e4b5f}input,select,textarea{width:100%;border:1px solid #d9e0e9;border-radius:11px;padding:11px 12px;font:inherit;font-size:13px;background:#fbfcfe;outline:none}input:focus,select:focus,textarea:focus{border-color:#079b73;box-shadow:0 0 0 3px #d7f5eb;background:#fff}.radios{display:flex;gap:18px;padding:9px 0}.radios label,.check label{font-weight:500;margin:0;display:flex;align-items:flex-start;gap:8px}.radios input,.check input{width:auto;margin-top:2px}.check{display:grid;gap:12px;margin-top:18px}.errors{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;padding:14px 18px;border-radius:12px;margin-bottom:18px;font-size:13px}.success{text-align:center;padding:55px 25px}.success b{display:block;font-size:22px;color:#0f766e;margin-bottom:8px}.submit{border:0;border-radius:12px;background:#079b73;color:#fff;padding:13px 24px;font-weight:800;font-size:14px;cursor:pointer;box-shadow:0 8px 20px rgba(7,155,115,.24)}.actions{display:flex;justify-content:flex-end;padding-top:22px}.hp{position:absolute;left:-9999px}@media(max-width:650px){.wrap{padding:10px}.head,.body{padding:22px 18px}.grid{grid-template-columns:1fr}.full{grid-column:auto}}</style></head><body><div class="wrap"><div class="card">
@if(session('registration_success'))<div class="success"><b>Registration submitted successfully</b><p>Thank you. The AssistMyHR team will review your information and contact you.</p></div>
@else<div class="head"><h1>Candidate Registration</h1><p>Register with AssistMyHR</p></div><div class="body">
@if($errors->any())<div class="errors"><b>Please correct the highlighted information.</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form id="consortium-registration-form" method="POST" action="{{ route('api.consortium-registration.store') }}" enctype="multipart/form-data"><input class="hp" name="website" tabindex="-1" autocomplete="off">
<section class="section"><h2>Basic Information</h2><p>Please provide your contact details.</p><div class="grid">
<div><label>First Name *</label><input name="first_name" value="{{ old('first_name') }}" required></div><div><label>Last Name *</label><input name="last_name" value="{{ old('last_name') }}" required></div>
<div><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" required></div><div><label>Phone Number *</label><input name="phone" value="{{ old('phone') }}" required></div>
<div><label>Gender</label><select name="gender"><option value="">Select</option>@foreach(['Male','Female','Other','Prefer not to say'] as $v)<option @selected(old('gender')===$v)>{{ $v }}</option>@endforeach</select></div><div><label>Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
<div><label>Street Address</label><input name="street_address" value="{{ old('street_address') }}"></div><div><label>City *</label><input name="city" value="{{ old('city') }}" required></div></div></section>
<section class="section"><h2>Work Eligibility</h2><p>Tell us about your eligibility to work in Canada.</p><div class="grid"><div><label>Legally eligible to work in Canada? *</label><div class="radios"><label><input type="radio" name="eligible_to_work_canada" value="1" required> Yes</label><label><input type="radio" name="eligible_to_work_canada" value="0" required> No</label></div></div><div><label>Status in Canada</label><select name="status_in_canada"><option value="">Select</option>@foreach(['Canadian Citizen','Permanent Resident','Work Permit','Study Permit','Refugee/Protected Person','Other'] as $v)<option @selected(old('status_in_canada')===$v)>{{ $v }}</option>@endforeach</select></div></div></section>
<section class="section"><h2>Job Preferences</h2><p>Tell us what kind of work you prefer.</p><div class="grid"><div><label>Preferred Job Type</label><select name="preferred_job_type"><option value="">Select</option>@foreach(['Full-Time','Part-Time','Temporary','Contract','Permanent'] as $v)<option @selected(old('preferred_job_type')===$v)>{{ $v }}</option>@endforeach</select></div><div><label>Mode of Commute</label><select name="commute_mode"><option value="">Select</option>@foreach(['Own Vehicle','Public Transit','Rideshare','Walking/Cycling','Other'] as $v)<option @selected(old('commute_mode')===$v)>{{ $v }}</option>@endforeach</select></div></div></section>
<section class="section"><h2>Experience & Skills</h2><p>Share your professional background.</p><div class="grid"><div><label>Total Years of Experience</label><input type="number" step="0.5" min="0" name="years_experience" value="{{ old('years_experience') }}"></div><div><label>Industry/Field of Expertise</label><input name="industry_expertise" value="{{ old('industry_expertise') }}"></div><div class="full"><label>Resume <span aria-hidden="true">*</span> (PDF, DOC, DOCX or RTF, maximum 10 MB)</label><input type="file" name="resume" accept=".pdf,.doc,.docx,.rtf" required aria-required="true"></div></div></section>
<section class="section"><h2>Availability</h2><p>Let us know your scheduling availability.</p><div class="grid"><div><label>Available to work weekends? *</label><div class="radios"><label><input type="radio" name="available_weekends" value="1" required> Yes</label><label><input type="radio" name="available_weekends" value="0" required> No</label></div></div><div><label>Available for night shifts? *</label><div class="radios"><label><input type="radio" name="available_night_shifts" value="1" required> Yes</label><label><input type="radio" name="available_night_shifts" value="0" required> No</label></div></div></div></section>
<section class="section"><h2>Additional Information</h2><div class="grid"><div><label>How did you hear about us?</label><select name="referral_source"><option value="">Select</option>@foreach(['Google','Indeed','LinkedIn','Facebook','Friend or Referral','Other'] as $v)<option @selected(old('referral_source')===$v)>{{ $v }}</option>@endforeach</select></div><div class="full"><label>Additional Information</label><textarea name="additional_information" rows="4">{{ old('additional_information') }}</textarea></div></div>
<div class="check"><label><input type="checkbox" name="information_certified" value="1" required> I certify that the submitted information is true and correct.</label><label><input type="checkbox" name="agreement_accepted" value="1" required> I have read and understood the Applicant Agreement. <a href="https://consortiumstaffing.ca/terms-and-condition/" target="_blank" rel="noopener noreferrer" style="color:#079b73;font-weight:700;text-decoration:underline;">(Click Here to read)</a></label><label><input type="checkbox" name="sms_consent" value="1"> I agree to receive SMS and calls from AssistMyHR and can opt out by replying STOP.</label></div></section>
<div id="api-form-errors" class="errors" style="display:none"></div><div class="actions"><button id="registration-submit" class="submit" type="submit">Submit Registration</button></div></form></div>@endif
</div></div><script>
(function(){
    var form=document.getElementById('consortium-registration-form');
    if(!form)return;
    var button=document.getElementById('registration-submit');
    var errors=document.getElementById('api-form-errors');
    form.addEventListener('submit',async function(event){
        event.preventDefault();
        errors.style.display='none';
        errors.innerHTML='';
        button.disabled=true;
        button.textContent='Submitting...';
        try{
            var response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json'}});
            var result=await response.json();
            if(!response.ok){
                var messages=[];
                Object.keys(result.errors||{}).forEach(function(key){messages=messages.concat(result.errors[key]);});
                errors.innerHTML='<b>Please correct the following:</b><ul>'+messages.map(function(message){return '<li>'+String(message).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];})+'</li>';}).join('')+'</ul>';
                errors.style.display='block';
                errors.scrollIntoView({behavior:'smooth',block:'center'});
                return;
            }
            document.querySelector('.body').innerHTML='<div class="success"><b>Registration submitted successfully</b><p>Thank you. The AssistMyHR team will review your information and contact you.</p></div>';
            window.scrollTo({top:0,behavior:'smooth'});
        }catch(error){
            errors.textContent='The registration could not be submitted. Please check your connection and try again.';
            errors.style.display='block';
        }finally{
            button.disabled=false;
            button.textContent='Submit Registration';
        }
    });
})();
</script></body></html>
