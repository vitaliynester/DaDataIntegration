<?php

use PhpOffice\PhpSpreadsheet\IOFactory;


class Parser
{
    private string $path;

    private string $apiUrl = 'https://cleaner.dadata.ru/api/v1/clean';
    private CurlHandle $handle;

    public function __construct(string $path)
    {
        $this->path = $path;

        $creds = require_once 'config.php';
        $token = $creds['token'];
        $secret = $creds['secret'];
        self::initDaData($token, $secret);
    }

    private function initDaData(string $token, string $secret)
    {
        $this->handle = curl_init();
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $token,
            "X-Secret: " . $secret,
        ));
        curl_setopt($this->handle, CURLOPT_POST, 1);
    }

    public function getAddresses(): array
    {
        $addressArray = [];
        $xlsxData = $this->parseXlsx();
        $it = 6;
        for (; $it < count($xlsxData); $it++) {
            if (isset($xlsxData[$it][2])) {
                $addressArray[] = $xlsxData[$it][2];
            }
        }
        return $addressArray;
    }

    private function parseXlsx(): array
    {
        try {
            $inputFileName = $this->path;

            $inputFileType = IOFactory::identify($inputFileName);

            /**  Create a new Reader of the type that has been identified  **/
            $reader = IOFactory::createReader($inputFileType);

            /**  Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load($inputFileName);

            /**  Convert Spreadsheet Object to an Array for ease of use  **/
            return $spreadsheet->getActiveSheet()->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getGeoData(array $addresses): array
    {
        try {
            $url = $this->apiUrl . "/address";
            $fields = $addresses;
            return $this->executeRequest($url, $fields);
        } catch (Exception $e) {
            return [];
        }
    }

    private function executeRequest($url, $fields)
    {
        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_POST, 1);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = $this->exec();
        return json_decode($result, true);
    }

    private function exec()
    {
        $result = curl_exec($this->handle);
        $info = curl_getinfo($this->handle);
        if ($info['http_code'] == 429) {
            throw new Exception('Too many requests');
        } elseif ($info['http_code'] != 200) {
            throw new Exception('Request failed with http code ' . $info['http_code'] . ': ' . $result);
        }
        return $result;
    }
}