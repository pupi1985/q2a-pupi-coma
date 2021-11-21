<?php

class PUPI_COMA_ConfigurationExportPage
{
    const FILE_NAME = 'config.json';

    /** @var string */
    private $directory;

    /** @var PUPI_COMA_ConfigurationManager */
    private $configurationManager;

    /**
     * @param string $directory
     * @param string $urltoroot
     */
    public function load_module(string $directory, string $urltoroot)
    {
        $this->directory = $directory;
    }

    public function match_request($request): bool
    {
        return $request === PUPI_COMA_Constants::URL_CONFIGURATION_EXPORT_PAGE;
    }

    public function process_request($request)
    {
        // For qa_strlen()
        require_once QA_INCLUDE_DIR . 'util/string.php';

        // For qa_get_logged_in_level()
        require_once QA_INCLUDE_DIR . 'app/users.php';

        require_once 'src/PUPI_COMA_ConfigurationManager.php';
        $this->configurationManager = new PUPI_COMA_ConfigurationManager($this->directory);
        $this->configurationManager->loadVersionConfiguration(QA_VERSION);

        try {
            $this->checkPermissions((int)qa_get_logged_in_level());

            $filters = [];

            if (qa_opt(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS)) {
                $filters[PUPI_COMA_ConfigurationManager::FIELD_OPTIONS] = $this->getOptionsMetasFilter();
            }

            $filters[PUPI_COMA_ConfigurationManager::FIELD_SERVER_INFO] = (bool)qa_opt(PUPI_COMA_Constants::SETTING_FILTER_SERVER_INFO);

            if (qa_opt(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG)) {
                $filters[PUPI_COMA_ConfigurationManager::FIELD_QA_CONFIG] = $this->getQaConfigMetasFilter();
            }

            $filters[PUPI_COMA_ConfigurationManager::FIELD_TABLE_ROW_COUNTS] = (bool)qa_opt(PUPI_COMA_Constants::SETTING_FILTER_TABLE_ROW_COUNTS);

            $output = $this->configurationManager->getConfiguration($filters);
            $output = json_encode($output, JSON_PRETTY_PRINT);

            $this->setRequestHeaders(qa_strlen($output));

            echo $output;

            return null;
        } catch (Exception $e) {
            $output = qa_content_prepare();
            $output['title'] = qa_lang_html('admin/admin_title');
            $output['error'] = qa_lang_html('admin/no_privileges');

            return $output;
        }
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

    /**
     * @param array $settingsToMetas
     *
     * @return array
     */
    private function getFilterMetas(array $settingsToMetas): array
    {
        $metas = [];

        foreach ($settingsToMetas as $setting => $meta) {
            if (qa_opt($setting)) {
                $metas[] = $meta;
            }
        }

        return $metas;
    }

    private function getOptionsMetasFilter(): array
    {
        $settingsToMetas = [
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SITE_URL => PUPI_COMA_Options::META_SITE_URL,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SECRET => PUPI_COMA_Options::META_SECRET,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_EMAIL => PUPI_COMA_Options::META_EMAIL,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_ACCOUNT => PUPI_COMA_Options::META_ACCOUNT,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_PLUGIN => PUPI_COMA_Options::META_PLUGIN,
        ];

        return $this->getFilterMetas($settingsToMetas);
    }

    private function getQaConfigMetasFilter(): array
    {
        $settingsToMetas = [
            PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_MYSQL_CONN => PUPI_COMA_QaConfig::META_MYSQL_CONNECTION,
            PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_SITE_URL => PUPI_COMA_QaConfig::META_SITE_URL,
            PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_PATH => PUPI_COMA_QaConfig::META_PATH,
        ];

        return $this->getFilterMetas($settingsToMetas);
    }

    /**
     * @param int $userLevel
     *
     * @throws Exception
     */
    private function checkPermissions(int $userLevel)
    {
        if ($userLevel < QA_USER_LEVEL_ADMIN) {
            throw new Exception();
        }
    }
}
