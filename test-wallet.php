
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flower Coin - Connect Wallet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 650px;
            margin: 60px auto;
            padding: 20px;
            background: #f7f5ff;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 18px #0001;
        }

        button {
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            background: #7546d8;
            color: white;
            cursor: pointer;
        }

        #address {
            overflow-wrap: anywhere;
        }

        #status {
            margin-top: 18px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>🌸 Flower Coin</h2>
        <p>ทดสอบเชื่อมต่อ MetaMask</p>

        <button id="connectBtn">Connect MetaMask</button>

        <p><strong>Wallet Address:</strong></p>
        <p id="address">ยังไม่ได้เชื่อมต่อ</p>

        <p><strong>Network Chain ID:</strong></p>
        <p id="network">-</p>

        <p id="status">สถานะ: รอเชื่อมต่อ</p>
    </div>

    <!-- โหลด ethers.js จากแพ็กเกจที่ติดตั้งไว้ -->
    <script src="node_modules/ethers/dist/ethers.umd.min.js"></script>

    <script>
        const connectBtn = document.getElementById("connectBtn");
        const addressEl = document.getElementById("address");
        const networkEl = document.getElementById("network");
        const statusEl = document.getElementById("status");

        connectBtn.addEventListener("click", async () => {
            try {
                statusEl.textContent = "กำลังเชื่อมต่อ...";

                if (!window.ethereum) {
                    throw new Error(
                        "ไม่พบ MetaMask กรุณาติดตั้ง Extension"
                    );
                }

                if (!window.ethers) {
                    throw new Error(
                        "โหลด ethers.js ไม่สำเร็จ กรุณาตรวจสอบตำแหน่งไฟล์"
                    );
                }

                const provider = new ethers.BrowserProvider(
                    window.ethereum
                );

                // ขออนุญาตเข้าถึงกระเป๋าจากผู้ใช้
                const accounts = await provider.send(
                    "eth_requestAccounts",
                    []
                );

                if (!accounts.length) {
                    throw new Error("ไม่พบ Wallet Address");
                }

                const address = ethers.getAddress(accounts[0]);
                const network = await provider.getNetwork();

                addressEl.textContent = address;
                networkEl.textContent = network.chainId.toString();
                statusEl.textContent =
                    "เชื่อมต่อ MetaMask สำเร็จ";

                connectBtn.textContent = "เชื่อมต่อแล้ว";
            } catch (error) {
                statusEl.textContent =
                    "เกิดข้อผิดพลาด: " +
                    (error.message || "ไม่สามารถเชื่อมต่อได้");
            }
        });

        // ติดตามเมื่อผู้ใช้เปลี่ยนกระเป๋า
        if (window.ethereum) {
            window.ethereum.on("accountsChanged", (accounts) => {
                addressEl.textContent =
                    accounts.length ? accounts[0] : "ยังไม่ได้เชื่อมต่อ";

                statusEl.textContent = accounts.length
                    ? "เปลี่ยน Wallet Address แล้ว"
                    : "ยกเลิกการเชื่อมต่อกระเป๋าแล้ว";
            });

            window.ethereum.on("chainChanged", () => {
                window.location.reload();
            });
        }
    </script>
</body>
</html>
