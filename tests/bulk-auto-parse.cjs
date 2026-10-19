const fs=require('fs'),vm=require('vm'),assert=require('assert/strict');
const source=fs.readFileSync('resources/views/admin/job-applications/create.blade.php','utf8');
const start=source.indexOf('        var bulkParsingCount =');
const end=source.indexOf('        /*',start);
const started=[];
const ctx={bulkQueue:Array.from({length:5},(_,id)=>({id,status:'pending'})),bulkParseItem(index){ctx.bulkQueue[index].status='parsing';ctx.bulkParsingCount++;started.push(index);}};
vm.createContext(ctx);vm.runInContext(source.slice(start,end),ctx);
ctx.bulkPumpParsing();assert.deepEqual(started,[0,1]);
ctx.bulkPumpParsing();assert.equal(started.length,2);
for(const index of [0,1,2]){ctx.bulkQueue[index].status='done';ctx.bulkParsingCount--;ctx.bulkPumpParsing();}
assert.deepEqual(started,[0,1,2,3,4]);assert.equal(ctx.bulkParsingCount,2);
ctx.bulkQueue[3].status='error';ctx.bulkParsingCount--;ctx.bulkPumpParsing();assert.equal(started.length,5);
ctx.bulkQueue[4].status='done';ctx.bulkParsingCount--;ctx.bulkPumpParsing();assert.equal(ctx.bulkParsingCount,0);
console.log('Automatic parsing checks passed: all five queued CVs start, two-request limit, no duplicate starts, failures release slots.');
