<?php
session_start();
include "../db/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$error  = "";

// 1) جلب السلة
$stmt = $conn->prepare("
    SELECT 
        c.product_id,
        c.quantity,
        p.name,
        p.price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$cartItems) {
    // لو السلة فاضية نرجع للسلة
    header("Location: cart.php");
    exit;
}

// حساب الإجمالي
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// معالجة الفورم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch        = $_POST['branch'] ?? '';
    $pickupPayment = $_POST['pickup_payment'] ?? '';

    if (empty($branch) || empty($pickupPayment)) {
        $error = "الرجاء اختيار الفرع وطريقة الدفع.";
    } else {

        try {
            if ($pickupPayment === 'cash') {
                // 🔹 استلام من المحل + دفع في المحل (كاش)
                $products = [];
                foreach ($cartItems as $item) {
                    $products[] = [
                        'product_id' => $item['product_id'],
                        'name'       => $item['name'],
                        'price'      => $item['price'],
                        'quantity'   => $item['quantity']
                    ];
                }
                $productsJson = json_encode($products, JSON_UNESCAPED_UNICODE);

                $conn->beginTransaction();

                // إنشاء الطلب (مثل الدفع عند الاستلام + أعمدة الاستلام)
                $orderStmt = $conn->prepare("
                    INSERT INTO orders (
                        user_id, products, total_price, status,
                        payment_method, is_pickup, pickup_branch, order_date
                    )
                    VALUES (?, ?, ?, 'preparing', 'cash', 1, ?, NOW())
                ");
                $orderStmt->execute([$userId, $productsJson, $total, $branch]);
                $orderId = $conn->lastInsertId();

                // إدخال المنتجات في order_items أيضاً (لين يخدم نظام الرفض والإرجاع)
                $itemStmt = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($cartItems as $item) {
                    $itemStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price']
                    ]);
                }

                // حذف السلة
                $delCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $delCart->execute([$userId]);

                $conn->commit();

                // توجيه لصفحة حالة الطلب
                header("Location: order_status.php?id=" . $orderId);
                exit;

            } elseif ($pickupPayment === 'wallet') {
                // 🔹 استلام من المحل + دفع من المحفظة

                // جلب رصيد المحفظة
                $balanceStmt = $conn->prepare("SELECT wallet_blance FROM users WHERE id = ?");
                $balanceStmt->execute([$userId]);
                $wallet = $balanceStmt->fetchColumn();

                if ($wallet < $total) {
                    $error = "رصيد المحفظة غير كافٍ.";
                } else {

                    $conn->beginTransaction();

                    // إنشاء الطلب (مثل pay_with_wallet + أعمدة الاستلام)
                    $orderStmt = $conn->prepare("
                        INSERT INTO orders (
                            user_id, total_price, status,
                            payment_method, is_pickup, pickup_branch, order_date
                        )
                        VALUES (?, ?, 'preparing', 'wallet', 1, ?, NOW())
                    ");
                    $orderStmt->execute([$userId, $total, $branch]);
                    $orderId = $conn->lastInsertId();// إدخال المنتجات في order_items
                    $itemStmt = $conn->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price)
                        VALUES (?, ?, ?, ?)
                    ");

                    foreach ($cartItems as $item) {
                        $itemStmt->execute([
                            $orderId,
                            $item['product_id'],
                            $item['quantity'],
                            $item['price']
                        ]);
                    }

                    // خصم من رصيد المحفظة
                    $updateBalance = $conn->prepare("
                        UPDATE users 
                        SET wallet_blance = wallet_blance - ? 
                        WHERE id = ?
                    ");
                    $updateBalance->execute([$total, $userId]);

                    // تسجيل العملية في سجل المحفظة
                    $log = $conn->prepare("
                        INSERT INTO wallet_transactions (
                            user_id, amount, type, description, order_id, created_at
                        )
                        VALUES (?, ?, 'debit', ?, ?, NOW())
                    ");
                    $desc = "خصم قيمة طلب استلام من المحل رقم #" . $orderId;
                    $log->execute([$userId, $total, $desc, $orderId]);

                    // حذف السلة
                    $delCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $delCart->execute([$userId]);

                    $conn->commit();

                    // توجيه لصفحة حالة الطلب
                    header("Location: order_status.php?id=" . $orderId);
                    exit;
                }

            } else {
                $error = "طريقة دفع غير صحيحة.";
            }

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = "حدث خطأ أثناء إنشاء الطلب: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>استلام من المحل - حلويات العالم</title>
    <link rel="stylesheet" href="../css/hh.css">
</head>
<body class="pickup-page-background">

<div class="pickup-page">

    <h1>استلام الطلب من المحل</h1>

    <p>إجمالي الطلب: <strong><?php echo htmlspecialchars($total); ?> د.ل</strong></p>

    <?php if (!empty($error)): ?>
        <div style="color:red;margin-bottom:12px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="pickup-form">

        <label>اختر الفرع:</label>
        <select name="branch" required>
            <option value="">اختر الفرع</option>
            <option value="فرع تاجوراء">فرع تاجوراء</option>
            <option value="فرع زاوية الدهماني">فرع زاوية الدهماني</option>
            <option value="فرع تريبولي مول">فرع تريبولي مول</option>
            <option value="فرع تاج مول">فرع تاج مول</option>
        </select>

        <label>طريقة الدفع:</label>

        <label>
    <input type="radio" name="pickup_payment" value="cash" checked>
    <span>الدفع في المحل</span>
</label>

<label>
    <input type="radio" name="pickup_payment" value="wallet">
    <span>الدفع من المحفظة</span>
</label>

        </div>

        <button type="submit" class="pickup-btn">تأكيد الطلب</button>

    </form>

</div>


</body>
</html>