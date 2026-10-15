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
<style>
#ats-welcome{position:fixed;inset:0;margin:auto;width:min(600px,calc(100% - 32px));max-height:90vh;overflow:auto;border:0;border-radius:24px;background:white;color:#17233b;padding:52px 40px 32px;text-align:center;box-shadow:0 28px 100px #0f172a45}#ats-welcome::backdrop{background:#0f172a99;backdrop-filter:blur(3px)}#ats-welcome .welcome-avatar{display:grid;place-items:center;width:88px;height:88px;border-radius:50%;background:#cffafe;color:#155e75;font-size:36px;font-weight:700;margin:14px auto 24px}#ats-welcome h2{font-size:28px;line-height:1.3;font-weight:750;margin:12px 0;overflow-wrap:anywhere}#ats-welcome .welcome-eyebrow{font-size:10px;letter-spacing:1.5px;font-weight:700;color:#2563eb}#ats-welcome .welcome-divider{height:4px;width:48px;background:#2563eb;margin:24px auto;border-radius:4px}#welcome-description{font-size:15px;line-height:1.8;color:#64748b;margin-bottom:18px}#welcome-step{font-size:12px;color:#64748b;min-height:18px}#ats-welcome #welcome-tour{background:#2563eb;color:white;border:0;border-radius:11px;padding:13px 24px;font-weight:700;cursor:pointer}#ats-welcome #welcome-dismiss{background:transparent;color:#2563eb;border:0;padding:18px 10px;font-size:13px;cursor:pointer}#ats-welcome button:focus-visible{outline:3px solid #93c5fd;outline-offset:3px}.welcome-confetti{height:14px;position:absolute;left:24px;right:24px;top:20px;background:repeating-linear-gradient(115deg,transparent 0 24px,#2563eb 25px 30px,transparent 31px 55px,#f59e0b 56px 61px,transparent 62px 87px,#10b981 88px 93px,transparent 94px 118px,#ec4899 119px 124px);opacity:.7}@media(max-width:550px){#ats-welcome{padding:42px 24px 24px}#ats-welcome h2{font-size:23px}}
</style>
<script>
(function(){
const dialog=document.getElementById('ats-welcome'),tour=document.getElementById('welcome-tour'),dismiss=document.getElementById('welcome-dismiss'),step=document.getElementById('welcome-step');
const slides=[['Create your first job','Open Jobs from the sidebar to add a role, set its location and salary, and publish the opening.'],['Build your candidate pipeline','Review applications, upload CVs, and move candidates through your hiring stages.'],['Make this workspace yours','Add your company logo in Business Settings, configure email, and invite your team.']];
let index=-1;
tour.addEventListener('click',function(){index++;if(index>=slides.length){dismiss.click();return;}document.getElementById('welcome-title').textContent=slides[index][0];document.getElementById('welcome-description').textContent=slides[index][1];step.textContent='Step '+(index+1)+' of '+slides.length;tour.textContent=index===slides.length-1?'Start exploring':'Next step →';dismiss.textContent='Skip tour';});
dialog.addEventListener('cancel',function(event){event.preventDefault();dismiss.click();});
if(typeof dialog.showModal==='function')dialog.showModal();else dialog.setAttribute('open','');
})();
</script>
