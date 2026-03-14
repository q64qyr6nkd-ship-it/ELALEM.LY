<?php
session_start();
include "../db/db.php"; // الاتصال بقاعدة البيانات

try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = 'chocolate' AND available = 1");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "<p>خطأ في جلب المنتجات: " . $e->getMessage() . "</p>";
    $products = [];
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>الشكولاتة  - حلويات العالم</title>
<link rel="stylesheet" href="../css/hh.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Amiri&display=swap" rel="stylesheet">
</head>
<body class="eastern-page">

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

<section class="products-section">
    <h1>الشكولاتة </h1>
    <div class="search-box">
        <input 
            type="text" 
            id="searchInput"
            placeholder="🔍 ابحث عن الشكولاتة ..."
            onkeyup="searchProducts()"
        >
    </div>
    <div class="products-container">
        <?php if(!empty($products)): ?>
            <?php foreach($products as $product): ?>
             <div  class="product-card"
  id="product-<?php echo $product['id']; ?>"
  data-id="<?php echo $product['id']; ?>"
                    data-name="<?php echo strtolower($product['name']); ?>"
     data-description="<?php echo strtolower($product['description']); ?>">
                  <img src="../images/<?php echo $product['image']; ?>" 
                    alt="<?php echo $product['name']; ?>" 
                    loading="lazy" 
                    decoding="async" />
                    <h3><?php echo $product['name']; ?></h3>
                    <p class="price"><?php echo $product['price']; ?> د.ل</p>
                    <p class="description"><?php echo $product['description']; ?></p>

                    <div class="quantity">
                        <span>1</span>
                        <button class="increase">+</button>
                        <button class="decrease">-</button>
                    </div>

                    <button class="add-to-cart" data-id="<?php echo $product['id']; ?>">أضف للسلة</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>لا توجد منتجات في هذا القسم</p>
        <?php endif; ?>
    </div>
</section>

<div class="cursor"></div>

<!-- كود أزرار السلة -->
<script>
const cartCount = document.querySelector("#cartCount");

document.querySelectorAll(".add-to-cart").forEach(btn => {
    btn.addEventListener("click", () => {
        const productId = btn.getAttribute("data-id");
        const quantity = parseInt(btn.closest(".product-card").querySelector(".quantity span").textContent);

        fetch("add_to_cart.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "product_id=" + productId + "&quantity=" + quantity
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                let count = parseInt(cartCount.textContent);
                cartCount.textContent = count + quantity;
            } else {
                alert(data.message);
            }
        });
    });
});

// زيادة ونقصان العدد
document.querySelectorAll(".increase").forEach(btn => {
    btn.addEventListener("click", () => {
        const span = btn.parentElement.querySelector("span");
        span.textContent = parseInt(span.textContent) + 1;
    });
});

document.querySelectorAll(".decrease").forEach(btn => {
    btn.addEventListener("click", () => {
        const span = btn.parentElement.querySelector("span");
        let val = parseInt(span.textContent);
        if(val > 1) span.textContent = val - 1;
    });
});
</script>

<script src="../js/update.js"></script>

<script>
// تحديث عداد السلة
autoUpdate('../updates.php?type=cart_count', function(data){
    if (data.success) {
        document.getElementById("cartCount").innerText = data.count;
    }
});
</script>

<!-- 🔥 تحديث المنتجات الخاصة بقسم الحلويات الشرقية فقط -->
<script>
function autoUpdateProducts() {

    fetch("../updates.php?type=products_update&cat=chocolate")
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;

            data.products.forEach(prod => {

                let card = document.querySelector(`.product-card[data-id="${prod.id}"]`);

                if (card) {

                    if (prod.available == 0) {
                        if (card.style.display !== "none") {
                            card.style.opacity = "0";
                            setTimeout(() => card.style.display = "none", 300);
                        }
                    } else {
                        card.style.display = "block";
                        setTimeout(() => card.style.opacity = "1", 50);
                    }
                }

            });
        });
}

setInterval(autoUpdateProducts, 2000);
autoUpdateProducts();
</script>

<script>
let lastScroll = 0;

window.addEventListener("scroll", () => {
    const header = document.querySelector(".header");
    const currentScroll = window.pageYOffset;

    if (currentScroll > lastScroll && currentScroll > 60) {
        header.classList.add("hide");
    } else {
        header.classList.remove("hide");
    }

    lastScroll = currentScroll;
});
</script>
    <script>
let debounceTimeout;

function searchProducts() {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        const input = document.getElementById("searchInput").value.toLowerCase();
        const products = document.getElementsByClassName("product-card");

        for (let i = 0; i < products.length; i++) {
            const name = products[i].getAttribute("data-name").toLowerCase();
            const desc = products[i].getAttribute("data-description").toLowerCase();

            if (name.includes(input) || desc.includes(input)) {
                products[i].classList.remove("hidden-by-search");
            } else {
                products[i].classList.add("hidden-by-search");
            }
        }
    }, 200); // تأخير 200ms بعد آخر ضغطة
}    
    </script>
    
        <!-- نافذة عرض الصورة المكبرة -->
<div class="image-modal" id="imageModal">
    <span class="close-btn" onclick="closeImageModal()">&times;</span>
    <img id="modalImage" src="" alt="صورة مكبرة">
</div>
    
    <script>
// فتح الصورة بشكل مكبر
document.querySelectorAll(".product-card img").forEach(img => {
    img.addEventListener("click", function() {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        
        modal.classList.add("active");
        modalImg.src = this.src;
        
        // منع التمرير في الخلفية
        document.body.style.overflow = "hidden";
    });
});

// إغلاق النافذة
function closeImageModal() {
    const modal = document.getElementById("imageModal");
    modal.classList.remove("active");
    document.body.style.overflow = "auto";
}

// إغلاق عند الضغط خارج الصورة
document.getElementById("imageModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

// إغلاق بزر ESC
document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeImageModal();
    }
});
</script>
</body>
</html>