<?php
/**
 * Created by PhpStorm.
 * User: Mārtiņš
 * Date: 05.08.2018
 * Time: 23:07
 */

namespace AppBundle\Client;


class BackendClient
{
    public function performRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $responseRaw = curl_exec($ch);
        curl_close($ch);
        return $this->formatXmlResponse($responseRaw);
    }

    public function formatXmlResponse($response)
    {
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        return json_decode($json,TRUE);
    }
}