function autoUpdate(url, callback, interval = 2000) {

    function run() {
        fetch(url, {
            method: 'GET',
            credentials: 'include'
        })
        .then(res => res.json())
        .then(data => {
            // نستدعي callback بدون شروط
            callback(data);
        })
        .catch(err => console.error("AutoUpdate Error:", err));
    }

    // تشغيل مباشر عند فتح الصفحة
    run();

    // وبعدها كل interval
    setInterval(run, interval);
}