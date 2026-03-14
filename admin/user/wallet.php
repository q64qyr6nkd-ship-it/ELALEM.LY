<?php
session_start();
include "../../db/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// رصيد المحفظة
$stmt = $conn->prepare("SELECT wallet_blance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();

// سجل العمليات
$historyStmt = $conn->prepare("
    SELECT amount, type, description, order_id, created_at
    FROM wallet_transactions
    WHERE user_id = ?
    ORDER BY id DESC
");
$historyStmt->execute([$user_id]);
$transactions = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>محفظتي</title>


<!-- تنسيق صفحة المحفظة فقط -->
<link rel="stylesheet" href="../../css/hh.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Amiri&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
    
    <!-- زر الرجوع -->
    <div class="back-btn" onclick="history.back()">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="10"></circle>
  <polyline points="13 16 9 12 13 8"></polyline>
</svg>
    </div>

   <div class="app-bar-title-pages">
    حلويات العالم
    <span>ELALEEM SWEETS</span>
</div>

  
    <!-- الشريط -->
    <nav class="navbar">
        <ul>

                       <li><a href="../../index.php"><i class="fa-solid fa-house"></i></a></li>
            <li><a href="wallet.php"><i class="fa-solid fa-wallet"></i></a></li>
            <li><a href="../../index.php#sections"><i class="fa-solid fa-layer-group"></i></a></li>
            <li><a href="../../sections/my_orders.php"><i class="fa-solid fa-box"></i></a></li>

            <!-- السلة -->
            <li class="cart">
                <a href="../../sections/cart.php">
                    <span id="cartCount">0</span>
         <svg width="24" height="24" viewBox="0 0 24 24" fill="#047a95">
  <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 
  0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.2 
  6l-.94-2H2v2h2l3.6 7.59-1.35 2.44C5.52 17.37 6.48 
  19 8 19h12v-2H8l1.1-2h7.45c.75 0 1.41-.41 
  1.75-1.03L21 9H5.21z"/>
</svg>
                </a>
            </li>

        </ul>
    </nav>

</header>

<!-- =======================
        رصيد المحفظة
========================= -->
<div class="wallet-box">
    <h2>رصيدك الحالي</h2>

    <div class="wallet-balance">
        <span id="wallet_balance"><?= number_format($balance, 2) ?></span> د.ل
    </div>

    <p class="wallet-note">يمكنك استخدام رصيد المحفظة للدفع عند الطلب.</p>
</div>


<!-- =======================
        سجل العمليات
========================= -->
<div class="history-box">
    <h3>سجل العمليات</h3>

    <?php if (!empty($transactions)): ?>
        <table class="wallet-table">
            <tr>
                <th>العملية</th>
                <th>المبلغ</th>
                <th>الوصف</th>
                <th>التاريخ</th>
            </tr>

            <?php foreach ($transactions as $t): ?>
            <tr>
                <td class="<?= $t['type'] === 'add' ? 'type-credit' : 'type-debit' ?>">
                    <?= $t['type'] === 'add' ? 'إيداع' : 'خصم' ?>
                </td>

                <td>
                    <?= ($t['type'] === 'add' ? '+' : '-') ?>
                    <?= number_format($t['amount'], 2) ?> د.ل
                </td>

                <td>
                    <?= htmlspecialchars($t['description']) ?>
                    <?php if (!empty($t['order_id'])): ?>
                        <br><small>طلب #<?= $t['order_id'] ?></small>
                    <?php endif; ?>
                </td>

                <td><?= $t['created_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

    <?php else: ?>
        <p class="no-history">لا توجد عمليات حتى الآن.</p>
    <?php endif; ?>
</div>

<script src="../../js/update.js"></script>

<script>
autoUpdate('../../updates.php?type=wallet', function(data){
    document.getElementById("wallet_balance").innerText = Number(data.balance).toFixed(2);
});
</script>

<script>
let lastHistoryJSON = "";

function updateWalletHistory() {
    fetch('../../updates.php?type=wallet_history', {
        method: 'GET',
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {

        if (!data.success || !data.history) return;

        let newJSON = JSON.stringify(data.history);
        if (newJSON === lastHistoryJSON) return;
        lastHistoryJSON = newJSON;

        let rows = `
            <tr>
                <th>العملية</th>
                <th>المبلغ</th>
                <th>الوصف</th>
                <th>التاريخ</th>
            </tr>
        `;

        data.history.forEach(t => {
            let typeText  = t.type === 'add' ? 'إيداع' : 'خصم';
            let typeClass = t.type === 'add' ? 'type-credit' : 'type-debit';
            let sign      = t.type === 'add' ? '+' : '-';

            rows += `
                <tr>
                    <td class="${typeClass}">${typeText}</td>
                    <td>${sign}${Number(t.amount).toFixed(2)} د.ل</td>
                    <td>
                        ${t.description}
                        ${t.order_id ? `<br><small>طلب #${t.order_id}</small>` : ``}
                    </td>
                    <td>${t.created_at}</td>
                </tr>
            `;
        });

        document.querySelector(".wallet-table").innerHTML = rows;
    })
    .catch(err => console.error("Wallet history error:", err));
}

// تحديث تلقائي
updateWalletHistory();
setInterval(updateWalletHistory, 2000);
</script>

<script>
let lastScroll = 0;

window.addEventListener("scroll", () => {
    const header = document.querySelector(".header");
    const currentScroll = window.pageYOffset;

    if (currentScroll > lastScroll && currentScroll > 60) {
        // المستخدم ينزل
        header.classList.add("hide");
    } else {
        // المستخدم يطلع
        header.classList.remove("hide");
    }

    lastScroll = currentScroll;
});
</script>

</body>
</html>
