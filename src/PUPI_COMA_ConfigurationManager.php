<?php

class PUPI_COMA_ConfigurationManager
{
    const FIELD_GENERAL_INFO = 'general_info';
    const FIELD_OPTIONS = 'options';
    const FIELD_SERVER_INFO = 'server_info';
    const FIELD_QA_CONFIG = 'qa_config';
    const FIELD_TABLE_ROW_COUNTS = 'table_row_counts';

    const SIMILAR_VERSIONS = [
        '1.8.7' => '1.8.6',
        '1.8.8' => '1.8.6',
    ];

    /** @var string */
    private $directory;

    /**
     * PUPI_COMA_ConfigurationManager constructor.
     *
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    public function getConfiguration(array $filters): array
    {
        $result[self::FIELD_GENERAL_INFO] = $this->getGeneralInfo();

        if (isset($filters[self::FIELD_OPTIONS])) {
            $result[self::FIELD_OPTIONS] = $this->getFilteredOptions($filters[self::FIELD_OPTIONS]);
        }

        if ($filters[self::FIELD_SERVER_INFO]) {
            $result[self::FIELD_SERVER_INFO] = $this->getServerInfo();
        }

        if (isset($filters[self::FIELD_QA_CONFIG])) {
            $result[self::FIELD_QA_CONFIG] = $this->getFilteredQaConfig($filters[self::FIELD_QA_CONFIG]);
        }

        if ($filters[self::FIELD_TABLE_ROW_COUNTS]) {
            $result[self::FIELD_TABLE_ROW_COUNTS] = $this->getTableRowCounts();
        }

        return $result;
    }

    /**
     * @param array $filteredSettings
     * @param string $filterValue
     *
     * @return array
     */
    private function excludeFromSettings(array $filteredSettings, string $filterValue): array
    {
        return array_filter($filteredSettings, function ($value) use ($filterValue) {
            return !isset($value[$filterValue]);
        });
    }

    /**
     * @param array $configurationFilters
     * @param array $filterKeys
     * @param array $allMetas
     * @param callback $callback
     *
     * @return array
     */
    private function getGenericFilteredSettings(array $configurationFilters, array $filterKeys, array $allMetas, callable $callback): array
    {
        $result = [];

        $excludeFilters = array_diff($allMetas, $configurationFilters);

        $filteredOptions = $filterKeys;
        foreach ($excludeFilters as $excludeFilter) {
            $filteredOptions = $this->excludeFromSettings($filteredOptions, $excludeFilter);
        }

        foreach ($filteredOptions as $setting => $metadata) {
            $result[$setting] = $callback($setting);
        }

        return $result;
    }

    /**
     * @param $filters
     *
     * @return array
     */
    private function getFilteredOptions($filters): array
    {
        return $this->getGenericFilteredSettings($filters, PUPI_COMA_Options::OPTIONS, PUPI_COMA_Options::getAllMetas(), function ($setting) {
            return qa_opt($setting);
        });
    }

    private function getServerInfo(): array
    {
        // For qa_db_mysql_version()
        require_once QA_INCLUDE_DIR . 'db/admin.php';

        return [
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'php_version' => PHP_VERSION,
            'operating_system' => PHP_OS,
            'mysql_version' => qa_db_mysql_version(),
        ];
    }

    private function getFilteredQaConfig($filters): array
    {
        $result = $this->getGenericFilteredSettings($filters, PUPI_COMA_QaConfig::GLOBAL_CONSTANTS, PUPI_COMA_QaConfig::getAllMetas(), function ($constant) {
            return defined($constant) ? constant($constant) : null;
        });

        $request_map = qa_get_request_map();
        $result['QA_CONST_PATH_MAP'] = empty($request_map) ? new stdClass() : $request_map;

        return $result;
    }

    private function getTableRowCounts(): array
    {
        $result = [];

        try {
            require_once $this->directory . '/vendor/autoload.php';

            $dump = new Ifsnop\Mysqldump\Mysqldump(sprintf('mysql:host=%s;dbname=%s', QA_MYSQL_HOSTNAME, QA_MYSQL_DATABASE), QA_MYSQL_USERNAME, QA_MYSQL_PASSWORD);
            $dump->setInfoHook(function ($object, $info) use (&$result) {
                if ($object === 'table') {
                    $tableName = $info['name'];
                    $result[$tableName] = $info['rowCount'];
                }
            });
            $dump->start();

            ksort($result);
        } catch (Exception $e) {
        }

        return $result;
    }

    private function getGeneralInfo(): array
    {
        $metadata = (new Q2A_Util_Metadata())->fetchFromAddonPath($this->directory);

        return [
            'Q2A_VERSION' => QA_VERSION,
            'pupi_coma_version' => $metadata['version'],
        ];
    }

    public function loadVersionConfiguration(string $q2aVersion)
    {
        $q2aVersion = self::SIMILAR_VERSIONS[$q2aVersion] ?? $q2aVersion;

        $versionToUse = $this->getConfigurationVersionToUse($q2aVersion);

        $allVersionsPath = $this->directory . 'src/' . PUPI_COMA_Constants::VERSIONS_DIRECTORY_PATH . '/';
        $versionPath = $allVersionsPath . $versionToUse;

        require_once $versionPath . '/PUPI_COMA_Options.php';
        require_once $versionPath . '/PUPI_COMA_QaConfig.php';
    }

    public function isVersionSupported(string $q2aVersion)
    {
        $q2aVersion = self::SIMILAR_VERSIONS[$q2aVersion] ?? $q2aVersion;

        $versionToUse = $this->getConfigurationVersionToUse($q2aVersion);

        return version_compare($q2aVersion, $versionToUse, '<=');
    }

    /**
     * @param string $q2aVersion
     *
     * @return string
     */
    private function getConfigurationVersionToUse(string $q2aVersion): string
    {
        $allVersionsPath = $this->directory . 'src/' . PUPI_COMA_Constants::VERSIONS_DIRECTORY_PATH . '/';

        $versions = [];
        foreach (glob($allVersionsPath . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $directory) {
            $versions[] = basename($directory);
        }

        usort($versions, function ($v1, $v2) {
            return version_compare($v1, $v2);
        });

        foreach ($versions as $version) {
            $versionToUse = $version;
            if (version_compare($q2aVersion, $version, '<=')) {
                break;
            }
        }

        return $versionToUse ?? reset($version);
    }
}
