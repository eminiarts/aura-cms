<?php

namespace Eminiarts\Aura\Operations;

class BaseOperation
{
    public function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Options',
                'slug' => 'options-tab',
                'global' => true,
            ],
            [
                'name' => 'Type',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'type',
                'options' => [
                    [
                        'value' => 'Send Email',
                        'key' => 'Eminiarts\\Aura\\Operations\\Mail',
                    ],
                    [
                        'value' => 'Send Notification',
                        'key' => 'Eminiarts\\Aura\\Operations\\Notification',
                    ],
                    [
                        'value' => 'Webhook',
                        'key' => 'Eminiarts\\Aura\\Operations\\Webhook',
                    ],
                    [
                        'value' => 'Read Post',
                        'key' => 'Eminiarts\\Aura\\Operations\\GetResource',
                    ],
                    [
                        'value' => 'Create Post',
                        'key' => 'Eminiarts\\Aura\\Operations\\CreateResource',
                    ],
                    [
                        'value' => 'Update Post',
                        'key' => 'Eminiarts\\Aura\\Operations\\UpdateResource',
                    ],
                    [
                        'value' => 'Delete Post',
                        'key' => 'Eminiarts\\Aura\\Operations\\DeleteResource',
                    ],
                    [
                        'value' => 'Trigger Flow',
                        'key' => 'Eminiarts\\Aura\\Operations\\TriggerFlow',
                    ],
                    [
                        'value' => 'Transform Payload',
                        'key' => 'transform-payload',
                    ],
                    [
                        'value' => 'Write to Log',
                        'key' => 'Eminiarts\\Aura\\Operations\\Log',
                    ],
                    [
                        'value' => 'Console Command',
                        'key' => 'console-command',
                    ],
                    [
                        'value' => 'Condition',
                        'key' => 'condition',
                    ],
                    [
                        'value' => 'Delay',
                        'key' => 'Eminiarts\\Aura\\Operations\\Delay',
                    ],
                ],
            ],
            [
                'name' => 'Options',
                'type' => 'Eminiarts\\Aura\\Fields\\Group',
                'validation' => '',
                'conditional_logic' => [
                ],
                'on_index' => false,
                'slug' => 'options',
            ],
            [
                'name' => 'X',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'x',
                'disabled' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Y',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'disabled' => true,
                'slug' => 'y',
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

    public function validateString($string)
    {
        $message = $string;

        // if safeString contains "{!!" or "@" or "!!}", throw an exception
        if (strpos($message, '{!!') !== false || strpos($message, '@') !== false || strpos($message, '!!}') !== false) {
            throw new \Exception('Message contains a blade tag 2');
        }

        if (strpos($message, '{{') == false && strpos($message, '}}') == false) {
            return $string;
        }

        $pattern = '/{{(?:\s*)?\$post(?:\s*)?->(?:(?!delete\(|get\(|create\(|update\()[a-z_\-0-9])*(?:\s*)?}}/';

        if (! preg_match($pattern, $string)) {
            throw new \Exception('Message contains a blade tag');
        }

        $safeString = trim(strip_tags($message));

        // if safeString contains a php tag, throw an exception
        if (strpos($safeString, '<?php') !== false) {
            throw new \Exception('Message contains php tag');
        }
        // if safeString contains a blade tag, throw an exception
        if (strpos($safeString, '@') !== false) {
            throw new \Exception('Message contains blade tag');
        }

        // if safeString contains "dd(" or "dump(" or "var_dump(" or "die(" or "exit(" or "exit;", throw an exception)
        if (strpos($safeString, 'dd(') !== false || strpos($safeString, 'dump(') !== false || strpos($safeString, 'var_dump(') !== false || strpos($safeString, 'die(') !== false || strpos($safeString, 'exit(') !== false || strpos($safeString, 'exit;') !== false) {
            throw new \Exception('Message contains a dump or die function');
        }

        // if safeString contains "eval(" or "assert(" or "base64_decode(" or "base64_encode(" or "gzinflate(" or "gzuncompress(" or "gzdecode(" or "str_rot13(" or "strrev(" or "str_shuffle(" or "str_split(" or "str_word_count(" or "strtr(" or "strnatcmp(" or "strnatcasecmp(" or "strncasecmp(" or "strncmp(" or "strpbrk(" or "strpos(" or "strrchr(" or "strrev(" or "strripos(" or "strrpos(" or "strspn(" or "strstr(" or "strtok(" or "strtolower(" or "strtoupper(" or "strtr(" or "substr_compare(" or "substr_count(" or "substr_replace(" or "substr(" or "trim(" or "ucfirst(" or "ucwords(" or "wordwrap(" or "addcslashes(" or "addslashes(" or "bin2hex(" or "chop(" or "chr(" or "chunk_split(" or "convert_cyr_string(" or "convert_uudecode(" or "convert_uuencode(" or "count_chars(" or "crc32(" or "crypt(" or "echo(" or "explode(" or "fprintf(" or "get_html_translation_table(" or "hebrev(" or "hebrevc(" or "hex2bin(" or "html_entity_decode(" or "htmlentities(" or "htmlspecialchars_decode(" or "htmlspecialchars(" or "implode(" or "join(" or "lcfirst(" or "levenshtein(" or "localeconv(" or "ltrim(" or "md5(" or "metaphone(" or "money_format(" or "nl_langinfo(" or "nl2br(" or "number_format(" or "ord(" or "parse_str(" or "print(" or "printf(" or "quoted_printable_decode(" or "quoted_printable_encode(" or "quotemeta(" or "rtrim(" or "setlocale(" or "sha1(" or "similar_text(" or "soundex(" or "sprintf(" or "sscanf(" or "str_getcsv(" or "str_ireplace(" or "str_pad(" or "str_repeat(" or "str_replace(" or "str_rot13(" or "str_shuffle(" or "str_split(", throw an exception)
        if (strpos($safeString, 'eval(') !== false || strpos($safeString, 'assert(') !== false || strpos($safeString, 'base64_decode(') !== false || strpos($safeString, 'base64_encode(') !== false || strpos($safeString, 'gzinflate(') !== false || strpos($safeString, 'gzuncompress(') !== false || strpos($safeString, 'gzdecode(') !== false || strpos($safeString, 'str_rot13(') !== false || strpos($safeString, 'strrev(') !== false || strpos($safeString, 'str_shuffle(') !== false || strpos($safeString, 'str_split(') !== false || strpos($safeString, 'str_word_count(') !== false || strpos($safeString, 'strtr(') !== false || strpos($safeString, 'strnatcmp(') !== false || strpos($safeString, 'strnatcasecmp(') !== false || strpos($safeString, 'strncasecmp(') !== false || strpos($safeString, 'strncmp(') !== false || strpos($safeString, 'strpbrk(') !== false || strpos($safeString, 'strpos(') !== false || strpos($safeString, 'strrchr(') !== false || strpos($safeString, 'strrev(') !== false || strpos($safeString, 'strripos(') !== false || strpos($safeString, 'strrpos(') !== false || strpos($safeString, 'strspn(') !== false || strpos($safeString, 'strstr(') !== false || strpos($safeString, 'strtok(') !== false || strpos($safeString, 'strtolower(') !== false || strpos($safeString, 'strtoupper(') !== false || strpos($safeString, 'strtr(') !== false || strpos($safeString, 'substr_compare(') !== false || strpos($safeString, 'substr_count(') !== false || strpos($safeString, 'substr_replace(') !== false || strpos($safeString, 'substr(') !== false || strpos($safeString, 'trim(') !== false || strpos($safeString, 'ucfirst(') !== false || strpos($safeString, 'ucwords(') !== false || strpos($safeString, 'wordwrap(') !== false || strpos($safeString, 'addcslashes(') !== false) {
            throw new \Exception('Message contains a string function');
        }

        // if safeString contains "array(" or "array_change_key_case(" or "array_chunk(" or "array_column(" or "array_combine(" or "array_count_values(" or "array_diff_assoc(" or "array_diff_key(" or "array_diff_uassoc(" or "array_diff_ukey(" or "array_diff(" or "array_fill_keys(" or "array_fill(" or "array_filter(" or "array_flip(" or "array_intersect_assoc(" or "array_intersect_key(" or "array_intersect_uassoc(" or "array_intersect_ukey(" or "array_intersect(" or "array_key_exists(" or "array_keys(" or "array_map(" or "array_merge_recursive(" or "array_merge(" or "array_multisort(" or "array_pad(" or "array_pop(" or "array_product(" or "array_push(" or "array_rand(" or "array_reduce(" or "array_replace_recursive(" or "array_replace(" or "array_reverse(" or "array_search(" or "array_shift(" or "array_slice(" or "array_splice(" or "array_sum(" or "array_udiff_assoc(" or "array_udiff_uassoc(" or "array_udiff(" or "array_uintersect_assoc(" or "array_uintersect_uassoc(" or "array_uintersect(" or "array_unique(" or "array_unshift(" or "array_values(" or "array_walk_recursive(" or "array_walk(" or "array(" or "arsort(" or "asort(" or "compact(" or "count(" or "current(" or "each(" or "end(" or "extract(" or "in_array(" or "key_exists(" or "key(" or "krsort(" or "ksort(" or "list(" or "natcasesort(" or "natsort(" or "next(" or "pos(" or "prev(" or "range(" or "reset(" or "rsort(" or "shuffle(" or "sizeof(" or "sort(" or "uasort(" or "uksort(" or "usort(" or "array_change_key_case(" or "array_chunk(" or "array_column(" or "array_combine(" or "array_count_values(" or "array_diff_assoc(" or "array_diff_key(" or "array_diff_uassoc(" or "array_diff_ukey(" or "array_diff(" or "array_fill_keys(" or "array_fill(" or "array_filter(", throw an exception
        if (strpos($safeString, 'array(') !== false || strpos($safeString, 'array_change_key_case(') !== false || strpos($safeString, 'array_chunk(') !== false || strpos($safeString, 'array_column(') !== false || strpos($safeString, 'array_combine(') !== false || strpos($safeString, 'array_count_values(') !== false || strpos($safeString, 'array_diff_assoc(') !== false || strpos($safeString, 'array_diff_key(') !== false || strpos($safeString, 'array_diff_uassoc(') !== false || strpos($safeString, 'array_diff_ukey(') !== false || strpos($safeString, 'array_diff(') !== false || strpos($safeString, 'array_fill_keys(') !== false || strpos($safeString, 'array_fill(') !== false || strpos($safeString, 'array_filter(') !== false || strpos($safeString, 'array_flip(') !== false || strpos($safeString, 'array_intersect_assoc(') !== false || strpos($safeString, 'array_intersect_key(') !== false || strpos($safeString, 'array_intersect_uassoc(') !== false || strpos($safeString, 'array_intersect_ukey(') !== false || strpos($safeString, 'array_intersect(') !== false || strpos($safeString, 'array_key_exists(') !== false || strpos($safeString, 'array_keys(') !== false || strpos($safeString, 'array_map(') !== false || strpos($safeString, 'array_merge_recursive(') !== false || strpos($safeString, 'array_merge(') !== false || strpos($safeString, 'array_multisort(') !== false || strpos($safeString, 'array_pad(') !== false || strpos($safeString, 'array_pop(') !== false || strpos($safeString, 'array_product(') !== false || strpos($safeString, 'array_push(') !== false || strpos($safeString, 'array_rand(') !== false || strpos($safeString, 'array_reduce(') !== false || strpos($safeString, 'array_replace_recursive(') !== false || strpos($safeString, 'array_replace(') !== false || strpos($safeString, 'array_reverse(') !== false || strpos($safeString, 'array_search(') !== false || strpos($safeString, 'array_shift(') !== false) {
            throw new \Exception('Message contains an array function');
        }

        // if safeString contains SQL keywords, throw an exception
        if (strpos($safeString, 'SELECT') !== false || strpos($safeString, 'UPDATE') !== false || strpos($safeString, 'DELETE') !== false || strpos($safeString, 'INSERT') !== false || strpos($safeString, 'CREATE') !== false || strpos($safeString, 'DROP') !== false || strpos($safeString, 'ALTER') !== false || strpos($safeString, 'TRUNCATE') !== false || strpos($safeString, 'RENAME') !== false || strpos($safeString, 'GRANT') !== false || strpos($safeString, 'REVOKE') !== false || strpos($safeString, 'LOCK') !== false || strpos($safeString, 'UNLOCK') !== false || strpos($safeString, 'INDEX') !== false || strpos($safeString, 'VIEW') !== false || strpos($safeString, 'TABLE') !== false || strpos($safeString, 'DATABASE') !== false || strpos($safeString, 'TRIGGER') !== false || strpos($safeString, 'PROCEDURE') !== false || strpos($safeString, 'FUNCTION') !== false || strpos($safeString, 'EVENT') !== false || strpos($safeString, 'USER') !== false || strpos($safeString, 'PASSWORD') !== false || strpos($safeString, 'ROLE') !== false || strpos($safeString, 'SCHEMA') !== false || strpos($safeString, 'SESSION') !== false || strpos($safeString, 'TRANSACTION') !== false || strpos($safeString, 'COMMIT') !== false || strpos($safeString, 'ROLLBACK') !== false || strpos($safeString, 'SAVEPOINT') !== false || strpos($safeString, 'EXPLAIN') !== false || strpos($safeString, 'DESCRIBE') !== false || strpos($safeString, 'SHOW') !== false || strpos($safeString, 'DESC') !== false || strpos($safeString, 'EXPLAIN') !== false || strpos($safeString, 'DESCRIBE') !== false || strpos($safeString, 'SHOW') !== false || strpos($safeString, 'DESC') !== false || strpos($safeString, 'EXPLAIN') !== false || strpos($safeString, 'DESCRIBE') !== false || strpos($safeString, 'SHOW') !== false || strpos($safeString, 'DESC') !== false || strpos($safeString, 'EXPLAIN') !== false) {
            throw new \Exception('Message contains SQL keywords');
        }

        // if safeString contains delete or truncate or drop, or any other dangerous keywords, throw an exception
        if (strpos($safeString, 'delete') !== false || strpos($safeString, 'truncate') !== false || strpos($safeString, 'drop') !== false || strpos($safeString, 'alter') !== false || strpos($safeString, 'create') !== false || strpos($safeString, 'update') !== false || strpos($safeString, 'insert') !== false || strpos($safeString, 'select') !== false || strpos($safeString, 'replace') !== false || strpos($safeString, 'grant') !== false || strpos($safeString, 'revoke') !== false || strpos($safeString, 'lock') !== false || strpos($safeString, 'unlock') !== false || strpos($safeString, 'index') !== false || strpos($safeString, 'view') !== false || strpos($safeString, 'table') !== false || strpos($safeString, 'database') !== false || strpos($safeString, 'trigger') !== false || strpos($safeString, 'procedure') !== false || strpos($safeString, 'function') !== false || strpos($safeString, 'event') !== false || strpos($safeString, 'user') !== false || strpos($safeString, 'password') !== false || strpos($safeString, 'role') !== false || strpos($safeString, 'schema') !== false || strpos($safeString, 'session') !== false || strpos($safeString, 'transaction') !== false || strpos($safeString, 'commit') !== false || strpos($safeString, 'rollback') !== false || strpos($safeString, 'savepoint') !== false || strpos($safeString, 'explain') !== false || strpos($safeString, 'describe') !== false || strpos($safeString, 'show') !== false || strpos($safeString, 'desc') !== false || strpos($safeString, 'explain') !== false || strpos($safeString, 'describe') !== false || strpos($safeString, 'show') !== false || strpos($safeString, 'desc') !== false || strpos($safeString, 'explain') !== false || strpos($safeString, 'describe') !== false || strpos($safeString, 'show') !== false || strpos($safeString, 'desc') !== false || strpos($safeString, 'explain') !== false) {
            throw new \Exception('Message contains dangerous keywords');
        }

        return $safeString;
    }
}
