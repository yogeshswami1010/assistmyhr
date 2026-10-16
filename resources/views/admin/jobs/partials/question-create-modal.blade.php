@if($user->cans('add_question'))
<dialog id="job-question-dialog" aria-labelledby="job-question-title" style="width:min(820px,calc(100% - 32px));border:0;border-radius:20px;padding:28px;height:min(820px,92vh);max-height:92vh;overflow:auto">
<form id="job-question-form">
<h2 id="job-question-title" style="font-size:20px;font-weight:700;margin-bottom:20px">Create custom question</h2>
@csrf
<label>Question<input name="question" required maxlength="1000"></label>
<label>Answer type<select name="type" id="jq-type"><option value="text">Text</option><option value="file">File upload</option><option value="radio">Multiple choice</option></select></label>
<label>Required<select name="required"><option value="yes">Yes</option><option value="no">No</option></select></label>
<div id="jq-options" hidden>
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px"><strong style="font-size:13px">Answer options</strong><button type="button" id="jq-add-option">Add option</button></div>
<p style="font-size:12px;color:#64748b;margin:8px 0">Add at least two options. Enable knockout and choose the answer that should reject the application.</p>
<label style="display:flex;gap:8px;align-items:center"><input name="is_knockout" value="1" style="width:auto" id="jq-knockout" type="checkbox">Knockout question</label>
<div id="jq-option-rows"></div>
</div>
<p id="jq-error" role="alert" style="color:#be123c;white-space:pre-line"></p>
<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px"><button type="button" id="jq-cancel">Cancel</button><button type="submit" id="jq-save" style="background:#2563eb;color:white">Create question</button></div>
</form></dialog>
<style>#job-question-form input[type="checkbox"],#job-question-form input[type="radio"]{position:static!important;left:auto!important;right:auto!important;top:auto!important;opacity:1!important;visibility:visible!important;display:inline-block!important;width:18px!important;height:18px!important;min-width:18px;min-height:18px;padding:0!important;margin:0!important;flex-shrink:0;transform:none!important;clip:auto!important;clip-path:none!important;-webkit-appearance:auto!important;appearance:auto!important;accent-color:#2563eb;cursor:pointer}#job-question-form .jq-knockout-choice{border:1px solid #fecaca;border-radius:10px;padding:10px;color:#b91c1c;background:#fff7f7;cursor:pointer}#job-question-form .jq-knockout-choice input{accent-color:#dc2626}#job-question-form .jq-option-row{padding:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px}#job-question-form .jq-option-row>input{margin:0}#job-question-form #jq-knockout{accent-color:#dc2626}#job-question-dialog::backdrop{background:#0f172a88}#job-question-form label{display:block;font-size:13px;font-weight:600;margin-top:14px}#job-question-form input,#job-question-form select,#job-question-form textarea{display:block;width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px;margin-top:6px;font:inherit}#job-question-form button{border:1px solid #e2e8f0;border-radius:10px;padding:10px 16px;cursor:pointer}#job-question-dialog [hidden]{display:none!important}#job-question-form :focus-visible{outline:2px solid #93c5fd;outline-offset:2px}</style>
<script>
(function(){
const dialog=document.getElementById('job-question-dialog'),form=document.getElementById('job-question-form'),type=document.getElementById('jq-type'),rows=document.getElementById('jq-option-rows'),knockout=document.getElementById('jq-knockout'),error=document.getElementById('jq-error'),save=document.getElementById('jq-save');
function addOption(value=''){
const row=document.createElement('div');row.className='jq-option-row';row.style.cssText='display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap';
const input=document.createElement('input');input.name='radio_options[]';input.placeholder='Option label';input.value=value;input.maxLength=150;input.style.cssText='flex:1;min-width:140px';
const label=document.createElement('label');label.className='jq-knockout-choice';label.style.cssText='display:flex;gap:6px;align-items:center;margin:0;font-size:12px';const radio=document.createElement('input');radio.type='radio';radio.name='knockout_answer';radio.value=value;radio.style.width='auto';label.append(radio,document.createTextNode('Set as knockout'));
const remove=document.createElement('button');remove.type='button';remove.textContent='Remove';remove.addEventListener('click',()=>row.remove());input.addEventListener('input',()=>radio.value=input.value.trim());row.append(input,label,remove);rows.append(row);label.hidden=!knockout.checked;
}
function reset(){form.reset();rows.replaceChildren();addOption();addOption();document.getElementById('jq-options').hidden=true;error.textContent='';}
document.getElementById('job-add-question').addEventListener('click',()=>{error.textContent='';dialog.showModal();});
document.getElementById('jq-cancel').addEventListener('click',()=>dialog.close());
document.getElementById('jq-add-option').addEventListener('click',()=>addOption());
type.addEventListener('change',()=>{document.getElementById('jq-options').hidden=type.value!=='radio';if(type.value!=='radio'){knockout.checked=false;rows.querySelectorAll('[name="knockout_answer"]').forEach(input=>input.checked=false);}rows.querySelectorAll('.jq-knockout-choice').forEach(label=>label.hidden=!knockout.checked);});
knockout.addEventListener('change',()=>{rows.querySelectorAll('.jq-knockout-choice').forEach(label=>label.hidden=!knockout.checked);if(!knockout.checked)rows.querySelectorAll('[name="knockout_answer"]').forEach(input=>input.checked=false);});
form.addEventListener('submit',async function(event){
event.preventDefault();if(save.disabled)return;error.textContent='';
rows.querySelectorAll('.jq-option-row').forEach(row=>row.querySelector('[name="knockout_answer"]').value=row.querySelector('[name="radio_options[]"]').value.trim());
const data=new FormData(form);data.set('inline_job','1');
if(type.value==='radio'){
const options=data.getAll('radio_options[]').map(value=>value.trim());
if(options.length<2||options.some(value=>!value||value.includes(','))){error.textContent='Enter at least two non-empty options without commas.';return;}
if(new Set(options.map(value=>value.toLowerCase())).size!==options.length){error.textContent='Each answer option must be different.';return;}
if(knockout.checked&&!data.get('knockout_answer')){error.textContent='Select the knockout answer before creating the question.';return;}
data.delete('radio_options[]');options.forEach(value=>data.append('radio_options[]',value));
}else{data.delete('radio_options[]');data.delete('is_knockout');data.delete('knockout_answer');}
save.disabled=true;
try{
const response=await fetch(@json(tenant_route('admin.questions.store')),{method:'POST',body:data,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}}),result=await response.json();
if(!response.ok){error.textContent=result.errors?Object.values(result.errors).flat().join('\n'):(result.message||'Could not create question.');return;}
if(!result.question || !result.view){error.textContent='Could not create question. Please try again.';return;}
const template=document.createElement('template');template.innerHTML=result.view.trim();const row=template.content.firstElementChild;
document.getElementById('questions-empty')?.remove();document.getElementById('question-list').prepend(row);document.getElementById('question-search').value='';window.filterQuestions();dialog.close();reset();
}catch(failure){error.textContent='Could not save the question. Check your connection and try again.';}finally{save.disabled=false;}
});
reset();
})();
</script>
@endif
