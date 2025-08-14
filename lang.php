<?php
/**
 * Système de traduction multilingue centralisé
 * Version: 2.0
 * Support: Français, Russe, Arabe, Turc, Anglais
 * 
 * Usage:
 * require_once 'path/to/lang.php';
 * echo __('Hello World');
 */


class UniversalTranslator {
    private static $instance = null;
    private $language = 'en';
    private $translations = [];
    private $translationsPath = '';
    private $supportedLanguages = ['en', 'fr', 'ru', 'ar', 'tr'];
    private $fallbackLanguage = 'en';
    private $autoTranslate = true;
    private $cacheTranslations = true;
    private $newTranslations = [];
    
    // Configuration des langues
    private $languageConfig = [
        'en' => ['name' => 'English', 'direction' => 'ltr', 'flag' => '🇺🇸'],
        'fr' => ['name' => 'Français', 'direction' => 'ltr', 'flag' => '🇫🇷'],
        'ru' => ['name' => 'Русский', 'direction' => 'ltr', 'flag' => '🇷🇺'],
        'ar' => ['name' => 'العربية', 'direction' => 'rtl', 'flag' => '🇸🇦'],
        'tr' => ['name' => 'Türkçe', 'direction' => 'ltr', 'flag' => '🇹🇷']
    ];
    
    private function __construct() {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Définir le chemin des traductions
        $this->translationsPath = $this->findTranslationsPath();
        
        // Définir la langue actuelle
        $this->setLanguage($this->detectLanguage());
        
        // Charger les traductions
        $this->loadTranslations();
        
        // Initialiser les traductions de base si nécessaire
        $this->initializeBaseTranslations();
    }
    
    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Trouver automatiquement le chemin des traductions
    private function findTranslationsPath() {
        $possiblePaths = [
            __DIR__ . '/translations/',
            __DIR__ . '/../translations/',
            $_SERVER['DOCUMENT_ROOT'] . '/translations/',
            dirname($_SERVER['SCRIPT_FILENAME']) . '/translations/'
        ];
        
        foreach ($possiblePaths as $path) {
            if (is_dir($path) || mkdir($path, 0755, true)) {
                return $path;
            }
        }
        
        // Fallback: créer dans le même dossier que ce fichier
        $fallbackPath = __DIR__ . '/translations/';
        if (!is_dir($fallbackPath)) {
            mkdir($fallbackPath, 0755, true);
        }
        return $fallbackPath;
    }
    
    // Détecter la langue automatiquement
    private function detectLanguage() {
        // 1. Paramètre URL (?lang=fr)
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->supportedLanguages)) {
            $_SESSION['language'] = $_GET['lang'];
            $this->setCookie('language', $_GET['lang']);
            return $_GET['lang'];
        }
        
        // 2. Session
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->supportedLanguages)) {
            return $_SESSION['language'];
        }
        
        // 3. Cookie
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->supportedLanguages)) {
            $_SESSION['language'] = $_COOKIE['language'];
            return $_COOKIE['language'];
        }
        
        // 4. En-tête du navigateur
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browserLangs as $lang) {
                $lang = substr(trim(explode(';', $lang)[0]), 0, 2);
                if (in_array($lang, $this->supportedLanguages)) {
                    $_SESSION['language'] = $lang;
                    $this->setCookie('language', $lang);
                    return $lang;
                }
            }
        }
        
        // 5. Langue par défaut
        return $this->fallbackLanguage;
    }

// Add this function to your UniversalTranslator class or as a standalone function

function formatTranslatedDate($timestamp, $format = 'M j, Y g:i A') {
    global $translator;
    
    try {
        // Handle different timestamp formats
        if (is_string($timestamp) && $timestamp === 'now') {
            $date = new DateTime();
        } elseif (is_string($timestamp)) {
            $date = new DateTime($timestamp);
        } elseif (is_numeric($timestamp)) {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
        } else {
            $date = new DateTime();
        }
    } catch (Exception $e) {
        error_log("Invalid timestamp in formatTranslatedDate: " . $e->getMessage());
        $date = new DateTime();
    }
    
    // Get month abbreviation and translate it
    $month = strtolower($date->format('M'));
    $monthKey = "month_$month";
    $translatedMonth = __($monthKey, $date->format('M'));
    
    // Get AM/PM and translate it
    $ampm = strtolower($date->format('A'));
    $translatedAmPm = __($ampm, $date->format('A'));
    
    // Format the date
    $formattedDate = $date->format($format);
    
    // Replace month and AM/PM with translated versions
    $formattedDate = str_replace($date->format('M'), $translatedMonth, $formattedDate);
    $formattedDate = str_replace($date->format('A'), $translatedAmPm, $formattedDate);
    
    return $formattedDate;
}
    
    // Définir un cookie de façon sécurisée
    private function setCookie($name, $value, $days = 30) {
        $expire = time() + ($days * 24 * 60 * 60);
        setcookie($name, $value, $expire, '/', '', false, true);
    }
    
    // Définir la langue
    public function setLanguage($language) {
        if (in_array($language, $this->supportedLanguages)) {
            $this->language = $language;
            $_SESSION['language'] = $language;
            $this->setCookie('language', $language);
            $this->loadTranslations();
        }
    }
    
    // Charger les traductions
    private function loadTranslations() {
        $file = $this->translationsPath . $this->language . '.json';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->translations = json_decode($content, true) ?: [];
        } else {
            $this->translations = [];
        }
    }
    
    // Obtenir une traduction
    public function get($key, $default = null, $params = []) {
        // Si la traduction existe
        if (isset($this->translations[$key])) {
            $translation = $this->translations[$key];
        } else {
            // Traduction manquante
            $translation = $default ?? $key;
            
            // Ajouter à la liste des nouvelles traductions
            if ($this->autoTranslate && !isset($this->newTranslations[$key])) {
                $this->newTranslations[$key] = $translation;
                $this->addNewTranslation($key, $translation);
            }
        }
        
        // Remplacer les paramètres si fournis
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace(':' . $param, $value, $translation);
            }
        }
        
        return $translation;
    }
    
    // Ajouter une nouvelle traduction
    private function addNewTranslation($key, $defaultValue) {
        // Ajouter à la langue actuelle
        $this->translations[$key] = $defaultValue;
        $this->saveTranslations($this->language, $this->translations);
        
        // Auto-traduire pour les autres langues
        if ($this->autoTranslate && $this->language === $this->fallbackLanguage) {
            $this->autoTranslateToAllLanguages($key, $defaultValue);
        }
    }
    
    // Auto-traduire vers toutes les langues
    private function autoTranslateToAllLanguages($key, $text) {
        foreach ($this->supportedLanguages as $lang) {
            if ($lang === $this->fallbackLanguage) continue;
            
            $langFile = $this->translationsPath . $lang . '.json';
            $langTranslations = [];
            
            if (file_exists($langFile)) {
                $content = file_get_contents($langFile);
                $langTranslations = json_decode($content, true) ?: [];
            }
            
            // Traduire seulement si la clé n'existe pas
            if (!isset($langTranslations[$key])) {
                $translated = $this->googleTranslate($text, $lang);
                $langTranslations[$key] = $translated;
                $this->saveTranslations($lang, $langTranslations);
                
                // Pause pour éviter le rate limiting
                usleep(100000); // 0.1 seconde
            }
        }
    }
    
    // Google Translate gratuit
    private function googleTranslate($text, $toLang) {
        try {
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl={$toLang}&dt=t&q=" . urlencode($text);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data[0][0][0])) {
                    return $data[0][0][0];
                }
            }
        } catch (Exception $e) {
            error_log("Translation error: " . $e->getMessage());
        }
        
        return $text; // Retourner le texte original en cas d'échec
    }
    
    // Sauvegarder les traductions
    private function saveTranslations($language, $translations) {
        $file = $this->translationsPath . $language . '.json';
        
        // Trier par clés pour un fichier plus lisible
        ksort($translations);
        
        $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($file, $json);
    }
    
    // Initialiser les traductions de base
    private function initializeBaseTranslations() {
        $baseTranslations = [
            // Navigation générale
            'Home' => 'Home',
            'About' => 'About',
            'Contact' => 'Contact',
            'Services' => 'Services',
            'Login' => 'Login',
            'Logout' => 'Logout',
            'Register' => 'Register',
            'Dashboard' => 'Dashboard',
            
            // Actions communes
            'Save' => 'Save',
            'Cancel' => 'Cancel',
            'Delete' => 'Delete',
            'Edit' => 'Edit',
            'View' => 'View',
            'Add' => 'Add',
            'Update' => 'Update',
            'Submit' => 'Submit',
            'Search' => 'Search',
            'Filter' => 'Filter',
            'Export' => 'Export',
            'Import' => 'Import',
            
            // Status
            'Active' => 'Active',
            'Inactive' => 'Inactive',
            'Pending' => 'Pending',
            'Approved' => 'Approved',
            'Rejected' => 'Rejected',
            'Completed' => 'Completed',
            'In Progress' => 'In Progress',
            'Draft' => 'Draft',
            'Published' => 'Published',
            
            // Formulaires
            'Name' => 'Name',
            'Email' => 'Email',
            'Password' => 'Password',
            'Phone' => 'Phone',
            'Address' => 'Address',
            'Message' => 'Message',
            'Subject' => 'Subject',
            'Date' => 'Date',
            'Time' => 'Time',
            'Description' => 'Description',
            'Title' => 'Title',
            
            // Messages
            'Success' => 'Success',
            'Error' => 'Error',
            'Warning' => 'Warning',
            'Info' => 'Information',
            'Loading' => 'Loading...',
            'No data found' => 'No data found',
            'Are you sure?' => 'Are you sure?',
            'Operation completed successfully' => 'Operation completed successfully',
            'An error occurred' => 'An error occurred',
            
            // Dashboard spécifique
            'HOF Dashboard' => 'HOF Dashboard',
            'Human Resource Dashboard' => 'Human Resource Dashboard',
            'Rectorate Dashboard' => 'Rectorate Dashboard',
            'Requests for Review' => 'Requests for Review',
            'Request Type:' => 'Request Type:',
            'Status:' => 'Status:',
            'More Details' => 'More Details',
            'Approve' => 'Approve',
            'Reject' => 'Reject',
            'Update Status' => 'Update Status',
        ];
        
        // Créer le fichier de base anglais si il n'existe pas
        $baseFile = $this->translationsPath . 'en.json';
        if (!file_exists($baseFile)) {
            $this->saveTranslations('en', $baseTranslations);
        } else {
            // Fusionner avec les traductions existantes
            $existingTranslations = json_decode(file_get_contents($baseFile), true) ?: [];
            $mergedTranslations = array_merge($baseTranslations, $existingTranslations);
            $this->saveTranslations('en', $mergedTranslations);
        }
        
        // Auto-générer pour les autres langues si elles n'existent pas
        foreach ($this->supportedLanguages as $lang) {
            if ($lang === 'en') continue;
            
            $langFile = $this->translationsPath . $lang . '.json';
            if (!file_exists($langFile)) {
                $this->generateTranslationsForLanguage($lang, $baseTranslations);
            }
        }
    }
    
    // Générer les traductions pour une langue spécifique
    private function generateTranslationsForLanguage($language, $baseTranslations) {
        $translated = [];
        $count = 0;
        
        foreach ($baseTranslations as $key => $englishText) {
            $translated[$key] = $this->googleTranslate($englishText, $language);
            $count++;
            
            // Pause tous les 10 éléments pour éviter le rate limiting
            if ($count % 10 === 0) {
                sleep(1);
            }
        }
        
        $this->saveTranslations($language, $translated);
    }
    
    // Générer le sélecteur de langue
    public function getLanguageSelector($style = 'dropdown') {
        $current = $this->language;
        $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
        $queryParams = $_GET;
        
        switch ($style) {
            case 'flags':
                return $this->getFlagSelector($current, $baseUrl, $queryParams);
            case 'buttons':
                return $this->getButtonSelector($current, $baseUrl, $queryParams);
            case 'dropdown':
            default:
                return $this->getDropdownSelector($current, $baseUrl, $queryParams);
        }
    }
    
    // Sélecteur dropdown
    private function getDropdownSelector($current, $baseUrl, $queryParams) {
        $html = '<div class="language-selector-container">';
        $html .= '<select onchange="changeLanguage(this.value)" class="form-select form-select-sm language-selector">';
        
        foreach ($this->supportedLanguages as $lang) {
            $selected = ($lang === $current) ? 'selected' : '';
            $flag = $this->languageConfig[$lang]['flag'];
            $name = $this->languageConfig[$lang]['name'];
            $html .= "<option value='{$lang}' {$selected}>{$flag} {$name}</option>";
        }
        
        $html .= '</select>';
        $html .= $this->getLanguageSelectorScript($baseUrl, $queryParams);
        $html .= '</div>';
        
        return $html;
    }
    
    // Sélecteur avec drapeaux
    private function getFlagSelector($current, $baseUrl, $queryParams) {
        $html = '<div class="language-flags-container">';
        
        foreach ($this->supportedLanguages as $lang) {
            $active = ($lang === $current) ? 'active' : '';
            $flag = $this->languageConfig[$lang]['flag'];
            $name = $this->languageConfig[$lang]['name'];
            
            $queryParams['lang'] = $lang;
            $url = $baseUrl . '?' . http_build_query($queryParams);
            
            $html .= "<a href='{$url}' class='language-flag {$active}' title='{$name}'>{$flag}</a>";
        }
        
        $html .= '</div>';
        $html .= $this->getFlagSelectorCSS();
        
        return $html;
    }
    
    // Script pour le sélecteur
    private function getLanguageSelectorScript($baseUrl, $queryParams) {
        $queryParamsJson = json_encode($queryParams);
        
        return "<script>
            function changeLanguage(lang) {
                const params = {$queryParamsJson};
                params.lang = lang;
                const queryString = Object.keys(params).map(key => key + '=' + params[key]).join('&');
                window.location.href = '{$baseUrl}?' + queryString;
            }
        </script>";
    }
    
    // CSS pour les drapeaux
    private function getFlagSelectorCSS() {
        return '<style>
            .language-flags-container {
                display: flex;
                gap: 10px;
                justify-content: center;
                margin: 10px 0;
            }
            .language-flag {
                font-size: 24px;
                text-decoration: none;
                padding: 5px;
                border-radius: 4px;
                transition: transform 0.2s;
            }
            .language-flag:hover {
                transform: scale(1.2);
            }
            .language-flag.active {
                background-color: #007bff;
                transform: scale(1.1);
            }
        </style>';
    }
    
    // Obtenir la configuration de la langue
    public function getLanguageConfig($lang = null) {
        $lang = $lang ?? $this->language;
        return $this->languageConfig[$lang] ?? $this->languageConfig[$this->fallbackLanguage];
    }
    
    // Obtenir la direction du texte
    public function getTextDirection($lang = null) {
        return $this->getLanguageConfig($lang)['direction'];
    }
    
    // Obtenir la langue actuelle
    public function getCurrentLanguage() {
        return $this->language;
    }
    
    // Obtenir les langues supportées
    public function getSupportedLanguages() {
        return $this->supportedLanguages;
    }
    
    // Ajouter une langue
    public function addLanguage($code, $name, $direction = 'ltr', $flag = '') {
        if (!in_array($code, $this->supportedLanguages)) {
            $this->supportedLanguages[] = $code;
            $this->languageConfig[$code] = [
                'name' => $name,
                'direction' => $direction,
                'flag' => $flag
            ];
        }
    }
    
    // Définir la traduction automatique
    public function setAutoTranslate($enabled) {
        $this->autoTranslate = $enabled;
    }
    
    // Obtenir toutes les traductions pour debugging
    public function getAllTranslations($language = null) {
        $language = $language ?? $this->language;
        $file = $this->translationsPath . $language . '.json';
        
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        
        return [];
    }
    
    // Exporter les traductions
    public function exportTranslations($language = null) {
        $language = $language ?? $this->language;
        $translations = $this->getAllTranslations($language);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="translations_' . $language . '.json"');
        echo json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // CSS par défaut
    public function getDefaultCSS() {
        return '<style>
            .language-selector-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1050;
                background: white;
                padding: 8px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .language-selector {
                min-width: 140px;
                border: 1px solid #ddd;
                font-size: 14px;
                padding: 4px 8px;
            }
            
            /* Support RTL */
            html[dir="rtl"] {
                direction: rtl;
            }
            
            html[dir="rtl"] .text-end {
                text-align: left !important;
            }
            
            html[dir="rtl"] .text-start {
                text-align: right !important;
            }
            
            /* Mobile responsive */
            @media (max-width: 768px) {
                .language-selector-container {
                    position: relative;
                    top: auto;
                    right: auto;
                    margin: 15px auto;
                    max-width: 200px;
                }
            }
                html[dir="rtl"] .notification-time {
    text-align: right;
}
        </style>';
    }
}

// Initialisation globale
$translator = UniversalTranslator::getInstance();

// Fonction globale raccourcie
if (!function_exists('__')) {
    function __($key, $default = null, $params = []) {
        global $translator;
        return $translator->get($key, $default, $params);
    }
}

// Fonctions helper
if (!function_exists('t')) {
    function t($key, $default = null, $params = []) {
        return __($key, $default, $params);
    }
}

if (!function_exists('lang')) {
    function lang() {
        global $translator;
        return $translator->getCurrentLanguage();
    }
}

if (!function_exists('isRtl')) {
    function isRtl() {
        global $translator;
        return $translator->getTextDirection() === 'rtl';
    }
}

// Helper pour l'inclusion dans HTML
if (!function_exists('getLanguageHTML')) {
    function getLanguageHTML($style = 'dropdown', $includeCSS = true) {
        global $translator;
        $html = '';
        
        if ($includeCSS) {
            $html .= $translator->getDefaultCSS();
        }
        
        $html .= $translator->getLanguageSelector($style);
        
        return $html;
    }
}

// Auto-inclure les métadonnées HTML si demandé
if (!function_exists('setLanguageHTML')) {
    function setLanguageHTML() {
        global $translator;
        $lang = $translator->getCurrentLanguage();
        $dir = $translator->getTextDirection();
        
        echo '<script>
            document.documentElement.setAttribute("lang", "' . $lang . '");
            document.documentElement.setAttribute("dir", "' . $dir . '");
        </script>';
    }
}
?>