const fs = require('fs');
const vm = require('vm');
const assert = require('assert/strict');
const source = fs.readFileSync('resources/views/admin/job-applications/create.blade.php','utf8');
const snippet = source.slice(source.indexOf('        var bulkSavingAll ='), source.indexOf('        function bulkSaveCurrent()'));
async function run(queue, failName) {
  const fields = {'bulk-save-all-status':{textContent:''},'bulk-job-id':{value:'22'},'bulk-location-id':{value:'3'},'bulk-job-select':{value:'44'},'bulk-save-all-job':{},'bulk-review-pane':{inert:false},'bulk-approve-all':{disabled:false}};
  const sent=[];
  const ctx={FormData,document:{getElementById:id=>fields[id]},bulkActive:1,bulkFiling:'job',bulkQueue:queue,bulkCsrfToken:'token',bulkStoreUrl:'/save',bulkSnapshotForm(){},bulkRenderQueue(){},bulkUpdateProgress(){},bulkUpdateBatchBar(){},bulkFindNext(){return -1},bulkShowAllDoneState(){},bulkSelectItem(){},$:{ajax:async({data})=>{sent.push(data);if(data.get('full_name')===failName)throw {responseJSON:{message:'Invalid email'}};return {status:'success',data:{id:100+sent.length}};}}};
  vm.createContext(ctx);vm.runInContext(snippet,ctx);await ctx.bulkSaveAllToJob();
  assert.equal(fields['bulk-review-pane'].inert,false);
  assert.equal(fields['bulk-save-all-job'].disabled,false);
  return {sent,ctx,fields};
}
(async()=>{
 const original={saved:true,applicationId:8,jobId:'11',status:'done',parsed:{full_name:'Already saved'}};
 const queue=[original,...['A','B','C','D'].map(name=>({status:'done',saved:false,parsed:{full_name:name,email:name+'@test.com'},notes:['Own note']}))];
 const first=await run(queue);
 assert.equal(first.sent.length,4);
 assert.equal(original.jobId,'11');assert.equal(original.applicationId,8);
 first.sent.forEach(data=>{assert.equal(data.get('job_id'),'22');assert.equal(data.get('location_id'),'3');assert.equal(data.get('entry_type'),'applicant');assert.equal(data.get('notes[]'),'Own note');});
 assert(queue.slice(1).every(item=>item.saved&&item.applicationId));
 const retryQueue=[{status:'done',saved:false,parsed:{full_name:'Good'}},{status:'done',saved:false,parsed:{full_name:'Bad'}}];
 await run(retryQueue,'Bad');assert.equal(retryQueue[0].saved,true);assert.equal(retryQueue[1].saved,false);
 const retry=await run(retryQueue);assert.equal(retry.sent.length,1);assert.equal(retry.sent[0].get('full_name'),'Bad');
 console.log('Save All checks passed: selected job, saved applicants untouched, per-CV notes, partial failure, safe retry.');
})().catch(error=>{console.error(error);process.exit(1)});
