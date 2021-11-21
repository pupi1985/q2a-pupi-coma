<?php

class PUPI_COMA_ConfigurationImport
{
    /**
     * @param array $config
     *
     * @throws Exception
     */
    public function import(array $config)
    {
        if (!isset($config[PUPI_COMA_ConfigurationManager::FIELD_OPTIONS])) {
            $nothingToImportErrorLang = qa_lang(PUPI_COMA_Constants::LANG_PREFIX . '/' . PUPI_COMA_Constants::LANG_ID_ADMIN_IMPORT_ERROR_NO_OPTIONS_TO_IMPORT);
            throw new Exception($nothingToImportErrorLang);
        }

        foreach ($config[PUPI_COMA_ConfigurationManager::FIELD_OPTIONS] as $option => $value) {
            if (isset(PUPI_COMA_Options::OPTIONS[$option][PUPI_COMA_Options::META_DO_NOT_IMPORT]) && PUPI_COMA_Options::OPTIONS[$option][PUPI_COMA_Options::META_DO_NOT_IMPORT]) {
                continue;
            }

            qa_opt($option, $value);
        }
    }
}
