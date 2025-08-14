<?php
require_once 'lang.php'; // Adjust this path if needed

$translator = UniversalTranslator::getInstance();
$translator->setAutoTranslate(true); // Enable for CLI only

echo "🔄 Starting missing-key translation generator...\n";

// Step 1: Load base (English) translations
$baseTranslations = $translator->getAllTranslations('en');
if (empty($baseTranslations)) {
    exit("❌ No base translations found in en.json.\n");
}

foreach ($translator->getSupportedLanguages() as $lang) {
    if ($lang === 'en') {
        echo "⏭ Skipping English (already base).\n";
        continue;
    }

    echo "🌍 Processing language: [$lang]...\n";

    $existingTranslations = $translator->getAllTranslations($lang);
    $missingKeys = array_diff_key($baseTranslations, $existingTranslations);

    if (empty($missingKeys)) {
        echo "✅ No missing keys for [$lang]. Already up-to-date.\n";
        continue;
    }

    echo "🔍 Found " . count($missingKeys) . " missing keys for [$lang]. Translating...\n";

    $translated = $existingTranslations; // Start with existing ones
    $count = 0;

    foreach ($missingKeys as $key => $englishText) {
        $translated[$key] = $translator->getInstance()->googleTranslate($englishText, $lang);
        $count++;
        echo "  + [$key] => " . $translated[$key] . "\n";

        if ($count % 10 === 0) {
            sleep(1); // Respect rate limits
        }
    }

    $translator->getInstance()->saveTranslations($lang, $translated);
    echo "✅ [$lang] updated with " . $count . " new keys.\n";
}

echo "\n🎉 All missing translations generated successfully.\n";
