<?php
session_start();
include "../db/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../admin/auth/login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

try {
   $orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    echo "الطلب غير موجود";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id=? AND order_id=? LIMIT 1");
$stmt->execute([$userId, $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "الطلب غير موجود";
    exit;
}
    if (!$order) {
        $orderProducts = [];
        $total = 0;
        $status = '';
    } else {
        $total = $order['total_price'];
        $status = $order['status'];
        
        $isPickup = ($order['is_pickup'] == 1);
        $orderType = $order['order_type'] ?? 'normal'; // لمعرفة هل هو حجز أم طلب عادي
$resDate = $order['reservation_date'] ?? null; // جلب تاريخ الحجز

        $orderType = $order['order_type'] ?? 'normal';
        
// خريطة الحالات المعروضة
$statusLabels = [
    'preparing'  => 'التحقق من الكمية',
    'ready'      => 'جاهز',
    'delivering' => 'جاري التوصيل',
    'delivered'  => 'تم التوصيل',
    'reject'     => 'مرفوض'
];

// خدعة الاستلام من المحل
if ($isPickup) {
    $statusLabels['delivered'] = 'تم الاستلام';
}
       if ($isPickup) {
    // استلام من المحل (3 مراحل فقط)
    $statusIndex = [
        'preparing' => 1,
        'ready'     => 2,
        'delivered' => 3
    ];
} else {
    // توصيل عادي
    $statusIndex = [
        'preparing'  => 1,
        'ready'      => 2,
        'delivering' => 3,
        'delivered'  => 4
    ];
}
        $currentStep = $statusIndex[$status] ?? 0;

        $itemStmt = $conn->prepare("SELECT product_id, quantity, price FROM order_items WHERE order_id=?");
        $itemStmt->execute([$order['order_id']]);
        $orderProducts = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orderProducts) && !empty($order['products'])) {
            $decoded = json_decode($order['products'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    $orderProducts[] = [
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price']
                    ];
                }
            }
        }
    }
} catch (PDOException $e) {
    $orderProducts = [];
    $total = 0;
    $status = '';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="../css/hh.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Amiri&display=swap" rel="stylesheet">
</head>

<body>

<section class="order-section">
<h1>حالة طلبك</h1>

    <?php 
    $remaining = $order['total_price'] - $order['paid_amount'];
    $resDate = $order['reservation_date'];
    ?>

    <?php if ($order['status'] == 'reject'): ?>
        <!-- 1. حالة الرفض: إذا كان مرفوض، يظهر هذا البلوك فقط ويختفي كل شيء بعده -->
        <div style="background: #ffebee; border: 1px solid #c62828; padding: 30px 20px; border-radius: 10px; margin-top: 20px; text-align: center;">
            <i class="fa-solid fa-circle-xmark" style="color: #c62828; font-size: 3.5rem; margin-bottom: 10px;"></i>
            <h3 style="color: #c62828; margin: 0 0 10px 0;">نأسف، تم رفض الطلب</h3>
            <p style="color: #555; font-size: 1.1rem;">تم إلغاء هذا الطلب لعدم توفر الكمية.</p>
            <a href="../index.php" style="display:inline-block; margin-top:15px; color:#c62828; text-decoration:underline;">العودة للرئيسية</a>
        </div>

    <?php else: ?>
        <!-- 2. إذا لم يكن مرفوضاً: اعرض باقي التفاصيل -->

        <?php if ($order['order_type'] == 'reservation'): ?>
            <!-- أ: تفاصيل الحجز (عربون أو مبدئي) -->
            <?php if ($order['paid_amount'] > 0): ?>
                <div style="background: #e8f5e9; border: 1px solid #2e7d32; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: center;">
                    <h3 style="color: #2e7d32; margin-top: 0;">تم تأكيد استلام العربون ✅</h3>
                    <p>القيمة المدفوعة: <b><?= number_format($order['paid_amount'], 2) ?> د.ل</b></p>
                    <p>المبلغ المتبقي: <b style="color: #c62828;"><?= number_format($remaining, 2) ?> د.ل</b></p>
                    <hr style="border: 0; border-top: 1px dashed #2e7d32; margin: 15px 0;">
                    <p style="color: #d35400; font-weight: bold;">📅 موعد استلام طلبيتك: <?= date('Y-m-d | H:i', strtotime($resDate)) ?></p>
                </div>
            <?php else: ?>
                <div style="background: #fff3e0; border: 1px solid #ef6c00; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: center;">
                    <h3 style="color: #e65100; margin-top: 0;">🕒 حجز مبدئي</h3>
                    <p>يرجى زيارة الفرع لتأكيد الحجز بدفع العربون.</p>
                   <div style="background:#e66767; color:#fff; padding:5px 15px; border-radius:20px; display: inline-block; margin-top: 10px; direction: rtl;">
    📅 موعدك: <?= date('d/m/Y', strtotime($resDate)) ?> | ⏰ <?= date('H:i', strtotime($resDate)) ?>
</div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- شريط المراحل (للطلبات العادية فقط) -->
        <?php if ($order['order_type'] !== 'reservation'): ?>
            <div class="order-progress">
                 <!-- كود الـ steps الخاص بك يوضع هنا -->
            </div>
        <?php endif; ?>

        <!-- عرض المنتجات (تظهر فقط إذا كان الطلب غير مرفوض) -->
        <?php if (!empty($orderProducts)): ?>
            <div style="margin-top: 25px;">
                <?php foreach ($orderProducts as $item): ?>
                    <!-- كود الـ order-card الخاص بك يوضع هنا -->
                <?php endforeach; ?>
                <div class="total">الإجمالي الكلي: <?= number_format($total, 2) ?> د.ل</div>
            </div>
        <?php endif; ?>

    <?php endif; ?> <!-- نهاية شرط الرفض الكبير -->

</section>
<?php if (!empty($orderProducts)): ?>
  
<header class="header">
    
 
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

 
    <nav class="navbar">
        <ul>
 <li><a href="../index.php"><i class="fa-solid fa-house"></i></a></li>
            <li><a href="../admin/user/wallet.php"><i class="fa-solid fa-wallet"></i></a></li>
            <li><a href="../index.php#sections"><i class="fa-solid fa-layer-group"></i></a></li>
            <li><a href="my_orders.php"><i class="fa-solid fa-box"></i></a></li>
            <li class="cart">
                <a href="../sections/cart.php">
                    <span id="cartCount">0</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#047a95">
                      <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 
                      2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 
                      2 2 2 2-.9 2-2-.9-2-2-2zM7.2 6l-.94-2H2v2h2l3.6 
                      7.59-1.35 2.44C5.52 17.37 6.48 19 8 19h12v-2H8l1.1-2h7.45c.75 
                      0 1.41-.41 1.75-1.03L21 9H5.21z"/>
                    </svg>
                </a>
            </li>
        </ul>
    </nav>

</header>
    <!-- شريط المراحل: سيظهر فقط إذا لم يكن نوع الطلب حجزاً مبدئياً -->
<?php if (($order['order_type'] ?? '') !== 'reservation'): ?>

<div class="order-progress">

    <div class="step <?= $currentStep >= 1 ? 'active' : '' ?>">
        <span class="icon">🧁</span>
        <p><?= $statusLabels['preparing'] ?></p>
    </div>

    <div class="line <?= $currentStep >= 2 ? 'active' : '' ?>"></div>

    <div class="step <?= $currentStep >= 2 ? 'active' : '' ?>">
        <span class="icon">🍽</span>
        <p><?= $statusLabels['ready'] ?></p>
    </div>

    <?php if (!$isPickup): ?>
        <div class="line <?= $currentStep >= 3 ? 'active' : '' ?>"></div>

        <div class="step <?= $currentStep >= 3 ? 'active' : '' ?>">
            <span class="icon">🛵</span>
            <p><?= $statusLabels['delivering'] ?></p>
        </div>
    <?php endif; ?>

    <div class="line <?= $currentStep >= ($isPickup ? 3 : 4) ? 'active' : '' ?>"></div>

    <div class="step <?= $currentStep >= ($isPickup ? 3 : 4) ? 'active' : '' ?>">
        <span class="icon">✅</span>
        <p><?= $statusLabels['delivered'] ?></p>
    </div>

</div>

<?php endif; ?>

<!-- المنتجات -->
<?php foreach ($orderProducts as $item): ?>
<?php
$stmt = $conn->prepare("SELECT name, image, available FROM products WHERE id=?");
$stmt->execute([$item['product_id']]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="order-card">
    <img src="../images/<?= htmlspecialchars($product['image']) ?>">
    <div class="order-details">
        <h4><?= htmlspecialchars($product['name']) ?></h4>
        <p>السعر: <?= $item['price'] ?> د.ل</p>
        <p>الكمية: <?= $item['quantity'] ?></p>
        <p>الإجمالي: <?= $item['price'] * $item['quantity'] ?> د.ل</p>
       
    </div>
</div>
<?php endforeach; ?>


<?php else: ?>
<p>لا يوجد طلبات حالياً</p>
<?php endif; ?>

</section>


<!-- تحديث تلقائي مصحح -->
<script>
// نأخذ القيمة الابتدائية من PHP
let currentIsPickup = <?= $isPickup ? 'true' : 'false' ?>;

function updateProgress(status, isPickup) {
    // 1. التعامل مع حالة الرفض
    if (status === 'reject') {
        const progressContainer = document.querySelector('.progress-container'); // أو العنصر المحتوي للشريط
        if (progressContainer) progressContainer.style.opacity = '0.3'; // تعتيم الشريط
        
      
        return; // توقف عن تحديث الخطوات
    }

    // 2. تحديث شريط المراحل بناءً على نوع الطلب
    const statusIndex = isPickup
        ? { preparing: 1, ready: 2, delivered: 3 }
        : { preparing: 1, ready: 2, delivering: 3, delivered: 4 };

    const currentStep = statusIndex[status] || 0;

    // تحديث الدوائر (Steps)
    document.querySelectorAll('.step').forEach((step, index) => {
        if (index + 1 <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });

    // تحديث الخطوط الواصلة (Lines)
    document.querySelectorAll('.line').forEach((line, index) => {
        if (index + 2 <= currentStep) {
            line.classList.add('active');
        } else {
            line.classList.remove('active');
        }
    });
}

// التحديث كل 2 ثانية
setInterval(() => {
    fetch("../updates.php?type=order_status&id=<?= $orderId ?>")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // نستخدم is_pickup القادم من السيرفر لضمان الدقة
                updateProgress(data.status, data.is_pickup == 1);
            }
        })
        .catch(err => console.error("Error fetching status:", err));
}, 2000);
</script>