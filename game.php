<?php
require_once __DIR__ . '/config.php';
$id = uid();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Flower Coin - เกมปลูกดอกไม้สะสมเหรียญ</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, sans-serif;
      background: #0f172a;
      color: #f8fafc;
      min-height: 100vh;
      padding: 20px;
    }
    .container { max-width: 900px; margin: 0 auto; }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #1e293b;
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
    }
    .user-info { display: flex; align-items: center; gap: 10px; }
    .btn {
      padding: 8px 14px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: bold;
      transition: opacity 0.2s;
    }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-primary { background: #38bdf8; color: #0f172a; }
    .btn-success { background: #22c55e; color: #fff; }
    .btn-danger { background: #ef4444; color: #fff; }
    .btn-secondary { background: #64748b; color: #fff; }

    .stats-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: #1e293b;
      padding: 14px;
      border-radius: 10px;
      text-align: center;
    }
    .stat-card h4 { font-size: 13px; color: #94a3b8; margin-bottom: 4px; }
    .stat-card p { font-size: 20px; font-weight: bold; color: #facc15; }

    .garden-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }
    .slot {
      background: #1e293b;
      aspect-ratio: 1;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      border: 2px dashed #334155;
      position: relative;
    }
    .slot.occupied { border-style: solid; border-color: #38bdf8; }
    .slot .emoji { font-size: 40px; margin-bottom: 4px; }
    .slot .name { font-size: 12px; color: #cbd5e1; }

    .inventory-section {
      background: #1e293b;
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 20px;
    }
    .inventory-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 10px;
      margin-top: 10px;
    }
    .inv-item {
      background: #0f172a;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
      border: 1px solid #334155;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      place-items: center;
      z-index: 1000;
    }
    .modal-content {
      background: #1e293b;
      padding: 24px;
      border-radius: 14px;
      width: 90%;
      max-width: 360px;
      text-align: center;
    }
    .modal-content input {
      width: 100%;
      padding: 10px;
      margin: 14px 0;
      border-radius: 8px;
      border: 1px solid #475569;
      background: #0f172a;
      color: white;
    }
  </style>
</head>
<body>

<div class="container">
  <header>
    <div class="user-info">
      <span>ผู้เล่น: <strong id="usernameDisplay">กำลังโหลด...</strong></span>
      <button class="btn btn-secondary" id="btnEditUsername" style="font-size: 12px; padding: 4px 8px;">✏️ แก้ไขชื่อ</button>
    </div>
    <div>
      <button class="btn btn-primary" id="btnModeToggle">สลับโหมด</button>
      <button class="btn btn-danger" id="btnLogout">ออกจากระบบ</button>
    </div>
  </header>

  <div class="stats-bar">
    <div class="stat-card">
      <h4>โหมดเวลา</h4>
      <p id="modeDisplay">-</p>
    </div>
    <div class="stat-card">
      <h4>Game Coin (ในเกม)</h4>
      <p id="coinsDisplay">0</p>
    </div>
    <div class="stat-card">
      <h4>FLOWER Token (รอรับเข้า MetaMask)</h4>
      <p id="pendingDisplay">0.00</p>
    </div>
  </div>

  <div style="display: flex; gap: 10px; margin-bottom: 20px;">
    <button class="btn btn-success" id="btnOpenBox">🎁 เปิด Mystery Box (100 Coin)</button>
    <button class="btn btn-secondary" id="btnBuyCoinModal">💳 ซื้อเหรียญในเกมด้วย FLOWER</button>
  </div>

  <div class="garden-grid" id="gardenGrid"></div>

  <div class="inventory-section">
    <h3>คลังดอกไม้ของคุณ</h3>
    <div class="inventory-list" id="inventoryList"></div>
  </div>
</div>

<!-- Modal ตั้งชื่อผู้ใช้ -->
<div class="modal" id="usernameModal">
  <div class="modal-content">
    <h3>ตั้งชื่อผู้เล่นใหม่</h3>
    <input type="text" id="usernameInput" maxlength="30" placeholder="ระบุชื่อ 3-30 ตัวอักษร" />
    <div style="display: flex; gap: 8px; justify-content: center;">
      <button class="btn btn-success" id="btnSaveUsername">บันทึก</button>
      <button class="btn btn-secondary" id="btnCloseUsernameModal">ยกเลิก</button>
    </div>
    <p id="usernameError" style="margin-top: 10px; font-size: 13px; color: #ef4444;"></p>
  </div>
</div>

<script>
  let gameState = null;
  let selectedTierToPlant = null;

  async function api(action, method = 'GET', body = null) {
    const opts = { method, credentials: 'same-origin' };
    if (body) {
      opts.headers = { 'Content-Type': 'application/json' };
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(`api.php?action=${action}`, opts);
    let data;
    try {
      data = await res.json();
    } catch (e) {
      throw new Error('ไม่สามารถประมวลผลข้อมูลจากเซิร์ฟเวอร์ได้');
    }
    if (!res.ok || !data.ok) {
      throw new Error(data.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
    return data;
  }

  async function loadState() {
    try {
      const data = await api('state');
      gameState = data;
      render();
    } catch (err) {
      alert(err.message);
    }
  }

  function render() {
    if (!gameState) return;

    document.getElementById('usernameDisplay').textContent = gameState.user.username;
    document.getElementById('modeDisplay').textContent = gameState.user.mode === 'day' ? '☀️ กลางวัน (Day)' : '🌙 กลางคืน (Night)';
    document.getElementById('coinsDisplay').textContent = parseFloat(gameState.user.coins).toLocaleString();
    document.getElementById('pendingDisplay').textContent = parseFloat(gameState.user.pending_coins).toFixed(2);

    // เรนเดอร์แปลงสวน 9 ช่อง
    const grid = document.getElementById('gardenGrid');
    grid.innerHTML = '';
    gameState.garden.forEach((g) => {
      const slotDiv = document.createElement('div');
      slotDiv.className = `slot ${g.flower_tier !== null ? 'occupied' : ''}`;

      if (g.flower_tier !== null) {
        const flower = gameState.flowers[g.flower_tier];
        slotDiv.innerHTML = `
          <div class="emoji">${flower.emoji}</div>
          <div class="name">${flower.thai}</div>
          <button class="btn btn-danger" style="font-size: 10px; margin-top: 4px; padding: 2px 6px;" onclick="dig(${g.slot})">ขุด</button>
        `;
      } else {
        slotDiv.innerHTML = `<span style="color: #64748b;">ว่าง</span>`;
        slotDiv.onclick = () => plant(g.slot);
      }
      grid.appendChild(slotDiv);
    });

    // เรนเดอร์คลังดอกไม้
    const invDiv = document.getElementById('inventoryList');
    invDiv.innerHTML = '';
    gameState.inventory.forEach((item) => {
      const flower = gameState.flowers[item.flower_tier];
      const div = document.createElement('div');
      div.className = 'inv-item';
      div.innerHTML = `
        <div style="font-size: 28px;">${flower.emoji}</div>
        <div>${flower.thai}</div>
        <div style="color: #38bdf8; font-weight: bold;">มี ${item.quantity} ดอก</div>
        <button class="btn btn-primary" style="font-size: 11px; margin-top: 6px; width: 100%;" onclick="selectPlant(${item.flower_tier})">เลือกปลูก</button>
      `;
      invDiv.appendChild(div);
    });
  }

  function selectPlant(tier) {
    selectedTierToPlant = tier;
    alert(`เลือกดอกไม้ Tier ${tier} เรียบร้อย กรุณากดเลือกช่องว่างในสวนเพื่อทำการปลูก`);
  }

  async function plant(slot) {
    if (selectedTierToPlant === null) {
      alert('กรุณากดเลือกดอกไม้จากคลังด้านล่างก่อนปลูก');
      return;
    }
    const form = new FormData();
    form.append('slot', slot);
    form.append('tier', selectedTierToPlant);

    const res = await fetch('api.php?action=plant', { method: 'POST', body: form });
    const d = await res.json();
    if (!res.ok || !d.ok) return alert(d.message);
    selectedTierToPlant = null;
    loadState();
  }

  async function dig(slot) {
    const form = new FormData();
    form.append('slot', slot);
    const res = await fetch('api.php?action=dig', { method: 'POST', body: form });
    const d = await res.json();
    if (!res.ok || !d.ok) return alert(d.message);
    loadState();
  }

  // สลับโหมด Day / Night
  document.getElementById('btnModeToggle').onclick = async () => {
    const nextMode = gameState.user.mode === 'day' ? 'night' : 'day';
    const form = new FormData();
    form.append('mode', nextMode);
    const res = await fetch('api.php?action=mode', { method: 'POST', body: form });
    const d = await res.json();
    if (!res.ok || !d.ok) return alert(d.message);
    if (d.message) alert(d.message);
    loadState();
  };

  // เปิดกล่องสุ่ม
  document.getElementById('btnOpenBox').onclick = async () => {
    try {
      const data = await api('box', 'POST');
      alert(`ยินดีด้วย! คุณได้รับ: ${data.flower.emoji} ${data.flower.thai}`);
      loadState();
    } catch (err) {
      alert(err.message);
    }
  };

  // จัดการ Modal เปลี่ยนชื่อ
  const uModal = document.getElementById('usernameModal');
  const uInput = document.getElementById('usernameInput');
  const uErr = document.getElementById('usernameError');

  document.getElementById('btnEditUsername').onclick = () => {
    uModal.style.display = 'grid';
    uInput.value = gameState.user.username;
    uErr.textContent = '';
  };
  document.getElementById('btnCloseUsernameModal').onclick = () => {
    uModal.style.display = 'none';
  };
  document.getElementById('btnSaveUsername').onclick = async () => {
    const name = uInput.value.trim();
    if (!name) return (uErr.textContent = 'กรุณาระบุชื่อผู้ใช้');
    try {
      await api('update_username', 'POST', { username: name });
      uModal.style.display = 'none';
      loadState();
    } catch (e) {
      uErr.textContent = e.message;
    }
  };

  // Logout
  document.getElementById('btnLogout').onclick = async () => {
    await api('logout', 'POST');
    window.location.href = 'index.php';
  };

  // โหลดข้อมูลเริ่มต้นและรีเฟรชทุก 10 วินาที
  loadState();
  setInterval(loadState, 10000);
</script>
</body>
</html>