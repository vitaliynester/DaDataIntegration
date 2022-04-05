<?php

use Dadata\DadataClient;
use PhpOffice\PhpSpreadsheet\IOFactory;


class Parser
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
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
            $creds = require_once 'config.php';
            $token = $creds['token'];
            $secret = $creds['secret'];

            $dadata = new DadataClient($token, $secret);
            $result = [];

            foreach ($addresses as $address) {
                $response = $dadata->clean("address", $address);
                $result[] = $response;
            }

            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
}