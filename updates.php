<?php
session_start();
include "db/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode(['success'=>false, 'message'=>"not_logged"]);
    exit;
}

$userId = $_SESSION['user']['id'];
$type = $_GET['type'] ?? '';

// خريطة تحويل الحالات للعربية
$statusMap = [
    'preparing'  => 'جاري التحضير',
    'ready'      => 'جاهز',
    'delivering' => 'جاري التوصيل',
    'delivered'  => 'تم التوصيل',
    'reject'     => 'مرفوض'
];

switch($type){

    /* 1. تحديث حالة طلب معين (لشريط تتبع الطلب) */
    case "order_status":
        $orderId = intval($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT status, is_pickup FROM orders WHERE user_id=? AND order_id=? LIMIT 1");
        $stmt->execute([$userId, $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if($order){
            echo json_encode([
                'success'    => true,
                'status'     => $order['status'],
                'is_pickup'  => $order['is_pickup'],
                'status_text'=> $statusMap[$order['status']] ?? $order['status']
            ]);
        } else {
            echo json_encode(['success'=>false]);
        }
        break;

    /* 2. تحديث رصيد المحفظة */
    case "wallet":
        $stmt = $conn->prepare("SELECT wallet_blance FROM users WHERE id=?");
        $stmt->execute([$userId]);
        echo json_encode(['success'=>true, 'balance'=>$stmt->fetchColumn()]);
        break;
        case "admin_orders_full":
    if ($_SESSION["user"]["role"] !== "admin") {
        echo json_encode(['success'=>false]);
        exit;
    }

    // 🆕 استبعاد الحجوزات من الاستعلام مباشرة
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            u.full_name,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE (o.reservation_date IS NULL OR o.reservation_date = '' OR o.reservation_date = '0000-00-00')
        AND (o.order_type IS NULL OR o.order_type != 'reservation')
        ORDER BY o.order_id DESC
    ");
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'orders'  => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    break;
        
        case "admin_reservations":
    if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
        echo json_encode(['success'=>false]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            u.full_name,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_type = 'reservation' 
        OR (o.reservation_date IS NOT NULL AND o.reservation_date != '' AND o.reservation_date != '0000-00-00')
        ORDER BY o.order_id DESC
    ");
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'orders'  => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    break;

    /* 4. تحديث قائمة الطلبات بالكامل للزبون (لصفحة "طلباتي") */
    case "orders_list":
        $stmt = $conn->prepare("SELECT order_id, status FROM orders WHERE user_id=? ORDER BY order_id DESC");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إضافة النص العربي للحالة قبل الإرسال
        foreach($orders as &$o) {
            $o['status_text'] = $statusMap[$o['status']] ?? $o['status'];
        }
        
        echo json_encode(['success'=>true, 'data'=>$orders]);
        break;

    /* 5. تحديث عدد السلة */
    case "cart_count":
        $stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id=?");
        $stmt->execute([$userId]);
        echo json_encode(['success'=>true, 'count'=>$stmt->fetchColumn() ?? 0]);
        break;

    /* 6. تحديث توفر المنتجات */
    case "products_update":
        $cat = $_GET['cat'] ?? '';
        $stmt = $conn->prepare("SELECT id, available FROM products WHERE category = ?");
        $stmt->execute([$cat]);
        echo json_encode(["success" => true, "products" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        echo json_encode(['success'=>false, 'message'=>'unknown_type']);
}
?>