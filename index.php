<?php
session_start();
include "db/db.php";

// جلب المنتجات المميزة
$stmt = $conn->prepare("SELECT * FROM products WHERE is_featured = 1 AND available = 1 ORDER BY id DESC");
$stmt->execute();
$featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب الأكثر مبيعاً
$stmt = $conn->prepare("SELECT * FROM products WHERE is_best_seller = 1 AND available = 1 ORDER BY id DESC");
$stmt->execute();
$bestSellingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تقسيم كل 3 عناصر في مجموعة
$bestChunks = array_chunk($bestSellingProducts, 3);

// التحقق من وجود مستخدم مسجل الدخول
$userName = "";
$userRole = "";
if (isset($_SESSION['user'])) {
    $userName = $_SESSION['user']['full_name'];
    $userRole = $_SESSION['user']['role'];
}

// لحساب عدد العناصر في السلة
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta charset="UTF-8">
    <title>حلويات العالم</title>
    <!-- ملف التنسيق الخارجي الجديد -->
    <link rel="stylesheet" href="css/hh.css">
    <!-- خط عربي جميل -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Amiri&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- القائمة الجانبية -->

<!-- القائمة الجانبية -->
<nav class="sidebar" id="sidebar">
    <ul>

        <li>
            <a href="#hero">
                <i class="fa-solid fa-house"></i> 
                الرئيسية
            </a>
        </li>

     
        <li>
            <a href="#sections">
                <i class="fa-solid fa-layer-group"></i>
                الأقسام
            </a>
        </li>

        <li>
            <a href="wellcom.php">
                <i class="fa-solid fa-envelope"></i>
                تواصل معنا
            </a>
        </li>

        <?php if(empty($userName)): ?>
            <li>
                <a href="admin/auth/register.php">
                    <i class="fa-solid fa-user-plus"></i>
                    التسجيل
                </a>
            </li>

            <li>
                <a href="admin/auth/login.php">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    تسجيل الدخول
                </a>
            </li>
        <?php else: ?>
            <li class="welcome">
                <i class="fa-solid fa-user"></i>
                مرحبا، <?php echo htmlspecialchars($userName); ?>
            </li>

            <?php if (isset($_SESSION['user'])): ?>
            <li>
                <a href="sections/my_orders.php">
                    <i class="fa-solid fa-box"></i>
                    طلباتي
                </a>
            </li>
        <?php endif; ?>


        <?php if(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
            <li>
                <a href="admin/admin_dashboard.php">
                    <i class="fa-solid fa-gear"></i>
                    لوحة الإدارة
                </a>
            </li>
        <?php endif; ?>

        <li>
            <a href="admin/user/wallet.php">
                <i class="fa-solid fa-wallet"></i>
                محفظتي
            </a>
        </li>

        <li>
            <a href="sections/cart.php">
                <i class="fa-solid fa-cart-shopping"></i>
                السلة
            </a>
        </li>


        <li>
                <a href="admin/auth/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    تسجيل الخروج
                </a>
            </li>
        <?php endif; ?>
        
    </ul>
</nav>

<!-- الشريط العلوي (App Bar) -->
<header class="app-bar">
    <div id="menu-toggle" class="menu-toggle">☰</div>
    <div class="app-bar-title">
        حـلــــويـات الــعــالــم
        <span>ELALEEM SWEETS</span>
    </div>
</header>

<!-- قسم الترحيب الجديد -->
<section class="hero" id="hero">
    <div class="hero-content">
        <h1>مرحبًا بكم في حلويات العالم</h1>
        <p>أفخم الحلويات الشرقية والمغربية والكيكات المصنوعة بأجود المكونات.</p>
        <a href="#sections" class="btn main-btn">استعرض الأقسام</a>
      
    </div>

    <div class="hero-logo-wrapper">
        <img src="images/logo4.png" class="hero-logo" alt="حلويات العالم">
    </div>
</section>

    
    
<!-- الأقسام -->
<section id="sections" class="sections">
    <h2>الأقسام</h2>
    <div class="search-box main-page-search">
    <form action="search.php" method="GET">
        <input 
            type="text" 
            name="q"
            placeholder="🔍 ابحث عن منتج..."
            required
        >
    </form>
</div>
    <div class="sections-grid">
        <a href="sections/eastern.php" class="section-box" data-images="images/eastern1.jpg,images/eastern2.jpg">
            <span>الحلويات الشرقية</span>
        </a>
        <a href="sections/moroccan.php" class="section-box" data-images="images/moroccan1.jpg,images/moroccan2.jpg">
            <span>الحلويات المغربية</span>
        </a>
        <a href="sections/nuts.php" class="section-box" data-images="images/nuts1.jpg,images/nuts2.jpg">
            <span>اللوزيات</span>
        </a>
        <a href="sections/cakes.php" class="section-box" data-images="images/cakes1.JPG,images/cakes2.JPG">
            <span>الكيكات</span>
        </a>
        <a href="sections/chocolate.php" class="section-box" data-images="images/choco1.JPG,images/choco2.JPG">
            <span>الشوكولاتة</span>
        </a>
        <a href="sections/juices.php" class="section-box" data-images="images/juice1.JPG,images/juice2.JPG">
            <span>العصائر</span>
        </a>
           <a href="sections/tort.php" class="section-box" data-images="images/tort.JPG,images/tort2.JPG">
            <span>التورتات</span>
        </a>
    </div>
</section>


<script>
document.addEventListener("DOMContentLoaded", function () {

    // --- أولاً: منطق القائمة الجانبية (Sidebar) ---
    const menuToggle = document.getElementById("menu-toggle");
    const sidebar    = document.getElementById("sidebar");

    if (menuToggle && sidebar) {
        menuToggle.addEventListener("click", (e) => {
            e.stopPropagation(); // منع إغلاق القائمة فور فتحها
            sidebar.classList.toggle("active");
        });
    }

    // إغلاق القائمة عند النقر في أي مكان خارجها
    document.addEventListener("click", function(e) {
        if (sidebar && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove("active");
        }
    });


    // --- ثانياً: منطق صناديق الأقسام (Section Boxes) مع الصور المتحركة ---
    document.querySelectorAll(".section-box").forEach(box => {
        const imagesAttr = box.dataset.images || "";
        const imgs = imagesAttr
            .split(",")
            .map(s => s.trim())
            .filter(Boolean); 

        if (!imgs.length) return;

        let idx = 0;

        // تعيين الصورة الأولى فوراً عند تحميل الصفحة
        box.style.backgroundImage = `url('${imgs[0]}')`;

        // تغيير الصور كل 7 ثوانٍ
        setInterval(() => {
            idx = (idx + 1) % imgs.length;
            const nextImageUrl = imgs[idx];

            // تحميل الصورة في الذاكرة أولاً لمنع ظهور مساحة بيضاء (Preloading)
            const imgPreloader = new Image();
            imgPreloader.src = nextImageUrl;
            
            imgPreloader.onload = () => {
                box.style.backgroundImage = `url('${nextImageUrl}')`;
            };
        }, 7000); 
    });


    // --- ثالثاً: منطق السليدر الرئيسي (Main Slider) ---
    let slides = document.querySelectorAll(".slide");
    if (slides.length > 0) {
        let slideIndex = 0;

        function showNextSlide() {
            slides[slideIndex].classList.remove("active");
            slideIndex = (slideIndex + 1) % slides.length;
            slides[slideIndex].classList.add("active");
        }

        // تغيير السليدر الرئيسي كل 3 ثوانٍ
        setInterval(showNextSlide, 3000);
    }

});
</script>
</body>
</html>
