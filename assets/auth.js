const metamaskLoginButton = document.getElementById('metamaskLoginButton');
const walletLoginMsg = document.getElementById('walletLoginMsg');

if (metamaskLoginButton && walletLoginMsg) {
  metamaskLoginButton.addEventListener('click', async () => {
    if (!window.ethereum) {
      walletLoginMsg.textContent =
        'ไม่พบ MetaMask กรุณาติดตั้งและเปิดใช้งานส่วนขยายก่อน';
      return;
    }

    metamaskLoginButton.disabled = true;
    walletLoginMsg.textContent = 'กำลังเชื่อมต่อ MetaMask...';

    try {
      const accounts = await window.ethereum.request({
        method: 'eth_requestAccounts'
      });

      if (!accounts || !accounts.length) {
        throw new Error('ไม่พบบัญชีใน MetaMask');
      }

      // แปลง Address เป็นตัวพิมพ์เล็กเสมอ
      const address = accounts[0].toLowerCase();

      const chainHex = await window.ethereum.request({
        method: 'eth_chainId'
      });

      const chainId = parseInt(chainHex, 16);

      // ขอ Challenge Nonce
      const nonceResponse = await fetch('api.php?action=wallet_nonce', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          address: address,
          chainId: chainId,
          chain_id: chainId
        })
      });

      const nonceData = await nonceResponse.json();

      if (!nonceResponse.ok || !nonceData.ok || !nonceData.message) {
        throw new Error(
          nonceData.message || 'ไม่สามารถสร้างข้อความยืนยันได้'
        );
      }

      walletLoginMsg.textContent = 'กรุณายืนยันลายเซ็นใน MetaMask...';

      // เซ็นข้อความผ่าน MetaMask
      const signature = await window.ethereum.request({
        method: 'personal_sign',
        params: [nonceData.message, address]
      });

      walletLoginMsg.textContent = 'กำลังตรวจสอบลายเซ็น...';

      // ส่ง Signature ไปตรวจสอบ
      const loginResponse = await fetch('api.php?action=wallet_login', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          address: address,
          chainId: chainId,
          chain_id: chainId,
          signature: signature
        })
      });

      const loginData = await loginResponse.json();

      if (!loginResponse.ok || !loginData.ok) {
        throw new Error(
          loginData.message || 'เข้าสู่ระบบด้วย MetaMask ไม่สำเร็จ'
        );
      }

      walletLoginMsg.textContent = 'เข้าสู่ระบบสำเร็จ กำลังเปิดเกม...';
      window.location.href = 'game.php';

    } catch (error) {
      console.error('MetaMask login error:', error);

      if (error.code === 4001) {
        walletLoginMsg.textContent = 'คุณยกเลิกการเชื่อมต่อหรือลงนาม';
      } else {
        walletLoginMsg.textContent =
          error.message || 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ';
      }
    } finally {
      metamaskLoginButton.disabled = false;
    }
  });
}