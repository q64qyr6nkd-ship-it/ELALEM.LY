<?php
session_start();
include "check_access.php"; 
requirePermission("manage_reservations"); 
include "../db/db.php";

try {
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_type = 'reservation' OR (o.reservation_date IS NOT NULL AND o.reservation_date != '')
        ORDER BY o.order_id DESC
    ");
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الحجوزات</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

<div class="orders-container">
    <h1>إدارة الحجوزات <span id="newOrdersBadge" class="new-badge">0</span></h1>
    <a href="admin_dashboard.php" class="back-btn" title="الرجوع للوحة التحكم"></a>

    <table class="orders-table">
        <thead>
            <tr>
                <th>رقم الحجز</th>
                <th>العميل</th>
                <th>الإجمالي</th>
                <th>المدفوع</th>
                <th>المتبقي</th>
                <th>موعد الاستلام 📅</th>
                <th>الحالة</th>
                <th>إجراء</th>
            </tr>
        </thead>
        <tbody id="reservations-body">
            <?php foreach ($reservations as $res): 
                $remaining = $res['total_price'] - $res['paid_amount'];
                $isRejected = ($res['status'] == 'reject');
                $pClass = $isRejected ? "badge-reject" : (($remaining <= 0) ? "badge-paid" : "badge-unpaid");
                $statusText = $isRejected ? "ملغي" : (($remaining <= 0) ? "خالص" : "بانتظار التكملة");
            ?>
            <tr id="order_row_<?= $res['order_id']; ?>" <?= $isRejected ? 'style="opacity:0.6;"' : '' ?>>
                <td>#<?= $res['order_id']; ?></td>
                <td><?= htmlspecialchars($res['full_name'] ?? 'زائر'); ?></td>
                <td><?= number_format($res['total_price'], 2); ?></td>
                <td class="paid-green" id="paid_val_<?= $res['order_id']; ?>"><?= number_format($res['paid_amount'], 2); ?></td>
                <td class="rem-red" id="rem_val_<?= $res['order_id']; ?>"><?= number_format($remaining, 2); ?></td>
                <td class="res-date-cell" id="date_val_<?= $res['order_id']; ?>"><?= $res['reservation_date'] ?: '---'; ?></td>
                <td id="status_cell_<?= $res['order_id']; ?>"><span class="status-badge <?= $pClass ?>"><?= $statusText ?></span></td>
                <td><button class="btn view-details" data-id="<?= $res['order_id']; ?>">تفاصيل / تعديل</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- المودال -->
<div id="orderModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div class="modal-box" style="background:white; padding:20px; border-radius:10px; width:90%; max-width:400px; position:relative;">
        <span class="close-modal" onclick="closeModal()" style="cursor:pointer; float:left; font-size:28px;">&times;</span>
        <h2 id="modal-title">تحديث الحجز</h2>
        <hr>
        <div id="modal-content"></div>
        <div class="modal-footer" style="margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
            <label>تعديل الموعد:</label>
            <input type="datetime-local" id="editDate" style="width:100%; margin-bottom:10px; padding:8px;">
            <label>إضافة مبلغ مدفوع:</label>
            <input type="number" id="payInput" step="0.01" placeholder="مثلاً: 50" style="width:100%; margin-bottom:10px; padding:8px;">
            <button id="btnUpdateAll" class="btn" style="width:100%; background:#27ae60; color:white; margin-bottom:5px; padding:10px; border:none; border-radius:5px; cursor:pointer;">حفظ التغييرات</button>
            <button id="btnCancelOrder" class="cancel-btn" style="width:100%; background:#e74c3c; color:white; padding:10px; border:none; border-radius:5px; cursor:pointer;">إلغاء الحجز نهائياً</button>
            <button id="printBtn" class="btn" style="width:100%; background:#34495e; color:white; margin-top:5px; padding:10px; border:none; border-radius:5px; cursor:pointer;">طباعة إيصال</button>
        </div>
    </div>
</div>

    <!-- صوت الطلب الجديد -->
<audio id="newOrderSound">
    <source src="sounds/new_order.mp3" type="audio/mpeg">
</audio>

<audio id="orderAlert" src="sounds/new_order.mp3" preload="auto"></audio>


<script src="../js/update.js"></script><script>
// متغيرات النظام
let currentOrderId = null;
let firstLoad = true;
let lastMaxId = 0;

// دوال المودال (النافذة المنبثقة)
const openModal = () => {
    document.getElementById("orderModal").style.display = "flex";
};

const closeModal = () => {
    document.getElementById("orderModal").style.display = "none";
    currentOrderId = null;
};

// حل مشكلة الأزرار (Event Delegation)
// نراقب الضغط على الصفحة، إذا كان العنصر هو زر "تفاصيل" نقوم بتنفيذ الكود
document.addEventListener("click", function(e) {
    const btn = e.target.closest(".view-details");
    if (btn) {
        const orderId = btn.getAttribute("data-id");
        if (orderId) loadReservationDetails(orderId);
    }
});

// جلب تفاصيل الحجز للمودال
function loadReservationDetails(id) {
    currentOrderId = id;
    const content = document.getElementById("modal-content");
    content.innerHTML = "<p style='text-align:center;'>جاري التحميل...</p>";
    openModal();
    
    fetch('fetch_order_details.php?id=' + id)
    .then(response => response.json())
    .then(data => {
        if(!data.success) {
            content.innerHTML = "فشل تحميل البيانات";
            return;
        }
        const o = data.order;
        const items = data.items || [];

let itemsRows = "";

if (items.length > 0) {
    items.forEach(i => {
        const q = Number(i.quantity);
        const p = Number(i.price);
        const totalItem = (q * p).toFixed(2);

        itemsRows += `
            <tr>
                <td>
                    <img src="../images/${i.image}" 
                         style="width:50px; height:auto; border-radius:5px;">
                </td>
                <td>${i.name}</td>
                <td>${q}</td>
                <td>${p.toFixed(2)} د.ل</td>
                <td>${totalItem} د.ل</td>
            </tr>
        `;
    });
} else {
    itemsRows = `
        <tr>
            <td colspan="5" style="text-align:center;">لا توجد منتجات</td>
        </tr>
    `;
}
        const total = parseFloat(o.total_price || 0);
        const paid = parseFloat(o.paid_amount || 0);
        const rem = (total - paid).toFixed(2);
        
        content.innerHTML = `
            <p>👤 <b>الزبون:</b> ${o.full_name || 'زائر'}</p>
            <p>📞 <b>الهاتف:</b> ${o.delivery_phone || '---'}</p>
            ${o.notes ? `<div style="background:#fffbea; border:1px solid #f39c12; padding:10px; margin-top:10px; border-radius:5px;">
    <p><b>📝 ملاحظات العميل:</b></p>
    <p style="color:#555; white-space:pre-wrap;">${o.notes}</p>
    <h3 style="margin-top:15px;">🛒 المنتجات</h3>
<table style="width:100%; border-collapse:collapse;" border="1">
    <thead>
        <tr style="background:#f1f1f1;">
            <th>الصورة</th>
            <th>المنتج</th>
            <th>الكمية</th>
            <th>السعر</th>
            <th>الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        ${itemsRows}
    </tbody>
</table>
</div>` : ''}
            <div style="background:#f9f9f9; padding:10px; border-radius:5px; border:1px solid #ddd;">
                <p>💰 الإجمالي: <b>${total.toFixed(2)} د.ل</b></p>
                <p style="color:green;">✅ المدفوع: <b>${paid.toFixed(2)} د.ل</b></p>
                <p style="color:red;">⏳ المتبقي: <b>${rem} د.ل</b></p>
            </div>`;
        
        if(o.reservation_date) {
            document.getElementById("editDate").value = o.reservation_date.replace(" ", "T");
        }
        
        // تعيين الوظائف للأزرار داخل المودال
        document.getElementById('btnUpdateAll').onclick = () => saveChanges(id, 'update');
        document.getElementById('btnCancelOrder').onclick = () => { 
            if(confirm("هل أنت متأكد من إلغاء هذا الحجز؟")) saveChanges(currentOrderId, 'cancel'); 
        };
        document.getElementById('printBtn').onclick = () => window.open('print_reservation.php?id=' + id, '_blank');
    })
    .catch(err => {
        content.innerHTML = "خطأ في الاتصال بالسيرفر";
        console.error(err);
    });
}

// حفظ التغييرات (دفع أو إلغاء)
function saveChanges(id, action) {
    const amt = document.getElementById('payInput').value || 0;
    const date = document.getElementById('editDate').value;
    
    // تأكد من استخدام الـ Backticks هنا لتمرير البيانات بشكل صحيح
    const now = new Date().toISOString().slice(0, 19).replace('T', ' '); // تنسيق MySQL
const bodyData = `order_id=${id}&amount=${amt}&new_date=${date}&action=${action}&payment_date=${now}`;

    
    fetch('update_reservation_payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: bodyData
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) { 
            closeModal();
            document.getElementById('payInput').value = "";
            // سيتم التحديث تلقائياً عبر الدالة بالأسفل
        } else { 
            alert(d.message); 
        }
    });
}
// ================== نظام التحديث التلقائي والصوت ==================
function startAutoUpdate() {
    fetch('../updates.php?type=admin_reservations') // 🆕 تغيير النوع
    .then(res => res.json())
    .then(data => {
        if (!data.success || !data.orders) return;
        
        const tableBody = document.getElementById("reservations-body");
        const sound = document.getElementById("newOrderSound");
        const badge = document.getElementById("newOrdersBadge");
        let hasNewOrder = false;

        // معالجة البيانات (من الأقدم للأحدث لإضافة الجديد في الأعلى)
        data.orders.reverse().forEach(order => {
            const orderId = parseInt(order.order_id);
            let row = document.getElementById("order_row_" + orderId);
            
            const total = parseFloat(order.total_price || 0);
            const paid = parseFloat(order.paid_amount || 0);
            const remaining = total - paid;
            
            let statusText = (order.status === 'reject') ? "ملغي" : (remaining <= 0 ? "خالص" : "بانتظار التكملة");
            let badgeClass = (order.status === 'reject') ? "badge-reject" : (remaining <= 0 ? "badge-paid" : "badge-unpaid");

            if(!row) {
                // إضافة حجز جديد في أعلى الجدول
                const newRowHtml = `
                <tr id="order_row_${orderId}" class="new-row-highlight">
                    <td>#${orderId}</td>
                    <td>${order.full_name || 'زائر'}</td>
                    <td>${total.toFixed(2)}</td>
                    <td class="paid-green" id="paid_val_${orderId}">${paid.toFixed(2)}</td>
                    <td class="rem-red" id="rem_val_${orderId}">${remaining.toFixed(2)}</td>
                    <td class="res-date-cell" id="date_val_${orderId}">${order.reservation_date || '---'}</td>
                    <td id="status_cell_${orderId}"><span class="status-badge ${badgeClass}">${statusText}</span></td>
                    <td><button class="btn view-details" data-id="${orderId}">تفاصيل / تعديل</button></td>
                </tr>`;
                tableBody.insertAdjacentHTML('afterbegin', newRowHtml);

                // 🆕 تشغيل الصوت وتحديث الشارة
                if (!firstLoad) {
                    hasNewOrder = true;
                    if(badge) {
                        let count = parseInt(badge.innerText) || 0;
                        badge.innerText = count + 1;
                    }
                }
            } else {
                // تحديث البيانات في الصف الموجود فعلياً
                document.getElementById(`paid_val_${orderId}`).innerText = paid.toFixed(2);
                document.getElementById(`rem_val_${orderId}`).innerText = remaining.toFixed(2);
                document.getElementById(`date_val_${orderId}`).innerText = order.reservation_date || '---';
                document.getElementById(`status_cell_${orderId}`).innerHTML = `<span class="status-badge ${badgeClass}">${statusText}</span>`;
                if(order.status === 'reject') row.style.opacity = "0.6";
            }
            
            if(orderId > lastMaxId) lastMaxId = orderId;
        });

        // 🆕 تشغيل الصوت إذا كان هناك حجز جديد
        if(hasNewOrder && sound) {
            sound.currentTime = 0;
            sound.play().catch(e => console.log("الصوت يحتاج تفاعل مع الصفحة أولاً"));
        }
        
        firstLoad = false;
    })
    .catch(err => console.error("Update Error:", err));
}

// تشغيل التحديث كل 3 ثواني
setInterval(startAutoUpdate, 3000);
// تشغيل مرة واحدة فوراً عند التحميل
startAutoUpdate();


</script>
    </body>
    </html>