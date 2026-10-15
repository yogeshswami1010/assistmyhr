<dialog id="ats-welcome" aria-labelledby="welcome-title" aria-describedby="welcome-description">
<div class="welcome-confetti" aria-hidden="true"></div>
<div class="welcome-avatar" aria-hidden="true">{{ mb_substr($user->name,0,1) }}</div>
<p class="welcome-eyebrow">YOUR NEXT GREAT HIRE STARTS HERE</p>
<h2 id="welcome-title">Welcome aboard, {{ $user->name }}!</h2>
<div class="welcome-divider" aria-hidden="true"></div>
<p id="welcome-description">Thank you for choosing AssistMyHR. Your workspace is ready. Let us show you how to organize jobs, manage candidates, and bring your team together.</p>
<p id="welcome-step" aria-live="polite"></p>
<button type="button" id="welcome-tour" autofocus>Show me around &rarr;</button>
<form method="post" action="{{ tenant_route('admin.welcome.dismiss') }}">@csrf<button type="submit" id="welcome-dismiss">No thanks, I'll explore it.</button></form>
</dialog>
<template id="ats-tour-template"><div id="ats-tour-highlight" aria-hidden="true"></div><section id="ats-tour-card" aria-labelledby="ats-tour-title"><p id="ats-tour-count" aria-live="polite"></p><h2 id="ats-tour-title"></h2><p id="ats-tour-description"></p><div class="ats-tour-controls"><button type="button" id="ats-tour-prev">&lsaquo; Previous</button><button type="button" id="ats-tour-next">Next &rsaquo;</button><button type="button" id="ats-tour-skip">Skip</button></div></section></template>
<style>
#ats-welcome.tour-active>[hidden]{display:none!important}#ats-welcome.tour-active{width:100vw;height:100dvh;max-width:none;max-height:none;margin:0;padding:0;border-radius:0;background:transparent;box-shadow:none;overflow:hidden}#ats-welcome.tour-active::backdrop{background:transparent;backdrop-filter:none}#ats-tour-highlight{position:fixed;border:3px solid #93c5fd;border-radius:10px;box-shadow:0 0 0 9999px #0f172a88;pointer-events:none}#ats-tour-card{position:fixed;width:min(400px,calc(100vw - 32px));background:white;border-radius:20px;padding:26px;text-align:left;box-shadow:0 20px 70px #0f172a30;max-height:calc(100dvh - 32px);overflow:auto}#ats-welcome #ats-tour-title{font-size:21px;margin:12px 0 16px}#ats-tour-count{font-size:13px;color:#64748b;margin:0}#ats-tour-description{font-size:14px;color:#64748b;line-height:1.8;margin-bottom:24px}.ats-tour-controls{display:flex;gap:8px;flex-wrap:wrap}#ats-tour-card button{background:#1e40af;color:white;border:0;border-radius:10px;padding:10px 14px;cursor:pointer;font:inherit;font-size:13px;font-weight:600}#ats-tour-card #ats-tour-skip{background:#f1f5f9;color:#17233b;margin-left:auto}#ats-tour-card button:disabled{opacity:.45;cursor:default}
</style>
<style>
#ats-welcome{position:fixed;inset:0;margin:auto;width:min(600px,calc(100% - 32px));max-height:90vh;overflow:auto;border:0;border-radius:24px;background:white;color:#17233b;padding:52px 40px 32px;text-align:center;box-shadow:0 28px 100px #0f172a45}#ats-welcome::backdrop{background:#0f172a99;backdrop-filter:blur(3px)}#ats-welcome .welcome-avatar{display:grid;place-items:center;width:88px;height:88px;border-radius:50%;background:#cffafe;color:#155e75;font-size:36px;font-weight:700;margin:14px auto 24px}#ats-welcome h2{font-size:28px;line-height:1.3;font-weight:750;margin:12px 0;overflow-wrap:anywhere}#ats-welcome .welcome-eyebrow{font-size:10px;letter-spacing:1.5px;font-weight:700;color:#2563eb}#ats-welcome .welcome-divider{height:4px;width:48px;background:#2563eb;margin:24px auto;border-radius:4px}#welcome-description{font-size:15px;line-height:1.8;color:#64748b;margin-bottom:18px}#welcome-step{font-size:12px;color:#64748b;min-height:18px}#ats-welcome #welcome-tour{background:#2563eb;color:white;border:0;border-radius:11px;padding:13px 24px;font-weight:700;cursor:pointer}#ats-welcome #welcome-dismiss{background:transparent;color:#2563eb;border:0;padding:18px 10px;font-size:13px;cursor:pointer}#ats-welcome button:focus-visible{outline:3px solid #93c5fd;outline-offset:3px}.welcome-confetti{height:14px;position:absolute;left:24px;right:24px;top:20px;background:repeating-linear-gradient(115deg,transparent 0 24px,#2563eb 25px 30px,transparent 31px 55px,#f59e0b 56px 61px,transparent 62px 87px,#10b981 88px 93px,transparent 94px 118px,#ec4899 119px 124px);opacity:.7}@media(max-width:550px){#ats-welcome{padding:42px 24px 24px}#ats-welcome h2{font-size:23px}}
</style>
<script>
(function(){
const dialog=document.getElementById('ats-welcome'),tour=document.getElementById('welcome-tour'),dismiss=document.getElementById('welcome-dismiss');
let index=0,steps=[],active=false;
function finish(){dismiss.click();}
function position(){
if(!active)return;
const target=steps[index].target,rect=target.getBoundingClientRect(),highlight=document.getElementById('ats-tour-highlight'),card=document.getElementById('ats-tour-card');
const left=Math.max(4,rect.left),top=Math.max(4,rect.top),right=Math.min(innerWidth-4,rect.right),bottom=Math.min(innerHeight-4,rect.bottom);
Object.assign(highlight.style,{left:left+'px',top:top+'px',width:Math.max(0,right-left)+'px',height:Math.max(0,bottom-top)+'px'});
const width=card.offsetWidth,height=card.offsetHeight;
let x=right+18,y=top;
if(x+width>innerWidth-16){x=Math.max(16,left-width-18);if(x+width>left){x=(innerWidth-width)/2;y=bottom+16;if(y+height>innerHeight-16)y=top-height-16;}}
card.style.left=Math.max(16,Math.min(x,innerWidth-width-16))+'px';card.style.top=Math.max(16,Math.min(y,innerHeight-height-16))+'px';
}
function render(){
const step=steps[index];step.target.scrollIntoView({block:'nearest',inline:'nearest',behavior:'instant'});
document.getElementById('ats-tour-count').textContent='Step '+(index+1)+' / '+steps.length;
document.getElementById('ats-tour-title').textContent=step.title;
document.getElementById('ats-tour-description').textContent=step.description;
document.getElementById('ats-tour-prev').disabled=index===0;
document.getElementById('ats-tour-next').textContent=index===steps.length-1?'Finish':'Next ›';
position();document.getElementById('ats-tour-next').focus();
}
tour.addEventListener('click',function(){
const definitions=[['#ra-sidebar','Navigation pane','Use the left sidebar to move between your ATS modules. Everything you need to manage recruitment is here.'],['[data-tour-target="jobs"]','Jobs','Create and publish jobs, manage open positions, and share application links with candidates.'],['[data-tour-target="applications"]','Job applications','Review applications and CVs, communicate with applicants, and move candidates through your hiring pipeline.'],['[data-tour-target="candidates"]','Candidate database','Keep your candidate records and CVs organized. Search your database when a new opportunity opens.'],['[data-tour-target="interviews"]','Interview schedule','Plan interviews and track upcoming meetings with candidates.'],['[data-tour-target="team"]','Your team','Invite recruiting colleagues and manage the people working in your ATS.'],['[data-tour-target="settings"]','Workspace settings','Set your company logo, configure email, manage your subscription, and connect your website through the Jobs API.']];
steps=definitions.map(([selector,title,description])=>({target:document.querySelector(selector),title,description})).filter(step=>step.target&&step.target.getClientRects().length&&getComputedStyle(step.target).visibility!=='hidden');
if(!steps.length){finish();return;}
Array.from(dialog.children).forEach(child=>child.hidden=true);
dialog.appendChild(document.getElementById('ats-tour-template').content.cloneNode(true));dialog.classList.add('tour-active');dialog.setAttribute('aria-labelledby','ats-tour-title');dialog.setAttribute('aria-describedby','ats-tour-description');active=true;
document.getElementById('ats-tour-prev').addEventListener('click',()=>{if(index>0){index--;render();}});
document.getElementById('ats-tour-next').addEventListener('click',()=>{if(index===steps.length-1){finish();return;}index++;render();});
document.getElementById('ats-tour-skip').addEventListener('click',finish);
render();
});
dialog.addEventListener('cancel',function(event){event.preventDefault();finish();});
window.addEventListener('resize',position);document.addEventListener('scroll',position,true);
if(typeof dialog.showModal==='function')dialog.showModal();else dialog.setAttribute('open','');
})();
</script>
