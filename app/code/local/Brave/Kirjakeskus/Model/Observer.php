<?php

class Brave_Kirjakeskus_Model_Observer {

	var $interchangeRef = '85';
	var $docType = 'Order';	
    var $ver = '1.1';	
    var $purposeCode = 'Original';	
    var $parties = [
		'local' => ['id' => '6430041110002', 'name' => 'Atena Kustannus OY', 'type' => 'GLN'],
		'remote' => ['id' => '6430049920009', 'name' => 'Porvoon Kirjakeskus', 'type' => 'GLN']
	];
    
    var $messageNum = '';    
    var $timestamp = '';    
    var $filename = '';	
    var $xmlString = '';    
    var $shippingCode = 0;

    var $mode = 'test'; // 'test' or 'production'
    var $ftpCredentials = [
        'test' => ['server' => 'ftp.kirjakeskus.fi', 'user' => 't_ateena', 'pass' => 't_r5Z_?z30zwE%'],
        'production' => ['server' => 'ftp.kirjakeskus.fi', 'user' => 'ateena', 'pass' => 'r5Z_?z30zwE%']
    ];

    public function SendRequest($observer) {
    	$orderDetails = []; // Collect order details
        $event = $observer->getEvent();
        $order = $event->getInvoice()->getOrder();

        $this->messageNum = $order->getIncrementId();
        $this->timestamp = date("Ymd") . "T" . date("Hi");
        $this->filename = 'TradeOrder_Atena_' . $this->timestamp . '-' . $this->messageNum . '.xml';
        $this->xmlString = $this->composeXml($order);

        if ($this->writeXmlToFile()) {
            $order->setXmlFileKirjakeskus($this->filename);
            Mage::log('XML created (' . $this->filename . ') for order #' . $this->messageNum , null , 'kirjakeskus.log');
        } else {
            Mage::log('XML creation failed for order #' . $this->messageNum . '!!!' , null , 'kirjakeskus.log');
            die("AINEISTON SIIRROSSA ONGELMA. OTA YHTEYS ASIAKASPALVELUUN!");
        }

        if ($this->putXmlFile()) {
            $order->setXmlFileKirjaksekusSent(1);
            Mage::log('XML transfered successfully for order #' . $this->messageNum , null , 'kirjakeskus.log');
        } else {
            Mage::log('XML transfer failed for order #' . $this->messageNum . '!!!' , null , 'kirjakeskus.log');
            die("AINEISTON SIIRROSSA ONGELMA. OTA YHTEYS ASIAKASPALVELUUN!");
        }
    }

    public function writeXmlToFile() {
        $baseDir = Mage::getBaseDir();
        $tgtDir = $baseDir . DS . 'var' . DS . 'xml' . DS;
        $filepath = $tgtDir . $this->filename;
        $fh = fopen($filepath, 'w');
        return fwrite($fh, $this->xmlString);
    }

    public function putXmlFile() {
        $baseDir = Mage::getBaseDir();
        $tgtDir = $baseDir . DS . 'var' . DS . 'xml' . DS;
        $filepath = $tgtDir . $this->filename;

        $server = $this->ftpCredentials[$this->mode]['server'];
        $user = $this->ftpCredentials[$this->mode]['user'];
        $pass = $this->ftpCredentials[$this->mode]['pass'];

        $conn = ftp_connect($server);
        if (!$conn) return false;

        $login = ftp_login($conn, $user, $pass);
        if (!$login) return false;

	ftp_pasv($conn, true); // Turn passive mode on
        ftp_chdir($conn, "in");
        ftp_put($conn, $this->filename, $filepath, FTP_ASCII);
        ftp_close($conn);

        return true;
    }

    public function composeXml($order) {
    	$Writer = new XMLWriter();
    	$Writer->openMemory();
        $Writer->setIndent(true);
        $Writer->setIndentString(' ');
        $Writer->startDocument('1.0', 'UTF-8');

        $Writer->startElement('EDItXMessage');
        $Writer->writeAttribute('version', '1.0');

        $Writer->startElement('EDItXMessageHeader');
        $Writer->writeElement('InterchangeReference', $this->interchangeRef);
        $Writer->writeElement('MessageNumber', $this->messageNum);
        $Writer->writeElement('DocumentType', $this->docType);
        $Writer->writeElement('VersionNumber', $this->ver);
        $Writer->writeElement('NumberOfDocuments', '1');
        $Writer->startElement('Sender');
        $Writer->startElement('PartyID');
        $Writer->writeElement('PartyIDType', $this->parties['local']['type']);
        $Writer->writeElement('Identifier', $this->parties['local']['id']);
        $Writer->endElement(); // PartyID
        $Writer->startElement('PartyName');
        $Writer->writeElement('NameLine', $this->parties['local']['name']);
        $Writer->endElement(); // PartyName
        $Writer->endElement(); // Sender
        $Writer->startElement('Receiver');
        $Writer->startElement('PartyID');
        $Writer->writeElement('PartyIDType', $this->parties['remote']['type']);
        $Writer->writeElement('Identifier', $this->parties['remote']['id']);
        $Writer->endElement(); // PartyID
        $Writer->startElement('PartyName');
        $Writer->writeElement('NameLine', $this->parties['remote']['name']);
        $Writer->endElement(); // PartyName
        $Writer->endElement(); // Receiver
        $Writer->writeElement('PurposeCode', $this->purposeCode);
        $Writer->writeElement('SentDateTime', $this->timestamp);
        $Writer->endElement(); // EDItXMessageHeader

        $Writer->startElement('EDItXMessagePayload');
        $Writer->startElement('Order');
        $Writer->writeAttribute('version', $this->ver);
        $Writer->startElement('Header');

        $parts = explode(' ', $order->getCreatedAt());
        $pvmt = explode ("-", $parts[0]);
        $pvt = explode (":", $parts[1]);
        $issueDateTime = $pvmt[0] . $pvmt[1] . $pvmt[2] . "T" . $pvt[0] . $pvt[1];

        $Writer->writeElement('OrderNumber', $this->messageNum);
        $Writer->writeElement('IssueDateTime', $issueDateTime);
        $Writer->writeElement('PurposeCode', $this->purposeCode);

        $Writer->startElement('BuyerParty');
        $Writer->startElement('PartyID');
        $Writer->writeElement('PartyIDType', $this->parties['local']['type']);
        $Writer->writeElement('Identifier', $this->parties['local']['id']);
        $Writer->endElement(); // PartyID
        $Writer->startElement('PartyName');
        $Writer->writeElement('NameLine', $this->parties['local']['name']);
        $Writer->endElement(); // PartyName
        $Writer->endElement(); // BuyerParty  

        $Writer->startElement('SellerParty');
        $Writer->startElement('PartyID');
        $Writer->writeElement('PartyIDType', $this->parties['remote']['type']);
        $Writer->writeElement('Identifier', $this->parties['remote']['id']);
        $Writer->endElement(); // PartyID
        $Writer->startElement('PartyName');
        $Writer->writeElement('NameLine', $this->parties['remote']['name']);
        $Writer->endElement(); // PartyName
        $Writer->endElement(); // SellerParty        

        $shipping = $order->getShippingAddress()->getData();
        $billing = $order->getBillingAddress()->getData();

        // Toimitustiedot
        $Writer->startElement('ShipToParty');
        $Writer->startElement('PartyID');
        $Writer->writeElement('PartyIDType', $this->parties['local']['type']);
        $Writer->writeElement('Identifier', $this->parties['local']['id']);
        $Writer->endElement(); // PartyID
        $Writer->startElement('PartyName');
        $Writer->writeElement('NameLine', $shipping['firstname'] . ' ' . $shipping['lastname']);
        $Writer->endElement(); // PartyName
        $Writer->startElement('PostalAddress');
        $address = explode(PHP_EOL, $shipping['street']);
        $Writer->writeElement('AddressLine', sizeof($address) > 1 ? $address[0] : '');
        $Writer->writeElement('AddressLine', sizeof($address) > 1 ? $address[1] : $address[0]);
        $Writer->writeElement('PostalTownCity', $shipping['country_id'] == 'FI' ? $shipping['city'] : $shipping['postcode'] . ', ' . $shipping['city']);
        $Writer->writeElement('PostalCode', $shipping['country_id'] == 'FI' ? $shipping['postcode'] : '');
        $Writer->writeElement('CountryCode', $shipping['country_id']);
        $Writer->endElement(); // PostalAddress
        $Writer->endElement(); // ShipToParty        

        // Laskutustiedot
        $Writer->startElement('BillToParty');
        $Writer->startElement('PartyID');
        $Writer->writeElement('PartyIDType', $this->parties['local']['type']);
        $Writer->writeElement('Identifier', $this->parties['local']['id']);
        $Writer->endElement(); // PartyID
        $Writer->startElement('PartyName');
        $Writer->writeElement('NameLine', $billing['firstname'] . ' ' . $billing['lastname']);
        $Writer->endElement(); // PartyName
        $Writer->startElement('PostalAddress');
        $address = explode(PHP_EOL, $billing['street']);
        $Writer->writeElement('AddressLine', sizeof($address) > 1 ? $address[0] : '');
        $Writer->writeElement('AddressLine', sizeof($address) > 1 ? $address[1] : $address[0]);
        $Writer->writeElement('PostalTownCity', $billing['country_id'] == 'FI' ? $billing['city'] : $billing['postcode'] . ', ' . $billing['city']);
        $Writer->writeElement('PostalCode', $billing['country_id'] == 'FI' ? $billing['postcode'] : '');
        $Writer->writeElement('CountryCode', $billing['country_id']);
        $Writer->endElement(); // PostalAddress
        $Writer->endElement(); // BillToParty        

        // Tilauksen lisätiedot
        $shippingDesc = $order->getShippingDescription();
        if (strstr($shippingDesc, 'ulkomaan') !== false) {
            if (strstr($shippingDesc, 'Nopea') !== false) $this->shippingCode = 808;
            else $this->shippingCode = 807;
        } else {
            if (strstr($shippingDesc, 'Nopea') !== false) $this->shippingCode = 806;
            else $this->shippingCode = 805;
        }

        $Writer->startElement('Message');
        $Writer->writeElement('MessageType', '01'); // Tilauksen id
        $Writer->writeElement('MessageLine', $this->messageNum);
        $Writer->endElement(); // Message
        $Writer->startElement('Message');
        $Writer->writeElement('MessageType', '40'); // Toimitustapa
        $Writer->writeElement('MessageLine', $this->shippingCode);
        $Writer->endElement(); // Message
        $Writer->startElement('Message');
        $Writer->writeElement('MessageType', '41'); // "Static data"
        $Writer->writeElement('MessageLine', 'FRF');
        $Writer->endElement(); // Message
        $Writer->startElement('Message');
        $Writer->writeElement('MessageType', '42'); // "Static data"
        $Writer->writeElement('MessageLine', 'EKA');
        $Writer->endElement(); // Message
        $Writer->endElement(); // Header

        $i = 1;
        foreach ($order->getAllItems() as $item) {

            $Writer->startElement('ItemDetail');
            $Writer->writeElement('LineNumber', $i);
            $Writer->startElement('ProductID');
            $Writer->writeElement('ProductIDType', 'EAN13');
            $Writer->writeElement('Identifier', $item->getSku());
            $Writer->endElement(); // ProductID
            $Writer->writeElement('OrderQuantity', $item->getQtyToShip());   

            // Kuponkikoodi
            if ($couponCode = $order->coupon_code) {
                $Writer->startElement('Message');
                $Writer->writeElement('MessageType', '01');
                $Writer->writeElement('MessageLine', $couponCode);
                $Writer->endElement();
            }

            // Haetaan tai lasketaan tuotteen mahdollinen alennusprosentti
            $discountPct = (float)$item->getDiscountPercent();
            if (!$discountPct) { // No pct set, is there amount?

                // Divide with quantity, because discount amount contains all products' discounts!
                $itemDiscount = (float)$item->getDiscountAmount() / (int)$item->getQtyOrdered();

                if ($itemDiscount) { // Yes
                    $price = (float)$item->getBaseOriginalPrice(); // Price incl. tax (discount has tax)
                    $discountPct = $itemDiscount / $price * 100;
                }
            }

            // Tuotteen veroton hinta (sis. mahdollisen alennuksen!)
            $itemPrice = (float)$item->getBasePrice();
            if ($discountPct) {
                $itemPrice = $itemPrice - $itemPrice * $discountPct / 100;
                $discountPct = number_format($discountPct, 2, ',', '');
            }
            $itemPrice = number_format($itemPrice, 2, ',', '');

            // Tuotteen hinta
            $Writer->startElement('Message');
            $Writer->writeElement('MessageType', '30');
            $Writer->writeElement('MessageLine', $itemPrice);
            //$Writer->writeElement('MessageLine', number_format($itemPrice, 2, ',', ''));
            $Writer->endElement();

            // Alennusprosentti (*100)
            $Writer->startElement('Message');
            $Writer->writeElement('MessageType', '31');
            $Writer->writeElement('MessageLine', $discountPct);
            $Writer->endElement();

            $Writer->endElement(); // ItemDetail
            $i++;
        }

        // Toimituskulut yhteensä omana tietona -- kaikki hinnat pluginin mukaan verottomia (vaikka sis. veron)...
        $Writer->startElement('ItemDetail');
        $Writer->writeElement('LineNumber', $i);
        $Writer->startElement('ProductID');
        $Writer->writeElement('ProductIDType', 'EAN13');
        $Writer->writeElement('Identifier', '9996501');
        $Writer->endElement(); // ProductID

        $shippingAmount     = (float)$order->getShippingAmount();
        $shippingDiscount   = 0;

        if (!$shippingAmount) {
            $resource       = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');

            // Päätellään toimituskulun summa, jos ilmainen toimitus.
            // Haetaan perussumma toimituskulutaulusta tilatulle tuotemäärälle ja toimituskohteelle.
            // Oletus tässä on, ettei ilmaisia toimituksia lähetetä nopealla toimitustavalla.
            $query   = 'SELECT * FROM ' . $resource->getTableName('shipping_matrixrate');
            $query  .= ' WHERE condition_from_value >= ' . $order->getTotalItemCount();
            $query  .= ' AND condition_to_value <= ' . $order->getTotalItemCount();

            // Lisätään toimituskohde ehtoihin, ja oletetaan, että kyseessä on normaali toimitus... (parempaan ei pysty)
            $query  .= ' AND dest_country_id = ';
            $query  .= $order->getShippingAddress()->getCountry() === 'FI' ? '\'FI\'' : '0';
            $query  .= ' AND delivery_type LIKE \'Normaali%\'';
            $results = $readConnection->fetchAll($query);

            if ($results) {
                $shippingAmount     = (float)$results[0]['price'];
                $shippingDiscount   = $shippingAmount;
            }
        }

        $Writer->writeElement('OrderQuantity', '1');
        $Writer->startElement('Message');
        $Writer->writeElement('MessageType', '30');
        $Writer->writeElement('MessageLine', number_format($shippingAmount / 1.10, 2, ',', ''));
        $Writer->endElement();

        $Writer->startElement('Message');
        $Writer->writeElement('MessageType', '31'); // Alennusprosentti
        $Writer->writeElement('MessageLine', number_format($shippingDiscount / 1.10, 2, ',', ''));
        $Writer->endElement();
        $Writer->endElement(); // ItemDetail
        $i++;

        // @todo Mahdollisen nopean toimituksen ja ulkomaalisän laitto omina riveinään
        if (in_array($this->shippingCode, [808, 806])) { // Toimitustapalisä
            $Writer->startElement('ItemDetail');
            $Writer->writeElement('LineNumber', $i);
            $Writer->startElement('ProductID');
            $Writer->writeElement('ProductIDType', 'EAN13');
            $Writer->writeElement('Identifier', '9996502');
            $Writer->endElement(); // ProductID
            $Writer->writeElement('OrderQuantity', '1');   
            $Writer->startElement('Message');
            $Writer->writeElement('MessageType', '30');
            $Writer->writeElement('MessageLine', number_format(6 / 1.10, 2, ',', '')); // @fix (possible?)
            $Writer->endElement();
            $Writer->startElement('Message');
            $Writer->writeElement('MessageType', '31'); // Alennusprosentti
            $Writer->writeElement('MessageLine', '0');
            $Writer->endElement();
            $Writer->endElement(); // ItemDetail
            $i++;
        }
        if (in_array($this->shippingCode, [808, 807])) { // Ulkomaan toimitus
            $Writer->startElement('ItemDetail');
            $Writer->writeElement('LineNumber', $i);
            $Writer->startElement('ProductID');
            $Writer->writeElement('ProductIDType', 'EAN13');
            $Writer->writeElement('Identifier', '9996503');
            $Writer->endElement(); // ProductID
            $Writer->writeElement('OrderQuantity', '1');   
            $Writer->startElement('Message');
            $Writer->writeElement('MessageType', '30');
            $Writer->writeElement('MessageLine', number_format(($shippingAmount - 6) / 1.10, 2, ',', '')); // @fix (possible?)
            $Writer->endElement();
            $Writer->startElement('Message');
            $Writer->writeElement('MessageType', '31'); // Alennusprosentti
            $Writer->writeElement('MessageLine', '0');
            $Writer->endElement();
            $Writer->endElement(); // ItemDetail
            $i++;
        }

        $i--;
        $Writer->startElement('Summary');
        $Writer->writeElement('NumberOfLines', $i);
        $Writer->endElement();

        $Writer->endElement(); // Order
        $Writer->endElement(); // EDItXMessagePayload
        $Writer->endElement(); // EDItXMessage
        $Writer->endDocument();       

        $xml = $Writer->outputMemory();
        return $xml; 
    }

}
