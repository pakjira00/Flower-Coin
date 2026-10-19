FLOWER COIN CLEAN BUILD

ติดตั้ง:
1. แตกโฟลเดอร์ flower_coin ไปที่ C:\xampp\htdocs\flower_coin
2. เปิด Apache + MySQL
3. เข้า phpMyAdmin และ Import database.sql
4. ถ้า MySQL root มี password ให้แก้ DB_PASS ใน config.php
5. เปิด http://localhost/flower_coin/

ระบบ:
- Login/Register
- ผู้เล่นใหม่ 500 Coin + Sunflower 5
- Garden 9 ช่องจริงใน MySQL
- Inventory แยกจาก Garden
- ปลูกได้เฉพาะดอกไม้ที่มีใน Inventory
- ขุดคืน Inventory
- Mystery Box 100 Coin -> Inventory
- Merge จาก Inventory
- Night: จัดสวน
- Day: สวนล็อกและสร้าง Coin ทุก 10 วินาที
- Timer Day
- กลับ Night: settle รายได้เข้า Coin ใน MySQL และแสดง Summary
- Day/Night ใช้พื้นหลังคนละธีม

หมายเหตุ: หน้าแรกมีระบบเข้าสู่ระบบ/สมัครสมาชิก ผู้สมัครใหม่จะถูกสร้าง Garden 9 ช่องและได้รับ 500 Coin + Sunflower 5 ดอกอัตโนมัติ
