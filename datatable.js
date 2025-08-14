$(document).ready(function () {
    // Get language from PHP - this should be passed from your PHP page
    let currentLang = 'en'; // Default fallback
    
    // Method 1: Get from PHP variable (recommended)
    // Add this to your HTML/PHP page: <script>var phpLang = '<?php echo lang(); ?>';</script>
    if (typeof phpLang !== 'undefined') {
        currentLang = phpLang;
    }
    
    // Method 2: Get from HTML lang attribute (if you're setting it via PHP)
    else if (document.documentElement.lang) {
        currentLang = document.documentElement.lang;
    }
    
    // Method 3: Get from URL parameter (if using lang.php URL switching)
    else {
        const urlParams = new URLSearchParams(window.location.search);
        currentLang = urlParams.get('lang') || 'en';
    }
    
    console.log("DEBUG: currentLang =", currentLang);
    console.log('Current language detected:', currentLang);
    
    let languageOption = {};

    if (currentLang === 'tr') {
        languageOption = {
            decimal: ",",
            thousands: ".",
            emptyTable: "Tabloda veri yok",
            info: "Gösterilen _START_ ile _END_ arası, toplam _TOTAL_ kayıt",
            infoEmpty: "Gösterilecek kayıt yok",
            infoFiltered: "(_MAX_ toplam kayıttan filtrelendi)",
            lengthMenu: "_MENU_ kayıt göster",
            loadingRecords: "Yükleniyor...",
            processing: "İşleniyor...",
            search: "Ara:",
            zeroRecords: "Eşleşen kayıt bulunamadı",
            paginate: {
                first: "İlk",
                last: "Son",
                next: "Sonraki",
                previous: "Önceki"
            }
        };
        document.documentElement.setAttribute('dir', 'ltr');
    } else if (currentLang === 'fr') {
        languageOption = {
            decimal: ",",
            thousands: " ",
            emptyTable: "Aucune donnée disponible dans le tableau",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ entrées",
            infoEmpty: "Affichage de 0 à 0 sur 0 entrées",
            infoFiltered: "(filtré à partir de _MAX_ entrées au total)",
            lengthMenu: "Afficher _MENU_ entrées",
            loadingRecords: "Chargement...",
            processing: "Traitement...",
            search: "Rechercher:",
            zeroRecords: "Aucun enregistrement correspondant trouvé",
            paginate: {
                first: "Premier",
                last: "Dernier",
                next: "Suivant",
                previous: "Précédent"
            }
        };
        document.documentElement.setAttribute('dir', 'ltr');
    } else if (currentLang === 'ru') {
        languageOption = {
            decimal: ",",
            thousands: " ",
            emptyTable: "В таблице нет данных",
            info: "Показано _START_ до _END_ из _TOTAL_ записей",
            infoEmpty: "Показано 0 до 0 из 0 записей",
            infoFiltered: "(отфильтровано из _MAX_ общих записей)",
            lengthMenu: "Показать _MENU_ записей",
            loadingRecords: "Загрузка...",
            processing: "Обработка...",
            search: "Поиск:",
            zeroRecords: "Соответствующих записей не найдено",
            paginate: {
                first: "Первая",
                last: "Последняя",
                next: "Следующая",
                previous: "Предыдущая"
            }
        };
        document.documentElement.setAttribute('dir', 'ltr');
    } else if (currentLang === 'ar') {
        languageOption = {
            decimal: ".",
            thousands: ",",
            emptyTable: "لا توجد بيانات في الجدول",
            info: "عرض _START_ إلى _END_ من أصل _TOTAL_ مدخل",
            infoEmpty: "عرض 0 إلى 0 من أصل 0 مدخل",
            infoFiltered: "(تم التصفية من _MAX_ إجمالي المدخلات)",
            lengthMenu: "أظهر _MENU_ مدخلات",
            loadingRecords: "جاري التحميل...",
            processing: "جاري المعالجة...",
            search: "بحث:",
            zeroRecords: "لم يتم العثور على سجلات مطابقة",
            paginate: {
                first: "الأول",
                last: "الأخير",
                next: "التالي",
                previous: "السابق"
            }
        };
        // Set RTL direction for Arabic
        document.documentElement.setAttribute('dir', 'rtl');
    } else {
        // Default English
        languageOption = {
            decimal: ".",
            thousands: ",",
            emptyTable: "No data available in table",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries to show",
            infoFiltered: "(filtered from _MAX_ total entries)",
            lengthMenu: "Show _MENU_ entries",
            loadingRecords: "Loading...",
            processing: "Processing...",
            search: "Search:",
            zeroRecords: "No matching records found",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        };
        // Ensure LTR direction for non-Arabic languages
        document.documentElement.setAttribute('dir', 'ltr');
    }

    $('#example').DataTable({
        responsive: true,
        deferRender: true,
        language: languageOption,
        initComplete: function () {
            $('#example').css('display', 'table').css('visibility', 'visible');
        }
    });
});