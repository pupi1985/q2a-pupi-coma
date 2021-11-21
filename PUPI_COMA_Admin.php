<?php

require_once 'src/PUPI_COMA_Constants.php';

class PUPI_COMA_Admin
{
    const EXPORT_BUTTON = 'pupi_coma_export_button';
    const IMPORT_BUTTON = 'pupi_coma_import_button';

    const IMPORT_FILE_INPUT = 'pupi_coma_import_file_input';

    const EXPORT_TITLE = 'pupi_coma_export_title';
    const IMPORT_TITLE = 'pupi_coma_import_title';

    /** @var array */
    private $errors = [];

    /** @var string */
    private $directory;

    /** @var string */
    private $urlToRoot;

    /** @var PUPI_COMA_ConfigurationManager */
    private $configurationManager;

    /**
     * @param string $directory
     * @param string $urlToRoot
     */
    public function load_module(string $directory, string $urlToRoot)
    {
        $this->directory = $directory;
        $this->urlToRoot = $urlToRoot;
    }

    public function option_default($option)
    {
        $option = PUPI_COMA_Constants::SETTING_PREFIX . $option;

        switch ($option) {
            case PUPI_COMA_Constants::SETTING_FILTER_OPTIONS:
            case PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SITE_URL:
            case PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SECRET:
            case PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_EMAIL:
            case PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_ACCOUNT:
            case PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_PLUGIN:
            case PUPI_COMA_Constants::SETTING_FILTER_SERVER_INFO:
            case PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG:
            case PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_MYSQL_CONN:
            case PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_SITE_URL:
            case PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_PATH:
            case PUPI_COMA_Constants::SETTING_FILTER_TABLE_ROW_COUNTS:
                return false;
        }

        return null;
    }

    public function admin_form(&$qa_content): array
    {
        require_once 'src/PUPI_COMA_ConfigurationManager.php';
        $this->configurationManager = new PUPI_COMA_ConfigurationManager($this->directory);
        $this->configurationManager->loadVersionConfiguration(QA_VERSION);

        require_once 'src/PUPI_COMA_ConfigurationImport.php';

        $result = null;
        $exportButtonClicked = qa_clicked(self::EXPORT_BUTTON);
        $importButtonClicked = qa_clicked(self::IMPORT_BUTTON);

        if ($exportButtonClicked || $importButtonClicked) {
            $this->saveAllSettings();

            if ($exportButtonClicked) {
                qa_redirect(PUPI_COMA_Constants::URL_CONFIGURATION_EXPORT_PAGE);
            } else {
                $this->importFile();
            }

            if (empty($this->errors)) {
                $result = qa_lang_html('admin/options_saved');
            }
        }

        qa_set_display_rules($qa_content, [
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SITE_URL => PUPI_COMA_Constants::SETTING_FILTER_OPTIONS,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SECRET => PUPI_COMA_Constants::SETTING_FILTER_OPTIONS,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_EMAIL => PUPI_COMA_Constants::SETTING_FILTER_OPTIONS,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_ACCOUNT => PUPI_COMA_Constants::SETTING_FILTER_OPTIONS,
            PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_PLUGIN => PUPI_COMA_Constants::SETTING_FILTER_OPTIONS,

            PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_MYSQL_CONN => PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG,
            PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_SITE_URL => PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG,
            PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_PATH => PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG,
        ]);

        $this->addFrontendFiles($qa_content);

        return [
            'tags' => sprintf('method="post" action="%s" enctype="multipart/form-data"', qa_admin_plugin_options_path(basename($this->directory))),
            'ok' => $result,
            'style' => 'tall',
            'fields' => $this->getFields(),
            'buttons' => $this->getButtons(),
        ];
    }

    private function getButtons(): array
    {
        return [
            'export' => [
                'tags' => sprintf('name="%s"', self::EXPORT_BUTTON),
                'label' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_EXPORT_BUTTON_LABEL),
            ],
            'import' => [
                'tags' => sprintf('name="%s"', self::IMPORT_BUTTON),
                'label' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_IMPORT_BUTTON_LABEL),
            ],
        ];
    }

    // Fields

    private function getFields(): array
    {
        $fields = [];
        if (!$this->configurationManager->isVersionSupported(QA_VERSION)) {
            $fields[] = $this->getUnsupportedVersionWarningField();
        }

        return array_merge($fields, [
            $this->getExportTitleField(),
            $this->getFilterOptionsField(),
            $this->getFilterOptionsSiteUrlField(),
            $this->getFilterOptionsSecretField(),
            $this->getFilterOptionsEmailField(),
            $this->getFilterOptionsAccountField(),
            $this->getFilterOptionsPluginField(),
            $this->getSeparator(),
            $this->getFilterServerInfoField(),
            $this->getSeparator(),
            $this->getFilterQaConfigField(),
            $this->getFilterQaConfigMySqlConnField(),
            $this->getFilterQaConfigSiteUrlField(),
            $this->getFilterQaConfigPathField(),
            $this->getSeparator(),
            $this->getFilterTableRowCountsField(),
            $this->getSeparator(),
            $this->getImportTitleField(),
            $this->getImportFileField(),
        ]);
    }

    private function getSeparator(): array
    {
        return [
            'type' => 'blank',
        ];
    }

    /**
     * @param string $setting
     * @param string $langId
     *
     * @return array
     */
    private function getGenericBooleanField(string $setting, string $langId): array
    {
        return [
            'type' => 'checkbox',
            'label' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . $langId),
            'tags' => sprintf('name="%s"', qa_html($setting)),
            'value' => (bool)qa_opt($setting),
        ];
    }

    private function getUnsupportedVersionWarningField(): array
    {
        return [
            'type' => 'static',
            'error' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_UNSUPPORTED_VERSION_WARNING),
        ];
    }

    private function getExportTitleField(): array
    {
        return [
            'type' => 'static',
            'label' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_EXPORT_TITLE),
            'id' => self::EXPORT_TITLE,
            'note' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_EXPORT_TITLE_NOTE),
        ];
    }

    /**
     * @return array
     */
    private function getImportTitleField(): array
    {
        return [
            'type' => 'static',
            'label' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_IMPORT_TITLE),
            'id' => self::IMPORT_TITLE,
            'note' => strtr(
                qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_IMPORT_TITLE_NOTE),
                [
                    '^1' => '<span class="pupi_coma_code">options</span>',
                    '^2' => '<span class="pupi_coma_code">' . qa_db_apply_sub('^options', []) . '</span>',
                ]
            ),
        ];
    }

    private function getFilterOptionsField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_OPTIONS_LABEL);
        $field['tags'] .= sprintf(' id="%s"', PUPI_COMA_Constants::SETTING_FILTER_OPTIONS);

        return $field;
    }

    private function getFilterOptionsSiteUrlField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SITE_URL, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_OPTIONS_SITE_URL_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SITE_URL;

        return $field;
    }

    private function getFilterOptionsSecretField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SECRET, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_OPTIONS_SECRET_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SECRET;

        return $field;
    }

    private function getFilterOptionsEmailField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_EMAIL, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_OPTIONS_EMAIL_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_EMAIL;

        return $field;
    }

    private function getFilterOptionsAccountField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_ACCOUNT, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_OPTIONS_ACCOUNT_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_ACCOUNT;

        return $field;
    }

    private function getFilterOptionsPluginField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_PLUGIN, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_OPTIONS_PLUGIN_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_PLUGIN;

        return $field;
    }

    private function getFilterServerInfoField(): array
    {
        return $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_SERVER_INFO, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_SERVER_INFO_LABEL);
    }

    private function getFilterQaConfigField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_QA_CONFIG_LABEL);
        $field['tags'] .= sprintf(' id="%s"', PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG);

        return $field;
    }

    private function getFilterQaConfigMySqlConnField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_MYSQL_CONN, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_QA_CONFIG_MYSQL_CONN_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_MYSQL_CONN;

        return $field;
    }

    private function getFilterQaConfigSiteUrlField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_SITE_URL, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_QA_CONFIG_SITE_URL_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_SITE_URL;

        return $field;
    }

    private function getFilterQaConfigPathField(): array
    {
        $field = $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_PATH, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_QA_CONFIG_PATH_LABEL);
        $field['id'] = PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_PATH;

        return $field;
    }

    private function getFilterTableRowCountsField(): array
    {
        return $this->getGenericBooleanField(PUPI_COMA_Constants::SETTING_FILTER_TABLE_ROW_COUNTS, PUPI_COMA_Constants::LANG_ID_ADMIN_FILTER_TABLE_ROW_COUNTS_LABEL);
    }

    private function getImportFileField(): array
    {
        return [
            'type' => 'file',
            'label' => qa_lang_html(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_IMPORT_FILE_LABEL),
            'tags' => sprintf('name="%s"', qa_html(self::IMPORT_FILE_INPUT)),
            'error' => $this->errors[self::IMPORT_FILE_INPUT] ?? null,
        ];
    }

    // Save methods

    private function saveAllSettings()
    {
        $this->saveFilterOptionsSetting();
        $this->saveFilterOptionsSiteUrlSetting();
        $this->saveFilterOptionsSecretSetting();
        $this->saveFilterOptionsEmailSetting();
        $this->saveFilterOptionsAccountSetting();
        $this->saveFilterOptionsPluginSetting();
        $this->saveFilterServerInfoSetting();
        $this->saveFilterQaConfigSetting();
        $this->saveFilterMySqlConnSetting();
        $this->saveFilterQaConfigSiteUrlSetting();
        $this->saveFilterQaConfigPathSetting();
        $this->saveFilterTableRowCountsSetting();
    }

    private function saveBooleanSetting($setting)
    {
        $value = (int)qa_post_text($setting);
        qa_opt($setting, $value);
    }

    private function saveFilterOptionsSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS);
    }

    private function saveFilterOptionsSiteUrlSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SITE_URL);
    }

    private function saveFilterOptionsSecretSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_SECRET);
    }

    private function saveFilterOptionsEmailSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_EMAIL);
    }

    private function saveFilterOptionsAccountSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_ACCOUNT);
    }

    private function saveFilterOptionsPluginSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_OPTIONS_PLUGIN);
    }

    private function saveFilterServerInfoSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_SERVER_INFO);
    }

    private function saveFilterQaConfigSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG);
    }

    private function saveFilterMySqlConnSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_MYSQL_CONN);
    }

    private function saveFilterQaConfigSiteUrlSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_SITE_URL);
    }

    private function saveFilterQaConfigPathSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_QA_CONFIG_PATH);
    }

    private function saveFilterTableRowCountsSetting()
    {
        $this->saveBooleanSetting(PUPI_COMA_Constants::SETTING_FILTER_TABLE_ROW_COUNTS);
    }

    private function importFile()
    {
        $fieldErrorLang = qa_lang(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_IMPORT_ERROR_INVALID_FILE);
        try {
            if (!isset($_FILES[self::IMPORT_FILE_INPUT]) || !is_array($_FILES[self::IMPORT_FILE_INPUT])) {
                throw new Exception($fieldErrorLang);
            }

            $fileInput = $_FILES[self::IMPORT_FILE_INPUT];
            $fileUploadError = $fileInput['error'];

            if ($fileUploadError === 1) {
                throw new Exception(qa_lang('main/file_upload_limit_exceeded'));
            }
            if ($fileUploadError !== 0 || $fileInput['size'] === 0) {
                throw new Exception($fieldErrorLang);
            }

            $fileContents = file_get_contents($fileInput['tmp_name']);

            $config = json_decode($fileContents, true);
            if (is_null($config)) {
                throw new Exception($fieldErrorLang);
            }

            (new PUPI_COMA_ConfigurationImport())->import($config);
        } catch (Exception $e) {
            $this->errors[self::IMPORT_FILE_INPUT] = $fieldErrorLang;
        }
    }

    private function addFrontendFiles(&$qa_content)
    {
        if (!isset($qa_content['css_src'])) {
            $qa_content['css_src'] = [];
        }

        $styleSheetUrl = $this->urlToRoot . 'public/admin.css';

        $qa_content['css_src'][] = qa_html($styleSheetUrl);
    }
}
