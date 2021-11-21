<?php

/*
    This file is part of PUPI - Configuration Manager, a Question2Answer plugin
    that helps manage Q2A's configuration.

    Copyright (C) 2021 Gabriel Zanetti <http://github.com/pupi1985>

    PUPI - Configuration Manager is free software: you can redistribute it
    and/or modify it under the terms of the GNU General Public License as
    published by the Free Software Foundation, either version 3 of the License,
    or (at your option) any later version.

    PUPI - Configuration Manager is distributed in the hope that it will be
    useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
    Public License for more details.

    You should have received a copy of the GNU General Public License along
    with PUPI - Configuration Manager. If not, see
    <http://www.gnu.org/licenses/>.
*/

if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('process', 'PUPI_COMA_Admin.php', 'PUPI_COMA_Admin', 'PUPI_COMA Admin');

qa_register_plugin_module('page', 'PUPI_COMA_ConfigurationExportPage.php', 'PUPI_COMA_ConfigurationExportPage', 'PUPI_COMA Configuration Export Page');

qa_register_plugin_phrases('lang/pupi_coma_*.php', 'pupi_coma');
