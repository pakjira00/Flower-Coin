let S=null,slot=0,tier=0,timerInt=null;
const $=id=>document.getElementById(id);
async function call(a,d={}){const o={method:'POST'};if(Object.keys(d).length){o.headers={'Content-Type':'application/x-www-form-urlencoded'};o.body=new URLSearchParams(d)}const r=await fetch('api.php?action='+a,o),j=await r.json();if(!j.ok)throw Error(j.message||'เกิดข้อผิดพลาด');return j}
async function load(){const j=await fetch('api.php?action=state').then(r=>r.json());if(!j.ok){location.href='index.php';return}S=j;render()}
function render(){const u=S.user,f=S.flowers;document.body.className=u.mode;$('username').textContent='👤 '+u.username;$('coins').textContent=Number(u.coins).toFixed(2);$('pendingCoins').textContent=Number(u.pending_coins||0).toFixed(2);let inc=0,count=0;
$('garden').innerHTML=S.garden.map(x=>{let q=x.flower_tier===null?null:f[x.flower_tier];if(q){inc+=Number(q.rate);count++}return `<button class="slot ${slot==x.slot?'selected':''}" onclick="slot=${x.slot};render()">${q?`<span>${q.emoji}</span><b>${q.thai}</b><small>${q.rate.toFixed(2)}/10s</small>`:'<span>＋</span><small>ว่าง</small>'}</button>`}).join('');
$('income').textContent=inc.toFixed(2);$('gardenCount').textContent=count+' / 9';$('inventoryTotal').textContent=S.inventory.reduce((a,x)=>a+Number(x.quantity),0);
$('dropRates').innerHTML=f.map((q,i)=>`<div class="drop-row"><div class="drop-flower"><span class="drop-emoji">${q.emoji}</span><span><b>Tier ${i+1}</b><small>${q.thai}</small></span></div><div class="drop-bar"><i style="width:${Number(q.drop)}%"></i></div><strong>${Number(q.drop).toFixed(0)}%</strong></div>`).join('');
$('inventory').innerHTML=S.inventory.map(x=>{let q=f[x.flower_tier];return `<button class="inv ${tier==x.flower_tier?'selected':''}" onclick="tier=${x.flower_tier};render()">${q.emoji}<b>${q.thai}</b><small>× ${x.quantity}</small></button>`}).join('');
$('mergeList').innerHTML=S.inventory.filter(x=>x.flower_tier<4&&x.quantity>=4).map(x=>{let q=f[x.flower_tier];return `<button onclick="merge(${x.flower_tier})">🔄 ${q.thai} ×4 → ${f[x.flower_tier+1].thai} <small>${q.merge}%</small></button>`}).join('')||'<span class="hint">ยังไม่มี Tier ที่มีครบ 4 ดอก</span>';
$('modeBadge').textContent=u.mode==='day'?'☀️ DAY':'🌙 NIGHT';$('modeTitle').textContent=u.mode==='day'?'ตอนเช้า — สวนกำลังสร้าง Coin':'ตอนกลางคืน — จัดสวนได้';$('modeHint').textContent=u.mode==='day'?'สวนถูกล็อก Coin จะสะสมในช่องด้านบนก่อน':'ปลูก ขุด เปิดกล่อง และ Merge ได้';$('modeBtn').textContent=u.mode==='day'?'🌙 กลับตอนกลางคืน':'☀️ เริ่มตอนเช้า';updateTimer()}
function updateTimer(){
 if(!S){$('timer').textContent='00:00:00';return;}
 let t=0;
 // IMPORTANT: last_tick is updated every 10 seconds for Coin settlement.
 // Never use it for the visible Day timer or the timer would reset every 10 seconds.
 if(S.user.mode==='day'&&S.user.day_started_at){
   const started=Date.parse(String(S.user.day_started_at).replace(' ','T'));
   if(Number.isFinite(started)) t=Math.max(0,Math.floor((Date.now()-started)/1000));
 }
 const h=Math.floor(t/3600),m=Math.floor((t%3600)/60),sec=t%60;
 $('timer').textContent=[h,m,sec].map(v=>String(v).padStart(2,'0')).join(':');
}
async function action(a,d={}){try{const j=await call(a,d);$('gameMsg').textContent=j.message||'สำเร็จ';await load();history();return j}catch(e){$('gameMsg').textContent=e.message;return null}}
$('plantBtn').onclick=()=>action('plant',{slot,tier});$('digBtn').onclick=()=>action('dig',{slot});
$('boxBtn').onclick=async()=>{const j=await action('box');if(j&&j.flower)showFlowerReward(j.flower,j.tier)};
$('modeBtn').onclick=async()=>{try{const to=S.user.mode==='day'?'night':'day';const j=await call('mode',{mode:to});await load();if(to==='night')showSummary(j.gain||0);history()}catch(e){$('gameMsg').textContent=e.message}};
async function merge(t){
 try{
  const j=await call('merge',{tier:t});
  await load();
  history();
  if(j.success){
   showMergeResult(true,j.flower,j.tier,t);
  }else{
   showMergeResult(false,j.flower,j.tier,t);
  }
 }catch(e){$('gameMsg').textContent=e.message}
}
async function history(){const j=await fetch('api.php?action=history').then(r=>r.json());$('history').innerHTML=j.items.map(x=>`<div class="history-row"><span>${x.description}</span><b>${Number(x.amount)>0?'+':''}${Number(x.amount).toFixed(2)}</b><small>${x.created_at}</small></div>`).join('')}
function showSummary(n){$('summaryCoins').textContent='+'+Number(n).toFixed(2)+' Coin';$('summaryModal').classList.remove('hidden')}
function showFlowerReward(f,t){$('rewardEmoji').textContent=f.emoji||'🌸';$('rewardName').textContent=f.thai||f.name||'ดอกไม้ใหม่';$('rewardTier').textContent='Tier '+(Number(t)+1);$('rewardModal').classList.remove('hidden')}
function showMergeResult(success,f,resultTier,fromTier){
 $('mergeTitle').textContent=success?'🎉 Merge สำเร็จ!':'😢 Merge ไม่สำเร็จ';
 $('mergeEmoji').textContent=f?.emoji||'🌸';
 $('mergeName').textContent=f?.thai||'ดอกไม้ Tier '+(Number(resultTier)+1);
 $('mergeTier').textContent='Tier '+(Number(resultTier)+1);
 $('mergeMessage').textContent=success
  ? 'ใช้ดอกไม้ Tier '+(Number(fromTier)+1)+' จำนวน 4 ดอก และได้รับดอกไม้ใหม่ 1 ดอก'
  : 'ใช้ดอกไม้ Tier '+(Number(fromTier)+1)+' จำนวน 4 ดอก แต่การ Merge ไม่สำเร็จ จึงได้รับดอกไม้ Tier เดิมกลับมา 1 ดอก';
 $('mergeModal').classList.remove('hidden');
}
$('closeSummary').onclick=()=>$('summaryModal').classList.add('hidden');
$('closeReward').onclick=()=>$('rewardModal').classList.add('hidden');
$('closeMerge').onclick=()=>$('mergeModal').classList.add('hidden');
$('logout').onclick=async()=>{await call('logout');location.href='index.php'};
load();history();setInterval(updateTimer,1000);
// Poll frequently so the UI notices the exact 10-second server accrual boundary.
// The server still awards Coin only in whole 10-second blocks; polling does not create extra Coin.
let refreshBusy=false;
setInterval(async()=>{
 if(S?.user.mode!=='day'||refreshBusy)return;
 refreshBusy=true;
 try{await load()}finally{refreshBusy=false}
},1000);