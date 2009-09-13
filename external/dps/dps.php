<?php 

function process_request($name, $amount, $ccnum, $ccmm, $ccyy, $merchRef)
{
    $cmdDoTxnTransaction .= "<Txn>";
    $cmdDoTxnTransaction .= "<PostUsername>".DPS_USERNAME."</PostUsername>";
    $cmdDoTxnTransaction .= "<PostPassword>".DPS_PASSWORD."</PostPassword>";
    $cmdDoTxnTransaction .= "<Amount>$amount</Amount>";
    $cmdDoTxnTransaction .= "<InputCurrency>NZD</InputCurrency>";
    $cmdDoTxnTransaction .= "<CardHolderName>$name</CardHolderName>";
    $cmdDoTxnTransaction .= "<CardNumber>$ccnum</CardNumber>";
    $cmdDoTxnTransaction .= "<DateExpiry>$ccmm$ccyy</DateExpiry>";
    $cmdDoTxnTransaction .= "<TxnType>Purchase</TxnType>";
    $cmdDoTxnTransaction .= "<MerchantReference>$merchRef</MerchantReference>";
    $cmdDoTxnTransaction .= "</Txn>";
    $URL = "www.paymentexpress.com/pxpost.aspx";
    			 
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,"https://".$URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$cmdDoTxnTransaction);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //Needs to be included if no *.crt is available to verify SSL certificates
    curl_setopt($ch, CURLOPT_SSLVERSION,3);	
    $result = curl_exec ($ch); 
    curl_close ($ch);
    			   
    return parse_xml($result);
}

function parse_xml($data)
{
    global $params;
    $xml_parser = xml_parser_create();
    xml_parse_into_struct($xml_parser, $data, $vals, $index);
    xml_parser_free($xml_parser);
    	
    $params = array();
    $level = array();
    foreach ($vals as $xml_elem) {
        if ($xml_elem['type'] == 'open') {
        if (array_key_exists('attributes',$xml_elem)) {
        list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
        } 
        else {
        $level[$xml_elem['level']] = $xml_elem['tag'];
        }
        }
        if ($xml_elem['type'] == 'complete') {
        $start_level = 1;
        $php_stmt = '$params';
        			
        while($start_level < $xml_elem['level']) {
            $php_stmt .= '[$level['.$start_level.']]';
            $start_level++;
        }
        $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
        eval($php_stmt);
        }
    }
    return $params;
}