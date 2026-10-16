@if($user->cans('add_question'))
<dialog id="job-question-dialog" aria-labelledby="job-question-title" style="width:min(540px,calc(100% - 32px));border:0;border-radius:20px;padding:28px;max-height:85vh;overflow:auto">
<form id="job-question-form">
<h2 id="job-question-title" style="font-size:20px;font-weight:700;margin-bottom:20px">Create custom question</h2>
@csrf
<label>Question<input name="question" required maxlength="1000"></label>
<label>Answer type<select name="type" id="jq-type"><option value="text">Text</option><option value="file">File upload</option><option value="radio">Multiple choice</option></select></label>
<label>Required<select name="required"><option value="yes">Yes</option><option value="no">No</option></select></label>
<div id="jq-options" hidden><label>Options (one per line)<textarea id="jq-options-text" rows="4" placeholder="Yes&#10;No"></textarea></label><label style="display:flex;gap:8px;align-items:center"><input style="width:auto" id="jq-knockout" type="checkbox">Knockout question</label><label>Knockout answer<select id="jq-knockout-answer"><option value="">Select an answer</option></select></label></div>
<p id="jq-error" role="alert" style="color:#be123c;white-space:pre-line"></p>
<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px"><button type="button" id="jq-cancel">Cancel</button><button type="submit" id="jq-save" style="background:#2563eb;color:white">Create question</button></div>
</form></dialog>
<style>#job-question-dialog::backdrop{background:#0f172a88}#job-question-form label{display:block;font-size:13px;font-weight:600;margin-top:14px}#job-question-form input,#job-question-form select,#job-question-form textarea{display:block;width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px;margin-top:6px;font:inherit}#job-question-form button{border:1px solid #e2e8f0;border-radius:10px;padding:10px 16px;cursor:pointer}#job-question-dialog [hidden]{display:none!important}#job-question-form :focus-visible{outline:2px solid #93c5fd;outline-offset:2px}</style>
<script>
(function(){
const dialog=document.getElementById('job-question-dialog'),form=document.getElementById('job-question-form'),type=document.getElementById('jq-type'),options=document.getElementById('jq-options-text'),answer=document.getElementById('jq-knockout-answer'),error=document.getElementById('jq-error'),save=document.getElementById('jq-save');
document.getElementById('job-add-question').addEventListener('click',()=>{error.textContent='';dialog.showModal();});
document.getElementById('jq-cancel').addEventListener('click',()=>dialog.close());
type.addEventListener('change',()=>{document.getElementById('jq-options').hidden=type.value!=='radio';});
options.addEventListener('input',()=>{const selected=answer.value;answer.replaceChildren(new Option('Select an answer',''));options.value.split('\n').map(x=>x.trim()).filter(Boolean).forEach(value=>answer.add(new Option(value,value)));answer.value=selected;});
form.addEventListener('submit',async function(event){
event.preventDefault();if(save.disabled)return;error.textContent='';save.disabled=true;
const data=new FormData(form);data.set('inline_job','1');
if(type.value==='radio'){options.value.split('\n').map(x=>x.trim()).filter(Boolean).forEach(value=>data.append('radio_options[]',value));data.set('is_knockout',document.getElementById('jq-knockout').checked?'1':'0');if(document.getElementById('jq-knockout').checked)data.set('knockout_answer',answer.value);}
try{
const response=await fetch(@json(tenant_route('admin.questions.store')),{method:'POST',body:data,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}}),result=await response.json();
if(!response.ok){error.textContent=result.errors?Object.values(result.errors).flat().join('\n'):(result.message||'Could not create question.');return;}
if(!result.question){error.textContent='Could not create question. Please try again.';return;}
const question=result.question,row=document.createElement('label'),checkbox=document.createElement('input'),text=document.createElement('span');
row.className='question-item flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4';row.dataset.questionText=question.question.toLowerCase();checkbox.type='checkbox';checkbox.name='question[]';checkbox.value=question.id;checkbox.checked=true;checkbox.className='question-checkbox';checkbox.addEventListener('change',()=>window.updateSelectedCount());text.textContent=question.question+' · '+question.type+(question.required==='yes'?' · Required':'')+(question.is_knockout?' · Knockout':'');row.append(checkbox,text);document.getElementById('questions-empty')?.remove();document.getElementById('question-list').prepend(row);document.getElementById('question-search').value='';window.filterQuestions();dialog.close();form.reset();document.getElementById('jq-options').hidden=true;
}catch(failure){error.textContent='Could not save the question. Check your connection and try again.';}finally{save.disabled=false;}
});
})();
</script>
@endif
