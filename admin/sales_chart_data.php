<?php
header('Content-Type: application/json; charset=utf-8');
include "../db/db.php";

$mode = $_GET['mode'] ?? 'day'; // day, month, year
$type = $_GET['type'] ?? 'order'; // order أو reservation

$data = [
    "labels" => [],
    "values" => []
];

if ($type === 'order') {
    // الطلبات العادية: من جدول orders
    switch($mode){
        case 'year':
            $sql = "SELECT YEAR(payment_date) AS period, SUM(total_price) AS total_sales
                    FROM orders
                    WHERE status='delivered' AND order_type='order'
                    GROUP BY YEAR(payment_date)
                    ORDER BY YEAR(payment_date)";
            break;
        case 'month':
            $sql = "SELECT DATE_FORMAT(payment_date,'%Y-%m') AS period, SUM(total_price) AS total_sales
                    FROM orders
                    WHERE status='delivered' AND order_type='order'
                    GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
                    ORDER BY DATE_FORMAT(payment_date,'%Y-%m')";
            break;
        default: // day
            $sql = "SELECT DATE(payment_date) AS period, SUM(total_price) AS total_sales
                    FROM orders
                    WHERE status='delivered' AND order_type='order'
                    GROUP BY DATE(payment_date)
                    ORDER BY DATE(payment_date)";
            break;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();

} else {
    // الحجوزات: من جدول payments
    switch($mode){
        case 'year':
            $sql = "SELECT YEAR(payment_date) AS period, SUM(amount) AS total_sales
                    FROM payments
                    GROUP BY YEAR(payment_date)
                    ORDER BY YEAR(payment_date)";
            break;
        case 'month':
            $sql = "SELECT DATE_FORMAT(payment_date,'%Y-%m') AS period, SUM(amount) AS total_sales
                    FROM payments
                    GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
                    ORDER BY DATE_FORMAT(payment_date,'%Y-%m')";
            break;
        default: // day
            $sql = "SELECT DATE(payment_date) AS period, SUM(amount) AS total_sales
                    FROM payments
                    GROUP BY DATE(payment_date)
                    ORDER BY DATE(payment_date)";
            break;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();
}

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data['labels'] = array_column($result, 'period');
$data['values'] = array_column($result, 'total_sales');

echo json_encode($data);