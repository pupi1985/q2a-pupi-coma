<?php

class PUPI_COMA_QaConfig
{
    const META_MYSQL_CONNECTION = 1;
    const META_SITE_URL = 2;
    const META_PATH = 3;

    const GLOBAL_CONSTANTS = [
        'QA_FINAL_MYSQL_HOSTNAME' => [
            self::META_MYSQL_CONNECTION => true,
        ],
        'QA_FINAL_MYSQL_PORT' => [
            self::META_MYSQL_CONNECTION => true,
        ],
        'QA_FINAL_MYSQL_USERNAME' => [
            self::META_MYSQL_CONNECTION => true,
        ],
        'QA_FINAL_MYSQL_PASSWORD' => [
            self::META_MYSQL_CONNECTION => true,
        ],
        'QA_FINAL_MYSQL_DATABASE' => [
            self::META_MYSQL_CONNECTION => true,
        ],
        'QA_MYSQL_TABLE_PREFIX' => [],
        'QA_BLOBS_DIRECTORY' => [
            self::META_PATH => true,
        ],
        'QA_CACHE_DIRECTORY' => [
            self::META_PATH => true,
        ],
        'QA_COOKIE_DOMAIN' => [
            self::META_SITE_URL => true,
        ],
        'QA_FINAL_EXTERNAL_USERS' => [],
        'QA_WORDPRESS_INTEGRATE_PATH' => [
            self::META_PATH => true,
        ],
        'QA_JOOMLA_INTEGRATE_PATH' => [
            self::META_PATH => true,
        ],
        'QA_HTML_COMPRESSION' => [],
        'QA_MAX_LIMIT_START' => [],
        'QA_IGNORED_WORDS_FREQ' => [],
        'QA_ALLOW_UNINDEXED_QUERIES' => [],
        'QA_OPTIMIZE_DISTANT_DB' => [],
        'QA_PERSISTENT_CONN_DB' => [],
        'QA_DEBUG_PERFORMANCE' => [],
    ];

    public static function getAllMetas(): array
    {
        return [
            self::META_MYSQL_CONNECTION,
            self::META_SITE_URL,
            self::META_PATH,
        ];
    }
}
