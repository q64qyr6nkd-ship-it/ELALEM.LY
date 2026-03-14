<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

  <title>حلويات العالم</title>

  <!-- خطوط -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&family=Playfair+Display:wght@500&display=swap">

  <!-- أيقونات -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="css/aa.css">
</head>

<body>

  <div class="container">

    <!-- الشعار -->
    <img src="images/logo.jpg" alt="شعار حلويات العالم" class="logo">

    <!-- عنوان رئيسي -->
    <h1>حلويات العالم</h1>
    <h2>Elaleem Sweets</h2>

    <!-- 🔵 قسم السوشيال ميديا (الأول) -->
    <div class="social-section">
      <h3>تابعونا على وسائل التواصل الاجتماعي</h3>

      <div class="social-links">

        <div class="social-item" onclick="openLink('https://www.facebook.com/share/1H5FGytbHS/?mibextid=wwXIfr')">
          <i class="fab fa-facebook"></i>
          <span>حلويات العالم | Facebook</span>
        </div>

        <div class="social-item" onclick="openLink('https://www.instagram.com/sweetselalem?igsh=eWx6dXh2ZnA4bTFv')">
          <i class="fab fa-instagram"></i>
          <span>حلويات العالم | Instagram</span>
        </div>

        <div class="social-item" onclick="openLink('https://www.tiktok.com/@user1274958130431?_r=1&_t=ZM-92PfRESX8BA')">
          <i class="fab fa-tiktok"></i>
          <span>حلويات العالم | TikTok</span>
        </div>

        <div class="social-item" onclick="openLink('https://wa.me/218923228798')">
          <i class="fab fa-whatsapp"></i>
          <span>تواصل معنا عبر WhatsApp</span>
        </div>

      </div>
    </div>

    <!-- 🔵 زر التسوق -->
    <button onclick="goToHome()">ابدأ التسوق الآن</button>


    <!-- 🔵 نبذة عنا (آخر الصفحة) -->
    <div class="about-section">
      <h3>نبذة عنّا</h3>
      <p>
        نبدع في صناعة الحلويات منذ عام 2004، بخبرة تمزج الطعم الأصيل بالفخامة الحديثة.
        غذاؤكم بين أيدٍ أمينة — لأننا نهتم بأدق تفاصيل الجودة والطعم ونستخدم أفضل المكوّنات.
      </p>
    </div>

  </div>

  <!-- فوتر -->
  <footer>© جميع الحقوق محفوظة - حلويات العالم 2025</footer>

  <script>
    function goToHome() {
      localStorage.setItem("visitedBefore", "true");
      window.location.href = "index.php";
    }

    function openLink(url) {
      window.open(url, "_self");
    }
  </script>

</body>
</html>