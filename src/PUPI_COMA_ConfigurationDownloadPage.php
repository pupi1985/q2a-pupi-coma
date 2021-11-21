<?php

class PUPI_COMA_ConfigurationDownloadPage
{
    const FILE_NAME = 'config.json';

    private $directory;

    public function load_module($directory, $urltoroot)
    {
        $this->directory = $directory;
    }

    public function match_request($request): bool
    {
        return $request === PUPI_COMA_Constants::URL_CONFIGURATION_DOWNLOAD_PAGE;
    }

    public function process_request($request)
    {
        // For qa_strlen()
        require_once QA_INCLUDE_DIR . 'util/string.php';

        // For qa_db_mysql_version()
        require_once QA_INCLUDE_DIR . 'db/admin.php';

        $output = (new PUPI_COMA_ConfigurationManager($this->directory))->getConfiguration([
            PUPI_COMA_ConfigurationManager::FIELD_OPTIONS => [
                PUPI_COMA_Options::META_SITE_URL,
                PUPI_COMA_Options::META_SECRET,
                PUPI_COMA_Options::META_EMAIL,
                PUPI_COMA_Options::META_ACCOUNT,
            ],
            PUPI_COMA_ConfigurationManager::FIELD_SERVER_INFO => true,
            PUPI_COMA_ConfigurationManager::FIELD_QA_CONFIG => [
                PUPI_COMA_QaConfig::META_MYSQL_CONNECTION,
                PUPI_COMA_QaConfig::META_SITE_URL,
                PUPI_COMA_QaConfig::META_PATH,
            ],
        ]);
        $output = json_encode($output, JSON_PRETTY_PRINT);

        $this->setRequestHeaders(qa_strlen($output));

        echo $output;
    }

    /**
     * @param $size
     */
    private function setRequestHeaders($size)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/json');
        header(sprintf('Content-Disposition: attachment; filename="%s"', self::FILE_NAME));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $size);
    }
}
