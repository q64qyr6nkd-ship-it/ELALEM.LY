<?php
session_start();
include "../db/db.php";

$isReservation = (isset($_GET['type']) && $_GET['type'] == 'reservation');
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit; }
$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY order_id DESC LIMIT 1");
$stmt->execute([$userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$order){ header("Location: index.php"); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);
    $landmark = trim($_POST['landmark']);
    $phone = trim($_POST['phone']);
    $res_date = $_POST['res_date'] ?? null;
    $notes = trim($_POST['notes']);

    // التحقق من أن رقم الهاتف يحتوي على أرقام فقط
    if (!preg_match('/^[0-9]+$/', $phone)) {
        echo "<script>alert('⚠️ رقم الهاتف يجب أن يحتوي على أرقام فقط!');</script>";
    } else {
        $update = $conn->prepare("UPDATE orders SET delivery_city=?, delivery_address=?, delivery_landmark=?, delivery_phone=?, reservation_date=?, notes=? WHERE order_id=?");
        $update->execute([$city, $address, $landmark, $phone, $res_date, $notes, $order['order_id']]);

        header("Location: order_status.php?id=" . $order['order_id']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>أكمل البيانات</title>
    <link rel="stylesheet" href="../css/hh.css">
</head>
<body>
<div class="auth-container">
    <h1>بيانات التوصيل</h1>
    <form method="POST" class="auth-form" id="deliveryForm">
        <label>المدينة: <span style="color: #999; font-size: 0.9em;">(اختياري)</span></label>
        <input type="text" name="city" placeholder="مثال: طرابلس">

        <label>العنوان التفصيلي: <span style="color: #999; font-size: 0.9em;">(اختياري)</span></label>
        <textarea name="address" placeholder="الشارع، رقم المنزل..."></textarea>
        
        <label>أقرب علامة دالة: <span style="color: #999; font-size: 0.9em;">(اختياري)</span></label>
        <input type="text" name="landmark" placeholder="مثال: خلف جامع ...">

        <label>رقم للتواصل: <span style="color: red;">*</span></label>
        <input type="tel" 
               name="phone" 
               id="phoneInput"
               required 
               placeholder="091xxxxxxx"
               pattern="[0-9]+"
               title="يجب إدخال أرقام فقط">
        
        <?php if($isReservation): ?>
        <div style="margin-bottom: 20px; border: 1px solid #f39c12; padding: 15px; border-radius: 10px; background: #fffcf5;">
            <label style="color: #d35400; font-weight: bold;">تاريخ ووقت الاستلام المتوقع: <span style="color: red;">*</span></label>
            <input type="datetime-local" 
                   name="res_date" 
                   id="resDateInput"
                   required 
                   style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <small style="color: #888; display: block; margin-top: 5px;">يمكنك الحجز من غداً حتى شهر قادم</small>
        </div>

        <script>
        // منع اختيار تاريخ اليوم أو الماضي
        const input = document.getElementById('resDateInput');

        // تاريخ الغد (الحد الأدنى)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);

        // تاريخ بعد شهر (الحد الأقصى)
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 1);

        // تنسيق التاريخ لـ datetime-local
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        };

        input.min = formatDate(tomorrow);
        input.max = formatDate(maxDate);

        // التحقق عند الإدخال
       input.addEventListener('change', function() {
          const selected = new Date(this.value);
            if (selected < tomorrow) {
               alert('⚠️ لا يمكنك اختيار تاريخ اليوم أو الماضي!');
                this.value = '';
            } else if (selected > maxDate) {
               alert('⚠️ لا يمكنك الحجز لأكثر من شهر!');
                this.value = '';
           }
        });
        </script>
        <?php endif; ?>

        <label>ملاحظات إضافية للطلب: <span style="color: #999; font-size: 0.9em;">(اختياري)</span></label>
        <textarea name="notes" placeholder="اكتب هنا أي ملاحظات تريد إيصالها للإدارة..." style="height: 80px;"></textarea>

        <button type="submit" class="auth-btn">حفظ ومتابعة</button>
    </form>
</div>

<script>
// التحقق من أن رقم الهاتف يحتوي على أرقام فقط
document.getElementById('phoneInput').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

</body>
</html>
