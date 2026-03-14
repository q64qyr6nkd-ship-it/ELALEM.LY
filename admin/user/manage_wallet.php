<?php
session_start();

include "../check_access.php";
requirePermission("manage_wallet");

include "../../db/db.php";

$message = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$searchResults = [];
$users = $conn->query("SELECT id, full_name, wallet_blance FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------- البحث --------------------
    if (isset($_POST['search_user'])) {

        $phone = trim($_POST['search_user']);

        $stmt = $conn->prepare("
            SELECT id, full_name, email, phone, wallet_blance 
            FROM users 
            WHERE phone LIKE ? 
            LIMIT 10
        ");
        $stmt->execute(["%$phone%"]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {

        // -------------------- تنفيذ العملية --------------------
        $userId = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $type   = $_POST['type']; // add OR deduct
        $reason = trim($_POST['reason']);

        if ($userId > 0 && $amount > 0) {

            $conn->beginTransaction();

            try {

                // جلب الرصيد الحالي للمستخدم
$check = $conn->prepare("SELECT wallet_blance FROM users WHERE id = ?");
$check->execute([$userId]);
$currentBalance = $check->fetchColumn();

// منع الخصم إذا الرصيد غير كافي
if ($type === 'deduct' && $amount > $currentBalance) {
    throw new Exception("❌ الرصيد غير كافٍ لإتمام عملية الخصم");
}
                
                
                // تعديل الرصيد
                if ($type === 'add') {
                    $stmt = $conn->prepare("UPDATE users SET wallet_blance = wallet_blance + ? WHERE id = ?");
                    $desc = "إيداع (+{$amount} د.ل)";
                } else {
                    $stmt = $conn->prepare("UPDATE users SET wallet_blance = wallet_blance - ? WHERE id = ?");
                    $desc = "خصم (-{$amount} د.ل)";
                }

                // إضافة سبب إذا موجود
                if (!empty($reason)) {
                    $desc .= " — سبب: " . $reason;
                }

                $stmt->execute([$amount, $userId]);

                // سجل العمليات
                $log = $conn->prepare("
                    INSERT INTO wallet_transactions (user_id, amount, type, description, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $log->execute([$userId, $amount, $type, $desc]);

                $conn->commit();

                $_SESSION['flash_message'] = ($type === 'add')
                    ? "✅ تم شحن الرصيد بنجاح!"
                    : "💸 تم خصم المبلغ بنجاح!";

                header("Location: " . $_SERVER['PHP_SELF']);
                exit;

            } catch (Exception $e) {

                $conn->rollBack();
                $message = "❌ خطأ أثناء العملية: " . $e->getMessage();
            }

        } else {
            $message = "⚠️ الرجاء إدخال مبلغ صالح واختيار مستخدم.";
        }
    }
}

// سجل آخر 10 عمليات
$history = $conn->query("
    SELECT t.*, u.full_name 
    FROM wallet_transactions t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// أعلى المستخدمين رصيدًا
$topUsers = $conn->query("
    SELECT full_name, wallet_blance 
    FROM users 
    ORDER BY wallet_blance DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة المحفظة - حلويات العالم</title>
<link rel="stylesheet" href="../admin_style.css">
</head>

<body>

    <a href="../admin_dashboard.php" class="back-btn" title="الرجوع للوحة التحكم"></a>
    
<div class="container">

  <h1>💳 إدارة المحفظة</h1>

  <?php if(!empty($message)): ?>
    <div id="toast"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>


  <!-- البحث -->
  <form method="POST" class="admin-search">
    <input type="text" name="search_user" placeholder="ابحث برقم الهاتف...">
    <button type="submit">بحث</button>
  </form>

  <?php if (!empty($searchResults)): ?>
  <div class="search-results">
    <h3>نتائج البحث:</h3>
    <ul>
      <?php foreach ($searchResults as $r): ?>
        <li>
          👤 <?= htmlspecialchars($r['full_name']) ?> —
          💰 <?= number_format($r['wallet_blance'], 2) ?> د.ل
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>


  <!-- نموذج تنفيذ العملية -->
  <form method="POST">

    <label>اختر المستخدم:</label>
    <select name="user_id" required>
      <option value="">-- اختر مستخدم --</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= $u['id'] ?>">
          <?= htmlspecialchars($u['full_name']) ?> — <?= number_format($u['wallet_blance'],2) ?> د.ل
        </option>
      <?php endforeach; ?>
    </select>

    <label>نوع العملية:</label>
    <select name="type" required>
      <option value="add">💰 شحن الرصيد</option>
      <option value="deduct">💸 خصم من الرصيد</option>
    </select>

    <label>المبلغ:</label>
    <input type="number" name="amount" step="0.01" required>

    <label>سبب العملية (اختياري):</label>
    <input type="text" name="reason" placeholder="مثال: تعويض - تعديل رصيد">

    <button type="submit">تنفيذ العملية</button>
  </form>



  <!-- سجل آخر العمليات -->
  <div class="section">
    <h3>📜 سجل آخر 10 عمليات</h3>
    <table>
      <tr>
        <th>المستخدم</th>
        <th>العملية</th>
        <th>المبلغ</th>
        <th>الوصف</th>
        <th>التاريخ</th>
      </tr>

      <?php foreach ($history as $h): ?>
      <tr>
        <td><?= htmlspecialchars($h['full_name']) ?></td>

        <td class="<?= $h['type'] == 'add' ? 'type-add' : 'type-deduct' ?>">
          <?= $h['type'] == 'add' ? 'إيداع' : 'خصم' ?>
        </td>

        <td>
          <?= $h['type'] == 'add' ? '+' : '-' ?>
          <?= number_format($h['amount'], 2) ?> د.ل
        </td>

        <td><?= htmlspecialchars($h['description']) ?></td>
        <td><?= $h['created_at'] ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>



  <!-- أعلى المستخدمين -->
  <div class="section">
    <h3>🏆 أعلى المستخدمين رصيدًا</h3>
    <table>
      <tr><th>المستخدم</th><th>الرصيد</th></tr>

      <?php foreach ($topUsers as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['full_name']) ?></td>
        <td><?= number_format($t['wallet_blance'],2) ?> د.ل</td>
      </tr>
      <?php endforeach; ?>

    </table>
  </div>

</div>


<script>
document.addEventListener("DOMContentLoaded", function() {
  const toast = document.getElementById("toast");
  if (toast) {
    toast.classList.add("show");
    setTimeout(() => toast.classList.remove("show"), 3000);
  }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {

  const resultsList = document.querySelectorAll(".search-results li");
  const userSelect = document.querySelector('select[name="user_id"]');

  // لو طلع نتائج
  if (resultsList.length > 0 && userSelect) {

      // ناخذ أول نتيجة بحث
      const firstResult = resultsList[0].textContent.trim();

      // نجيب الاسم فقط من السطر
      const extractedName = firstResult
          .replace("👤", "")
          .split("—")[0]
          .trim();

      // ندور عليه في القائمة المنسدلة
      for (let option of userSelect.options) {
          if (option.textContent.includes(extractedName)) {
              option.selected = true;

              // نضيف تنبيه بصري
              userSelect.style.border = "2px solid #800020";
              userSelect.scrollIntoView({behavior: 'smooth', block: 'center'});
              break;
          }
      }
  }
});
</script>


</body>
</html>
