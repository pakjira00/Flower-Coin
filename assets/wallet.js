
const connectWalletBtn = document.getElementById('connectWalletBtn');
const walletStatus = document.getElementById('walletStatus');
const walletAddress = document.getElementById('walletAddress');
const walletChain = document.getElementById('walletChain');
const walletMessage = document.getElementById('walletMessage');

async function walletApi(action, data) {
    const response = await fetch(`wallet_api.php?action=${action}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!response.ok || !result.ok) {
        throw new Error(result.message || 'เกิดข้อผิดพลาด');
    }

    return result;
}

connectWalletBtn.addEventListener('click', async () => {
    if (!window.ethereum) {
        walletMessage.textContent =
            'ไม่พบ MetaMask กรุณาติดตั้งหรือเปิดใช้งานส่วนขยาย';
        return;
    }

    if (!window.ethers) {
        walletMessage.textContent =
            'โหลด ethers.js ไม่สำเร็จ กรุณาตรวจสอบไฟล์';
        return;
    }

    connectWalletBtn.disabled = true;
    walletMessage.textContent = 'กำลังเชื่อมต่อ Wallet...';

    try {
        const accounts = await window.ethereum.request({
            method: 'eth_requestAccounts'
        });

        if (!accounts.length) {
            throw new Error('ไม่พบ Wallet Address');
        }

        const address = window.ethers.getAddress(accounts[0]);
        const chainHex = await window.ethereum.request({
            method: 'eth_chainId'
        });
        const chainId = Number(BigInt(chainHex));

        // ขอข้อความยืนยันจาก Backend
        const challenge = await walletApi('nonce', {
            address,
            chainId
        });

        walletMessage.textContent =
            'กรุณาตรวจสอบและเซ็นข้อความยืนยันใน MetaMask';

        const signature = await window.ethereum.request({
            method: 'personal_sign',
            params: [
                window.ethers.hexlify(
                    window.ethers.toUtf8Bytes(challenge.message)
                ),
                address
            ]
        });

        const result = await walletApi('save', { signature });

        walletStatus.textContent = 'เชื่อมต่อแล้ว';
        walletAddress.textContent = result.wallet || address;
        walletChain.textContent = String(chainId);
        walletMessage.textContent = result.message;
        connectWalletBtn.textContent = 'เชื่อมต่อแล้ว';
    } catch (error) {
        walletMessage.textContent =
            error.message || 'เชื่อมต่อ Wallet ไม่สำเร็จ';
    } finally {
        connectWalletBtn.disabled = false;
    }
});

if (window.ethereum) {
    window.ethereum.on('accountsChanged', () => {
        walletStatus.textContent = 'กรุณาเชื่อมต่อ Wallet อีกครั้ง';
        walletAddress.textContent = '-';
        walletChain.textContent = '-';
        connectWalletBtn.textContent = '🦊 เชื่อมต่อ MetaMask';
    });

    window.ethereum.on('chainChanged', () => {
        walletStatus.textContent = 'เครือข่ายเปลี่ยน กรุณาเชื่อมต่ออีกครั้ง';
        walletChain.textContent = '-';
    });
}
