<?php

/*
// Connect to production database
try {
    $dbh = new PDO('mysql:host=mysql18.nebula.fi;dbname=atenakustn3', 'atenakustn3', 'zaqH9APE');

    $coldefs = [
        'store', 'websites', 'attribute_set', 'type', 'category_ids', 'sku', 'has_options', 'name', 'subtopic', 'release_date', 
        'number_of_pages', 'original_book', 'translated_by', 'illustration', 'other_information', 'presentation_ingress', 'url_key', 
        'country_of_manufacture', 'msrp_enabled', 'msrp_display_actual_price_type', 'meta_title', 'meta_description', 'image', 
        'small_image', 'thumbnail', 'sizechart_image', 'custom_design', 'page_layout', 'options_container', 'gift_message_available', 
        'image_label', 'small_image_label', 'thumbnail_label', 'url_path', 'released', 'for_sale', 'status', 'visibility', 
        'inquiry_only', 'tax_class_id', 'is_recurring', 'weight', 'price', 'special_price', 'msrp', 'short_description', 
        'presentation_text', 'quotes', 'free_module', 'meta_keyword', 'custom_layout_update', 'news_from_date', 'news_to_date', 
        'special_from_date', 'special_to_date', 'custom_design_from', 'custom_design_to', 'qty', 'min_qty', 'use_config_min_qty', 
        'is_qty_decimal', 'backorders', 'use_config_backorders', 'min_sale_qty', 'use_config_min_sale_qty', 'max_sale_qty', 
        'use_config_max_sale_qty', 'is_in_stock', 'low_stock_date', 'notify_stock_qty', 'use_config_notify_stock_qty', 'manage_stock', 
        'use_config_manage_stock', 'stock_status_changed_auto', 'use_config_qty_increments', 'qty_increments', 
        'use_config_enable_qty_inc', 'enable_qty_increments', 'is_decimal_divided', 'stock_status_changed_automatically', 
        'use_config_enable_qty_increments', 'product_name', 'store_id', 'product_type_id', 'kirja_ulkoasu', 'product_status_changed', 
        'product_changed_websites', 'website', '_media_image', '_media_lable', '_media_position', '_media_is_disabled'
    ];

    $fp = fopen('media/import/books5.csv', 'w');
    fputcsv($fp, $coldefs);

    require_once 'app/Mage.php';
    Mage::app();

    $query = "SELECT 
                kirjat.*,
                (SELECT 
                        GROUP_CONCAT(luokat.kirjaluokka_nimi)
                    FROM
                        kirjaluokitus luokitus
                            LEFT JOIN
                        kirjaluokat luokat ON luokat.kirjaluokka_id = luokitus.kirjaluokitus_luokka_ref
                    WHERE
                        luokitus.kirjaluokitus_kirja_ref = kirjat.kirja_id) AS luokat,
                (SELECT 
                        GROUP_CONCAT(CONCAT(lainaus.lainaus_teksti,'<br/>-',lainaus.lainaus_kriitikko,', ',lainaus.lainaus_media,'<br/>',lainaus.lainaus_medialinkki) SEPARATOR '<br/><br/>')
                    FROM
                        lainaukset lainaus
                    WHERE
                        lainaus.lainaus_kirja_ref = kirjat.kirja_id) AS lainaukset,
                (SELECT 
                        GROUP_CONCAT(CONCAT(kirjailijat.kirjailija_etunimet,' ',kirjailijat.kirjailija_sukunimi))
                    FROM
                        kirjoittajat
                            LEFT JOIN
                        kirjailijat ON kirjailijat.kirjailija_id = kirjoittajat.kirjoittaja_kirjailija_ref
                    WHERE
                        kirjoittajat.kirjoittaja_kirja_ref = kirjat.kirja_id) AS kirjailijat,
                (SELECT 
                        sesongit.sesonki_nimi
                    FROM
                        sesonkiluokitus sluokitus
                            LEFT JOIN
                        sesongit ON sesongit.sesonki_id = sluokitus.sesonki_id
                    WHERE
                        sluokitus.kirja_id = kirjat.kirja_id
                    LIMIT 1) AS sesongit

            FROM
                atenakustn3.kirjat kirjat
            ORDER BY kirja_id
            LIMIT 800, 200";

    $i = 0;
    foreach($dbh->query($query, PDO::FETCH_ASSOC) as $result) {

        $categories = ['19'];
        if ($result['luokat']) {    
            foreach (explode(",", $result['luokat']) as $luokka) {
                $_category = Mage::getResourceModel('catalog/category_collection')
                    ->addFieldToFilter('name', utf8_encode($luokka))
                    ->getFirstItem();
                $categoryId = $_category->getId();
                if ($categoryId) $categories[] = $categoryId;
            }
        }
        if ($result['kirjailijat']) {        
            foreach (explode(",", $result['kirjailijat']) as $kirjailija) {
                $_category = Mage::getResourceModel('catalog/category_collection')
                    ->addFieldToFilter('name', utf8_encode($kirjailija))
                    ->getFirstItem();
                $categoryId = $_category->getId();
                if ($categoryId) $categories[] = $categoryId;
            }
        }
        if ($result['sesongit']) {
            foreach (explode(",", $result['sesongit']) as $sesonki) {
                $_category = Mage::getResourceModel('catalog/category_collection')
                    ->addFieldToFilter('name', utf8_encode($sesonki))
                    ->getFirstItem();
                $categoryId = $_category->getId();
                if ($categoryId) $categories[] = $categoryId;
            }        
        }

        $row = [
            0 => 'admin', 1 => 'base', 2 => 'Oletus', 3 => 'simple', 4 => '', 5 => '', 6 => 0, 7 => '', 8 => '', 9 => '', 10 => '',
            11 => '', 12 => '', 13 => '', 14 => '', 15 => '', 16 => '', 17 => '', 18 => 'Käytä kokoonpanoasetuksia',
            19 => 'Käytä kokoonpanoasetuksia', 20 => '', 21 => '', 22 => '', 23 => '', 24 => '', 25 => '', 26 => '',
            27 => 'Ei sommitelmaa', 28 => 'Tuotteen tietosarake', 29 => 'Ei', 30 => '', 31 => '', 32 => '', 33 => '', 34 => '',
            35 => '', 36 => '', 37 => 'Luettelo, haku', 38 => 'Ei', 39 => 'Verotettava tuote', 40 => 'Ei', 41 => 0.000, 42 => '',
            43 => '', 44 => '', 45 => '', 46 => '', 47 => '', 48 => '', 49 => '', 50 => '', 51 => '', 52 => '', 53 => '', 54 => '',
            55 => '', 56 => '', 57 => 100.0000, 58 => 0.0000, 59 => 1, 60 => 0, 61 => 0, 62 => 1, 63 => 0.0000, 64 => 1, 65 => 0.0000,
            66 => 1, 67 => 1, 68 => '', 69 => '', 70 => 1, 71 => 1, 72 => 0, 73 => 0, 74 => 1, 75 => 0.0000, 76 => 1, 77 => 0, 78 => 0,
            79 => 0, 80 => 1, 81 => '', 82 => 0, 83 => 'simple',
        ];
        
        $row[4] = implode(",", $categories); // Categories $row[4]
        $row[5] = str_replace(["\r", "\n"], '', $result['kirja_isbn']); // ISBN (sku) $row[5]
        $row[7] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_nimi'])); // Nimi (name)
        $row[8] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_alaotsikko'])); // Alaotsikko (subtobic)
        $row[9] = $result['kirja_ilmestyminen'] != '0000-00-00' ? date("j.n.Y", strtotime($result['kirja_ilmestyminen'])) : ''; // Julkaisu (release_date)
        $row[10] = $result['kirja_sivumaara']; // Sivumäärä (number_of_pages)
        $row[11] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_alkuteos'])); // Alkuperäinen teos (original_book)
        $row[12] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_suomentaja'])); // Suomentaja (translated_by)
        $row[13] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_kuvitus'])); // Kuvitus (illustration)
        $row[14] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_muuta'])); // Muuta (other_information)
        $row[15] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_ingressi'])); // Esittelyn ingressi (presentation_ingress)
        $row[16] = null; // url-key -- let magento create
        $row[20] = utf8_encode($result['kirja_nimi']); // Meta Title (meta_title)
        $row[21] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_alaotsikko'])); // Metatiedon kuvaus (meta_description)
        $row[22] = $row[23] = $row[24] = '/images/normit/' . $result['kirja_kuva']; // Kuva (normi, small_ ja thumb)
        $row[25] = $result['kirja_kuva_iso'] ? '/images/isot/' . $result['kirja_kuva_iso'] : ''; // Korkearesoluutiokuva (sizechart_image)
        $row[33] = null; // url_path -- let magento create
        $row[34] = $result['kirja_ilmestynyt'] ? 'Kyllä' : 'Ei'; // Ilmestynyt (released)
        $row[35] = $result['kirja_myytavana'] ? 'Kyllä' : 'Ei'; // Myytävänä (for_sale)
        $row[36] = $result['kirja_visib'] ? 'Käytössä' : 'Pois käytöstä'; // Näkyvillä (status)
        $row[42] = number_format((float)$result['kirja_hinta'], 4, '.', ''); // price
        $row[45] = $result['kirja_kirjastolk']; // Kirjastoluokka (short_description)
        $row[46] = str_replace(["\r", "\n"], '', utf8_encode($result['kirja_esittely'])); // Esittely (presentation_text)
        $row[47] = $result['lainaukset'] ? str_replace(["\r", "\n"], '', utf8_encode($result['lainaukset'])) : ''; // Lainaukset (jätetään pois, työläyttä liikaa jo tähän kustannukseen)
        $row[49] = utf8_encode($result['kirja_asiasanat']); // Metatiedon avainsanat
        $row[81] = utf8_encode($result['kirja_nimi']);
        $row[84] = utf8_encode($result['kirja_asu']);

        fputcsv($fp, $row);
    }

    fclose($fp);
    $dbh = null;
    die();

} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
*/
/*
$dbh = new PDO('mysql:host=mysql18.nebula.fi;dbname=atenakustn3', 'atenakustn3', 'zaqH9APE');

require_once 'app/Mage.php';
Mage::app();

$query = 'SELECT 
    k1.kirja_isbn AS k1_isbn, k2.kirja_isbn AS k2_isbn
FROM
    samanlaiset_kirjat ref
        JOIN
    kirjat k1 ON k1.kirja_id = ref.kirja_id
        JOIN
    kirjat k2 ON k2.kirja_id = ref.samanlainen_id ORDER BY k1.kirja_isbn ASC';

$isbn = null;
$arr = [];
foreach($dbh->query($query, PDO::FETCH_ASSOC) as $result) {
    if ($result['k1_isbn'] != $isbn && $isbn !== null) {
        $_product = Mage::getModel('catalog/product')->loadByAttribute('sku', key($arr));

        if ($_product) {
            $linkData = [];
            $i = 1;
            foreach ($arr[key($arr)] as $rel_sku) {
                $_related = Mage::getModel('catalog/product')->loadByAttribute('sku', $rel_sku);
                if ($_related) {
                    $linkData[$_related->getId()] = ['position' => $i];
                    $i++;
                }
            }

            if ($linkData) {
                $_product->setRelatedLinkData($linkData);
                $_product->save();
            }

        }

        $isbn = $result['k1_isbn'];
        $arr = [];
    } elseif ($isbn == null) {
        $isbn = $result['k1_isbn'];
    }

    $arr[$result['k1_isbn']][] = $result['k2_isbn'];
}

$_product = Mage::getModel('catalog/product')->loadByAttribute('sku', key($arr));
if ($_product) {
    $linkData = [];
    $i = 1;
    foreach ($arr[key($arr)] as $rel_sku) {
        $_related = Mage::getModel('catalog/product')->loadByAttribute('sku', $rel_sku);
        if ($_related) {
            $linkData[$_related->getId()] = ['position' => $i];
            $i++;
        }
    }

    if ($linkData) {
        $_product->setRelatedLinkData($linkData);
        $_product->save();
    }

}
*/