<?php
session_start();

include "check_access.php";
requirePermission("manage_orders");

include "../db/db.php";

try {
    // جلب الطلبات + عدد المنتجات في كل طلب
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            u.full_name,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.order_id DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage());
}

$statusMap = [
    'preparing'  => 'جاري التحضير',
    'ready'      => 'جاهز',
    'delivering' => 'جاري التوصيل',
    'delivered'  => 'تم التوصيل',
    'reject'     => 'مرفوض',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الطلبات</title>
    <link rel="stylesheet" href="admin_style.css">


</head>
<body>
<a href="admin_dashboard.php" class="back-btn" title="الرجوع للوحة التحكم"></a>
    
<div class="orders-container">

    <h1>
        إدارة الطلبات
        <span id="newOrdersBadge" class="new-badge">0</span>
    </h1>

    <?php if (!empty($orders)): ?>
    <table class="orders-table">
        <tr>
            <th>رقم الطلب</th>
            <th>العميل</th>
            <th>عدد المنتجات</th>
            <th>الإجمالي</th>
            <th>طريقة الدفع</th>
            <th>الحالة</th>
            <th>الفرع</th>
            <th>تاريخ الطلب</th>
            <th>إجراء</th>
            

        </tr>
<?php foreach ($orders as $o): 
    // شرط الاستبعاد: إذا كان هناك تاريخ حجز أو نوع الطلب "حجز"، يتم تخطيه في هذه الصفحة
    if (!empty($o['reservation_date']) || (isset($o['order_type']) && $o['order_type'] === 'reservation')) {
        continue; 
    }
?>
    <tr id="order_row_<?= $o['order_id']; ?>">
        <td>#<?= $o['order_id']; ?></td>
        <td><?= htmlspecialchars($o['full_name'] ?? 'غير معروف'); ?></td>
        <td><?= (int)$o['items_count']; ?></td>
        <td><?= number_format($o['total_price'], 2); ?> د.ل</td>
        <td><?= $o['payment_method'] === 'wallet' ? 'محفظة' : 'كاش'; ?></td>
        <td id="status_<?= $o['order_id']; ?>">
            <?= $statusMap[$o['status']] ?? $o['status']; ?>
        </td>
        <td>
            <?= $o['is_pickup'] == 1 ? htmlspecialchars($o['pickup_branch']) : "—"; ?>
        </td>
        <td><?= $o['order_date']; ?></td>
        <td>
            <button class="btn view-details" data-id="<?= $o['order_id']; ?>">
                تفاصيل
            </button>
        </td>
    </tr>
<?php endforeach; ?>

    </table>
    <?php else: ?>
        <p class="no-orders">لا توجد طلبات حالياً.</p>
    <?php endif; ?>
</div>

<!-- ========== المودال ========== -->
<div id="orderModal" class="modal">
    <div class="modal-box">
        <span class="close-modal">&times;</span>
        <h2 id="modal-title"></h2>

        <div id="modal-content"></div>

        <!-- قسم المنتجات المرفوضة -->
        <div id="reject-section" class="reject-section" style="display:none;">
            <h3>المنتجات غير المتوفرة</h3>
            <p class="hint">اختر المنتجات التي تريد إخفاءها من الأقسام (تظهر في قسم المنتجات المخفية).</p>
            <div id="reject-products-list"></div>
            <button id="saveRejectedBtn" class="btn danger">حفظ المنتجات المرفوضة</button>
        </div>

        <div class="modal-footer">
            <div class="status-row">
                <label for="statusSelect">تغيير حالة الطلب:</label>
                <select id="statusSelect">
                    <option value="preparing">جاري التحضير</option>
                    <option value="ready">جاهز</option>
                    <option value="delivering">جاري التوصيل</option>
                    <option value="delivered">تم التوصيل</option>
                    <option value="reject">مرفوض</option>
                </select>
                <button id="saveStatusBtn" class="btn update">حفظ الحالة</button>
            </div>

            <button id="printBtn" class="btn print">طباعة إيصال بسيط</button>
        </div>
    </div>
</div>

<script>
// ===== متغيرات عامة للمودال =====
let currentOrderId = null;
let currentItems   = [];

// فتح وإغلاق المودال
function openModal() {
    document.getElementById("orderModal").style.display = "flex";
}
function closeModal() {
    document.getElementById("orderModal").style.display = "none";
    document.getElementById("modal-content").innerHTML = "";
    currentOrderId = null;
    currentItems   = [];
}

const closeBtn = document.querySelector(".close-modal");
if (closeBtn) closeBtn.onclick = closeModal;

window.addEventListener('click', function(e){
    if (e.target.id === 'orderModal') closeModal();
});

// ربط زر "تفاصيل" (لكل الأزرار الحالية والجديدة)
document.addEventListener("click", function(e){
    if (e.target.classList.contains("view-details")) {
        const id = e.target.dataset.id;
        if (id) loadOrderDetails(id);
    }
});

// إظهار/إخفاء قسم المنتجات المرفوضة حسب الحالة
function toggleRejectSection() {
    const statusSelect = document.getElementById("statusSelect");
    const rejectSection = document.getElementById("reject-section");
    if (!statusSelect || !rejectSection) return;

    if (statusSelect.value === "reject") {
        rejectSection.style.display = "block";
    } else {
        rejectSection.style.display = "none";
    }
}

const statusSelectGlobal = document.getElementById("statusSelect");
if (statusSelectGlobal) {
    statusSelectGlobal.addEventListener("change", toggleRejectSection);
}

// AJAX – جلب تفاصيل الطلب
function loadOrderDetails(id) {
    const modalContent = document.getElementById("modal-content");
    const modalTitle   = document.getElementById("modal-title");

    modalContent.innerHTML = "<p>جاري التحميل...</p>";
    openModal();

    fetch("fetch_order_details.php?id=" + encodeURIComponent(id))
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                modalContent.innerHTML = "<p style='color:red;'>حدث خطأ في جلب البيانات.</p>";
                return;
            }

            const order = data.order;
            const items = data.items || [];

            currentOrderId = order.order_id;
            currentItems   = items;

            modalTitle.textContent = "تفاصيل الطلب #" + order.order_id;
            modalTitle.dataset.orderId = order.order_id;

            const statusSelect = document.getElementById("statusSelect");
            if (statusSelect && order.status) {
                statusSelect.value = order.status;
            }

            // نبني جدول المنتجات
            let rows = "";

            if (items.length > 0) {
                items.forEach(i => {
                    const q = Number(i.quantity);
                    const p = Number(i.price);
                    const total = (q * p).toFixed(2);

                    rows += `
                        <tr>
                        <td><img src="../images/${escapeHTML(i.image)}" alt="${escapeHTML(i.name)}" style="width:50px; height:auto;"></td>
                            <td>${escapeHTML(i.name)}</td>
                            <td>${q}</td>
                            <td>${p.toFixed(2)} د.ل</td>
                            <td>${total} د.ل</td>
                        </tr>
                    `;
                });
            } else {
                rows = "<tr><td colspan='5'>لا توجد منتجات في هذا الطلب.</td></tr>";
            }

            modalContent.innerHTML = `
                <h3>بيانات الزبون</h3>
                <p><strong>الاسم:</strong> ${escapeHTML(order.full_name || 'غير معروف')}</p>
                <p><strong>الهاتف:</strong> ${escapeHTML(order.delivery_phone || '')}</p>
                <p><strong>المدينة:</strong> ${escapeHTML(order.delivery_city || '')}</p>
                <p><strong>العنوان:</strong> ${escapeHTML(order.delivery_address || '')}</p>
                <p><strong>نوع الطلب:</strong> ${order.is_pickup == 1 ? "استلام من المحل" : "توصيل"}</p>
<p><strong>الفرع:</strong> ${order.is_pickup == 1 ? order.pickup_branch : "—"}</p>
                ${order.delivery_landmark 
                    ? `<p><strong>معلم قريب:</strong> ${escapeHTML(order.delivery_landmark)}</p>` 
                    : ''
                }
                ${order.notes
                    ? `<p><strong>ملاحظات:</strong> ${escapeHTML(order.notes)}</p>`
                    : ''
                }

                <h3>المنتجات</h3>
                <table class="modal-table">
                    <tr>
                     <th>الصورة</th>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>الإجمالي</th>
                    </tr>
                    ${rows}
                </table>

                <h3>الدفع</h3>
                <p><strong>طريقة الدفع:</strong> ${order.payment_method === "wallet" ? "محفظة" : "كاش عند التوصيل"}</p>

                <h3>الإجمالي</h3>
                <p><strong>${Number(order.total_price).toFixed(2)} د.ل</strong></p>

                <h3>التاريخ</h3>
                <p>${escapeHTML(order.order_date || '')}</p>
            `;

            // بناء قائمة المنتجات المرفوضة (checkbox)
            const rejectList = document.getElementById("reject-products-list");
            if (rejectList) {
                if (items.length > 0) {
                    rejectList.innerHTML = items.map(i => `
                        <label class="reject-item">
                            <input type="checkbox" value="${i.product_id}">
                            <span>${escapeHTML(i.name)} - كمية: ${i.quantity}</span>
                        </label>
                    `).join("");
                } else {
                    rejectList.innerHTML = "<p>لا توجد منتجات في هذا الطلب.</p>";
                }
            }

            // إظهار/إخفاء القسم حسب الحالة الحالية
            toggleRejectSection();
        })
        .catch(() => {
            modalContent.innerHTML = "<p style='color:red;'>تعذر الاتصال بالسيرفر.</p>";
        });
}

// حفظ حالة الطلب
document.getElementById("saveStatusBtn").addEventListener("click", function () {
    const modalTitle = document.getElementById("modal-title");
    const orderId = modalTitle.dataset.orderId;
    const status  = document.getElementById("statusSelect").value;

    if (!orderId) {
        alert("لا يوجد طلب محدد.");
        return;
    }

    fetch("update_order_status.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "order_id=" + encodeURIComponent(orderId) +
              "&status=" + encodeURIComponent(status)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("تم تحديث حالة الطلب بنجاح.");
            closeModal();
            location.reload(); // ممكن تلغيها لو تبي كله Ajax
        } else {
            alert("حدث خطأ أثناء تحديث الحالة.");
        }
    })
    .catch(() => {
        alert("تعذر الاتصال بالسيرفر.");
    });
});

// حفظ المنتجات المرفوضة
document.getElementById("saveRejectedBtn").addEventListener("click", function () {
    if (!currentOrderId) {
        alert("لا يوجد طلب محدد.");
        return;
    }

    const statusSelect = document.getElementById("statusSelect");
    if (!statusSelect || statusSelect.value !== "reject") {
        alert("يجب أولاً تعيين حالة الطلب إلى (مرفوض).");
        return;
    }

    const checked = Array.from(
        document.querySelectorAll("#reject-products-list input[type='checkbox']:checked")
    ).map(el => el.value);

    if (checked.length === 0) {
        alert("الرجاء اختيار منتج واحد على الأقل.");
        return;
    }

    fetch("reject_products.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "order_id=" + encodeURIComponent(currentOrderId) +
              "&rejected=" + encodeURIComponent(JSON.stringify(checked))
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("تم حفظ المنتجات المرفوضة وإخفاؤها من الأقسام.");
            closeModal();
            location.reload();
        } else {
            alert(data.message || "حدث خطأ أثناء حفظ المنتجات المرفوضة.");
        }
    })
    .catch(() => {
        alert("تعذر الاتصال بالسيرفر.");
    });
});

// زر طباعة الإيصال البسيط
document.getElementById("printBtn").addEventListener("click", function () {
    const modalTitle = document.getElementById("modal-title");
    const orderId = modalTitle.dataset.orderId;
    if (!orderId) {
        alert("لا يوجد طلب محدد للطباعة.");
        return;
    }
    window.open("print_receipt.php?id=" + encodeURIComponent(orderId), "_blank");
});

// دالة بسيطة لتفادي مشاكل XSS
function escapeHTML(str) {
    return String(str || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<!-- سكربت التحديث التلقائي العام -->
<script src="../js/update.js"></script>

<!-- صوت الطلب الجديد -->
<audio id="newOrderSound">
    <source src="sounds/new_order.mp3" type="audio/mpeg">
</audio>

<audio id="orderAlert" src="sounds/new_order.mp3" preload="auto"></audio>

<script>
// ===== التحديث التلقائي + الصوت للطلبات الجديدة =====
let firstLoad = true;

autoUpdate('../updates.php?type=admin_orders_full', function(data){

    let map = {
        'preparing':'جاري التحضير',
        'ready':'جاهز',
        'delivering':'جاري التوصيل',
        'delivered':'تم التوصيل',
        'reject':'مرفوض'
    };

    let table = document.querySelector(".orders-table");
    let sound = document.getElementById("newOrderSound");
    let badge = document.getElementById("newOrdersBadge");

    if (!data.orders || !table) return;

    data.orders.forEach(order => {

        if (order.computed_type === 'reservation') return; 
        
        let rowId = "order_row_" + order.order_id;
        let row   = document.getElementById(rowId);

        // ===== طلب جديد =====
        if (!row) {

            let payment = order.payment_method === "wallet" ? "محفظة" : "كاش";

          let newRow = `
    <tr id="order_row_${order.order_id}" class="new-row">

        <td>#${order.order_id}</td>

        <td>${order.full_name ?? 'غير معروف'}</td>

        <td>${order.items_count}</td>

        <td>${parseFloat(order.total_price).toFixed(2)} د.ل</td>

        <td>${payment}</td>

        <td id="status_${order.order_id}">
            ${map[order.status] ?? order.status}
        </td>

        <td>
            ${order.is_pickup == 1 ? order.pickup_branch : "—"}
        </td>

        <td>${order.order_date}</td>

        <td>
            <button class="btn view-details" data-id="${order.order_id}">تفاصيل</button>
        </td>

    </tr>
`;

            table.insertAdjacentHTML('afterbegin', newRow);

            let newRowElement = document.getElementById(rowId);
            if (newRowElement) {
                setTimeout(() => {
                    newRowElement.style.transition = "0.8s";
                    newRowElement.style.background = "white";
                }, 2000);
            }

            // تشغيل الصوت فقط بعد أول تحميل
            if (!firstLoad && sound) {
                sound.currentTime = 0;
                sound.play().catch(()=>{});
            }

            // زيادة عدّاد الطلبات الجديدة
            if (badge) {
                let current = parseInt(badge.innerText) || 0;
                badge.innerText = current + 1;
            }

            console.log("🔔 طلب جديد:", order.order_id);
        }

        // ===== تحديث الحالة لو تغيّرت =====
        let statusCell = document.getElementById("status_" + order.order_id);
        if (statusCell) {
            statusCell.innerText = map[order.status] ?? order.status;
        }
    });

    firstLoad = false;

}, 2000);
</script>

</body>
</html>