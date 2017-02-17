<?php

class Maglife_Core_Model_Store extends Mage_Core_Model_Store
{

    public function getBaseUrl($type=self::URL_TYPE_LINK, $secure=null)
    {
        $store_code = $this->getCode();
        $url = parent::getBaseUrl($type, $secure);
        if ($url_ini = @$_SERVER['MAGENTO_DEVURLS'])
        {
            if ($urls = parse_ini_file($url_ini))
            {
                $host = parse_url($url, PHP_URL_HOST);
                if (isset($urls[$host]))
                {
                    $url = str_replace('://'.$host.'/', '://'.$urls[$host].'/', $url);
                }
            }
        }
        return $url;
    }

}

?>