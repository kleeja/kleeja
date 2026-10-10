<?php

/**
 *
 * @package Kleeja
 * @copyright (c) 2007 Kleeja.net
 * @license ./docs/license.txt
 *
 */

//no direct access
if (!defined('IN_COMMON')) {
    exit();
}

/**
 * Finds the templates, compiles them, caches them and renders them
 *
 * KleejaTemplateCompiler compiles a template to cache/tpl_<name>.php, and that file is used
 * until it is deleted, or compiled again on every request when $caching is off.
 * The templates read the global variables, which display() copies to $this->vars.
 */
class kleeja_style
{
    /** the first line of the compiled files, so they can not be opened by themselves */
    protected const COMPILED_FILE_GUARD = "<?php if (!defined('IN_COMMON')) { exit(); } ?>";

    /** @var array the variables of the templates, a copy of $GLOBALS */
    protected array $vars = [];

    /** @var bool reuse the compiled templates, DEV_STAGE and STOP_TPL_CACHE turn it off */
    public bool $caching = true;

    /**
     * Render a template
     * @param  string $template_name
     * @param  string $style_path    optional, good for plugins
     * @return string the page content
     */
    public function display(string $template_name, string $style_path = ''): string
    {
        $this->vars = $GLOBALS;

        $compiled_file = PATH . 'cache/tpl_' . $this->re_name_tpl($template_name, $style_path) . '.php';

        if (!$this->caching || !file_exists($compiled_file)) {
            $code = $this->_load_template($template_name, $style_path);

            //the cache folder is not writable, run it from memory
            if (!$this->save_compiled_template($compiled_file, $code)) {
                return $this->run_code($code);
            }
        }

        return $this->run_file($compiled_file);
    }

    /**
     * Render the field of a setting, the option column of the config table holds template code
     * @param  string $html
     * @return string
     */
    public function admindisplayoption(string $html): string
    {
        $this->vars = $GLOBALS;

        return $this->run_code(trim($this->_parse(stripcslashes($html))));
    }

    /**
     * Assign Variables
     * @param string $var
     * @param mixed  $to
     */
    public function assign(string $var, mixed $to): void
    {
        $GLOBALS[$var] = $to;
    }

    /**
     * check if a template exists or not
     * @param  string       $template_name
     * @param  string       $style_path
     * @return string|false the template path, or false when it does not exist
     */
    public function template_exists(string $template_name, string $style_path = ''): string|false
    {
        global $config, $STYLE_PATH_ADMIN_ABS, $THIS_STYLE_PATH_ABS, $DEFAULT_PATH_ADMIN_ABS;

        //admin template always begin with admin_
        $is_admin_template = str_starts_with($template_name, 'admin_');

        $style_path = str_replace(DIRECTORY_SEPARATOR, '/', $style_path);

        if (empty($style_path)) {
            $style_path = $is_admin_template ? $STYLE_PATH_ADMIN_ABS : $THIS_STYLE_PATH_ABS;
        }

        $template_path = rtrim($style_path, '/') . '/' . $template_name . '.html';
        $candidates = [$template_path];

        //a missing template is taken from the style this one depends on, or else from bootstrap, the default style
        if (trim($config['style_depend_on']) != '') {
            $candidates[] = str_replace(
                '/' . $config['style'] . '/',
                '/' . $config['style_depend_on'] . '/',
                $template_path,
            );
        } elseif ($is_admin_template) {
            $candidates[] = $DEFAULT_PATH_ADMIN_ABS . $template_name . '.html';
        } elseif ($config['style'] !== 'bootstrap') {
            $candidates[] = str_replace('/' . $config['style'] . '/', '/bootstrap/', $template_path);
        }

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return false;
    }

    /**
     * Load a template and compile it
     * @param  string $template_name
     * @param  string $style_path
     * @return string the compiled code
     */
    protected function _load_template(string $template_name, string $style_path = ''): string
    {
        if (!($template_path = $this->template_exists($template_name, $style_path))) {
            big_error(
                'No Template !',
                'Requested <b>"' .
                    htmlspecialchars($template_name, ENT_QUOTES, 'UTF-8') .
                    '"</b> template doesnt exist!',
            );
        }

        $html = $this->_parse((string) file_get_contents($template_path), $template_name);

        extract(runHook('style_load_template_func', get_defined_vars()));

        return $html;
    }

    /**
     * Compile the template syntax to PHP
     * @param  string $html
     * @param  string $template_name empty for the fields of the settings
     * @return string
     */
    protected function _parse(string $html, string $template_name = ''): string
    {
        extract(runHook('style_parse_func', get_defined_vars()));

        try {
            $html = (new KleejaTemplateCompiler())->compile($html, $template_name);
        } catch (KleejaTemplateException $e) {
            big_error('Template error', htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }

        //plugins add their own tags here, as regex => replacement pairs that run on the compiled code
        $rep = [];

        extract(runHook('style_parse_func_step_2', get_defined_vars()));

        if (!empty($rep)) {
            $html = preg_replace(array_keys($rep), array_values($rep), $html);
        }

        //a plugin tag that writes broken PHP is reported here, with the name of the template
        if (function_exists('token_get_all')) {
            try {
                token_get_all($html, TOKEN_PARSE);
            } catch (ParseError $e) {
                big_error(
                    'Template error',
                    htmlspecialchars(
                        ($template_name !== '' ? 'Template "' . $template_name . '"' : 'A setting field') .
                            ' compiles to code that is not valid PHP, a plugin tag may have broken it: ' .
                            $e->getMessage(),
                        ENT_QUOTES,
                        'UTF-8',
                    ),
                );
            }
        }

        return $html;
    }

    /**
     * Save a compiled template through a temporary file, so no request includes half of it
     * @param  string $file
     * @param  string $code
     * @return bool
     */
    protected function save_compiled_template(string $file, string $code): bool
    {
        $code = self::COMPILED_FILE_GUARD . $this->after_closing_tag($code);
        $temp_file = $file . '.' . uniqid('', true) . '.tmp';

        if (@file_put_contents($temp_file, $code) !== strlen($code)) {
            @unlink($temp_file);

            return false;
        }

        // Read and write for owner, read for everybody else
        @chmod($temp_file, 0644);

        if (!@rename($temp_file, $file)) {
            @unlink($temp_file);

            return false;
        }

        //opcache would keep running the old version of the file
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }

        return true;
    }

    /**
     * Run a compiled template file
     * @param  string $file
     * @return string what it prints
     */
    protected function run_file(string $file): string
    {
        ob_start();

        try {
            include $file;
        } finally {
            $page = ob_get_clean();
        }

        return $page;
    }

    /**
     * Run compiled template code
     * @param  string $code
     * @return string what it prints
     */
    protected function run_code(string $code): string
    {
        ob_start();

        try {
            eval('?>' . $this->after_closing_tag($code));
        } finally {
            $page = ob_get_clean();
        }

        return $page;
    }

    /**
     * PHP eats the new line that comes right after a closing tag, give it one more
     * so that a template that begins with a new line keeps it
     * @param  string $code
     * @return string
     */
    protected function after_closing_tag(string $code): string
    {
        return (in_array($code[0] ?? '', ["\r", "\n"], true) ? "\n" : '') . $code;
    }

    /**
     * change name of template to be valid
     * @param  string      $name
     * @param  null|string $style_path
     * @return string
     */
    protected function re_name_tpl(string $name, ?string $style_path = null): string
    {
        return preg_replace('/[^a-z0-9_-]/', '-', strtolower($name)) . (!empty($style_path) ? md5($style_path) : '');
    }
}

/**
 * Compiles the template syntax of Kleeja to PHP
 *
 *     {name}, {name.key}                print a variable, a missing {lang.KEY} prints itself
 *     {{name}}                          print a column of the current LOOP row
 *     {name|e}                          escape it for HTML, or |url, |js (as a JSON value), |int
 *     {%key%}, {%value%}                the key and the value of the current LOOP row
 *     <IF NAME="name == value">         also <ELSEIF> and <UNLESS>, see condition()
 *     <ELSE>, </IF>                     </LOOP>, </UNLESS>, </IS_BROWSER> and </END> close a block too
 *     (name == value?yes:no)            a short IF
 *     <LOOP NAME="rows">
 *     <INCLUDE NAME="template">         with PATH="folder" for a template outside of the style
 *     <IS_BROWSER="ie,opera">           or <IS_BROWSER!="...">, see is_browser()
 *     <ODD="column">, <EVEN="column">   the rows where the column is odd or even, until </ODD>, </EVEN>
 *     <RAND="first","second">           print the first value, then the second one, by turns
 *     <IGNORE>...</IGNORE>              printed as it is
 *
 * The compiled code runs no PHP other than what this class writes: PHP tags in a template are
 * removed and a "<?" left over is printed as text, and the value of a tag is checked and written
 * as a PHP literal, so it never becomes code.
 */
class KleejaTemplateCompiler
{
    /** {name} or {{name}}, the last group of braces tells which one */
    protected const VARIABLE = '([{]{1,2})+([A-Z0-9_\.]+)[}]{1,2}';

    /** the operators of a comparison, in the order they are tried at the same place */
    protected const OPERATORS = [
        ' eq ' => '==',
        ' lt ' => '<',
        ' gt ' => '>',
        ' lte ' => '<=',
        ' gte ' => '>=',
        ' neq ' => '!=',
        '==' => '==',
        '!=' => '!=',
        '>=' => '>=',
        '<=' => '<=',
        '<' => '<',
        '>' => '>',
    ];

    /** a comparison is split at its last operator */
    protected const COMPARISON = '/(.*)( eq | lt | gt | lte | gte | neq |==|!=|>=|<=|<|>)(.*)/i';

    /** every tag of the syntax, each one is a named group */
    protected const TAGS =
        '/(?<condition><(?<condition_tag>IF|ELSEIF|UNLESS)\s(?<condition_attributes>(?:"[^"\n]*"|[^">\n])+)>)' .
        '|(?<else><ELSE\s*\/?>)' .
        '|(?<close><\/(?<close_tag>LOOP|IF|END|IS_BROWSER|UNLESS|ODD|EVEN)>)' .
        '|(?<loop><LOOP\s+NAME\s*=\s*"*(?<loop_name>[a-z0-9_.]+)"*\s*>)' .
        '|(?<include><INCLUDE(?:\s+NAME)?\s*=*\s*"(?<include_name>[^"\n]+)"(?:\s*PATH\s*=+\s*"(?<include_path>[^"\n]*)")?\s*>)' .
        '|(?<browser><IS_BROWSER\s*(?<browser_not>!?)=\s*"(?<browser_names>[a-z0-9,]+)"\s*>)' .
        '|(?<row><(?<row_tag>ODD|EVEN)\s*=\s*"(?<row_column>[a-z0-9_\-+.\/]+)"\s*>)' .
        '|(?<rand><RAND\s*=\s*"(?<rand_first>[^"]*)"\s*,\s*"(?<rand_second>[^"]*)"\s*>)' .
        '|(?<loop_item>\{%(?<loop_item_name>key|value)%\})' .
        '|(?<variable>(?<variable_braces>[{]{1,2})+(?<variable_name>[A-Z0-9_.]+)(?:\|(?<variable_filter>e|url|js|int))?[}]{1,2})/i';

    /** @var string the template being compiled, for the error messages */
    protected string $template_name = '';

    /** @var int the line being compiled, for the error messages */
    protected int $line = 1;

    /** @var array the open blocks, the innermost one last */
    protected array $blocks = [];

    /** @var array the compiled pieces in order, [true, PHP code] or [false, text] */
    protected array $parts = [];

    /**
     * Compile a template
     * @param  string                  $source
     * @param  string                  $template_name for the error messages, empty for a setting field
     * @throws KleejaTemplateException
     * @return string                  PHP code
     */
    public function compile(string $source, string $template_name = ''): string
    {
        $this->template_name = $template_name;
        $this->line = 1;
        $this->blocks = $this->parts = [];

        //split out the <IGNORE> blocks, every odd piece is one
        $pieces = preg_split('/<IGNORE>(.*?)<\/IGNORE>/is', $source, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($pieces === false) {
            throw $this->error('it can not be read, ' . preg_last_error_msg());
        }

        foreach ($pieces as $i => $piece) {
            $first_line = $this->line;

            if ($i % 2) {
                $this->text($this->strip_php($piece));
            } else {
                $this->compile_text($this->expand_short_ifs($this->strip_php($piece)));
            }

            $this->line = $first_line + substr_count($piece, "\n");
        }

        if (!empty($this->blocks)) {
            $block = end($this->blocks);
            $this->line = $block['line'];

            throw $this->error('<' . $block['tag'] . '> is not closed');
        }

        return $this->assemble();
    }

    /**
     * Remove the PHP code of a template
     * @param  string $text
     * @return string
     */
    protected function strip_php(string $text): string
    {
        return preg_replace(
            [
                '#<([\?%])=?.*?\1>#s',
                '#<script\s+language\s*=\s*(["\']?)php\1\s*>.*?</script\s*>#s',
                '#<\?php(?:\r\n?|[ \n\t]).*?\?>#s',
            ],
            '',
            $text,
        );
    }

    /**
     * (condition?yes:no) is a short IF, like (config.mod_writer?guide.html:go.php?go=guide)
     * @param  string $text
     * @return string
     */
    protected function expand_short_ifs(string $text): string
    {
        return preg_replace_callback(
            '/\(([{A-Z0-9_\.}\s!=<>]+)\?(.*):(.*)\)/iU',
            function (array $m): string {
                return '<IF NAME="' . $m[1] . '">' . $m[2] . '<ELSE>' . $m[3] . '</IF>';
            },
            $text,
        );
    }

    /**
     * Compile the tags of a piece of a template
     * @param string $text
     */
    protected function compile_text(string $text): void
    {
        if (
            preg_match_all(self::TAGS, $text, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL) ===
            false
        ) {
            throw $this->error('it can not be read, ' . preg_last_error_msg());
        }

        $position = 0;

        foreach ($tags as $tag) {
            [$tag_text, $offset] = $tag[0];

            $this->text(substr($text, $position, $offset - $position));
            $this->line += substr_count($text, "\n", $position, $offset - $position);
            $position = $offset + strlen($tag_text);

            $this->compile_tag(
                array_map(function (array $group): ?string {
                    return $group[0];
                }, $tag),
                $text,
                $position,
            );

            $this->line += substr_count($tag_text, "\n");
        }

        $this->text(substr($text, $position));
    }

    /**
     * Compile one tag
     * @param array  $tag  the groups of TAGS, null for the ones that did not match
     * @param string $text the piece of the template that has the tag
     * @param int    $end  where the tag ends in the piece
     */
    protected function compile_tag(array $tag, string $text, int $end): void
    {
        if ($tag['condition'] !== null) {
            $this->compile_condition_tag(strtoupper($tag['condition_tag']), $tag['condition_attributes']);
        } elseif ($tag['else'] !== null) {
            $this->continue_block('<ELSE>', true);
            $this->code('<?php } else { ?>');
        } elseif ($tag['close'] !== null) {
            $this->close_block(strtoupper($tag['close_tag']), $tag['close']);
        } elseif ($tag['loop'] !== null) {
            $this->compile_loop($tag['loop_name']);
        } elseif ($tag['include'] !== null) {
            $this->compile_include($tag['include_name'], $tag['include_path'] ?? '');
        } elseif ($tag['browser'] !== null) {
            $this->open_block(
                'IS_BROWSER',
                '<?php if (' . $tag['browser_not'] . 'is_browser(' . $this->export($tag['browser_names']) . ')) { ?>',
            );
        } elseif ($tag['row'] !== null) {
            $this->compile_row_block(strtoupper($tag['row_tag']), $tag['row_column'], $tag['row'], $text, $end);
        } elseif ($tag['rand'] !== null) {
            $this->code(
                '<?php $KLEEJA_tpl_rand_is = (!isset($KLEEJA_tpl_rand_is) || $KLEEJA_tpl_rand_is == 0) ? 1 : 0; ' .
                    'echo $KLEEJA_tpl_rand_is == 1 ? ' .
                    $this->export($tag['rand_first']) .
                    ' : ' .
                    $this->export($tag['rand_second']) .
                    '; ?>',
            );
        } elseif ($tag['loop_item'] !== null) {
            $this->code('<?php echo $' . strtolower($tag['loop_item_name']) . '; ?>');
        } else {
            $this->compile_variable(
                $tag['variable'],
                $tag['variable_braces'],
                $tag['variable_name'],
                $tag['variable_filter'],
            );
        }
    }

    /**
     * IF, ELSEIF and UNLESS
     * @param string $tag
     * @param string $attributes
     */
    protected function compile_condition_tag(string $tag, string $attributes): void
    {
        $condition = $this->condition($this->attributes($attributes));

        if ($tag === 'ELSEIF') {
            $this->continue_block('<ELSEIF>');
            $this->code('<?php } elseif (' . $condition . ') { ?>');
        } elseif ($tag === 'UNLESS') {
            $this->open_block('UNLESS', '<?php if (!(' . $condition . ')) { ?>');
        } else {
            $this->open_block('IF', '<?php if (' . $condition . ') { ?>');
        }
    }

    /**
     * The condition of an IF, ELSEIF or UNLESS tag, from its attributes
     *
     *     NAME="name"              the variable is true, also {name} and {{name}}
     *     NAME="name == value"     also != < > <= >= and the words eq neq lt gt lte gte; the value
     *                              is a number, a 'string', {name}, {{name}} or a word (a string)
     *     LOOP="..."               like NAME, the names are the columns of the current LOOP row
     *     ISSET="name", EMPTY="name"
     *     AND="...", OR="..."      one more comparison, joined with && and ||
     *
     * @param  array  $attributes
     * @return string
     */
    protected function condition(array $attributes): string
    {
        //with LOOP, every name of the tag is a column of the current row
        $in_loop = !empty($attributes['LOOP']);

        if (isset($attributes['NAME'], $attributes['LOOP'])) {
            throw $this->error('a condition has NAME or LOOP, not both');
        }

        $terms = [];

        foreach (['NAME', 'LOOP'] as $attribute) {
            if (isset($attributes[$attribute])) {
                $terms[] = $this->comparison($attributes[$attribute], $in_loop);
            }
        }

        foreach (['ISSET' => 'isset', 'EMPTY' => 'empty'] as $attribute => $function) {
            if (isset($attributes[$attribute])) {
                $terms[] = $this->check($function, $attributes[$attribute], $in_loop);
            }
        }

        if (empty($terms)) {
            throw $this->error('a condition needs a NAME, LOOP, ISSET or EMPTY attribute');
        }

        $condition = implode(' && ', $terms);

        if (isset($attributes['AND'])) {
            $condition .= ' && ' . $this->comparison($attributes['AND'], $in_loop);
        }

        if (isset($attributes['OR'])) {
            $condition .= ' || ' . $this->comparison($attributes['OR'], $in_loop);
        }

        return $condition;
    }

    /**
     * The attributes of a tag by their names in upper case, written name="value" or name='value'
     * @param  string $text
     * @return array
     */
    protected function attributes(string $text): array
    {
        preg_match_all('/([a-z]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $text, $matches, PREG_SET_ORDER);

        $attributes = [];

        foreach ($matches as $match) {
            $attributes[strtoupper($match[1])] = $match[3] ?? $match[2];
        }

        return $attributes;
    }

    /**
     * A variable, or a variable compared with a value
     * @param  string $condition
     * @param  bool   $in_loop
     * @return string
     */
    protected function comparison(string $condition, bool $in_loop): string
    {
        if (trim($condition) === '') {
            throw $this->error('a condition is empty');
        }

        if (!preg_match(self::COMPARISON, $condition, $match)) {
            return $this->variable_operand($condition, $in_loop) ?? $this->denied($condition);
        }

        $variable = $this->variable_operand($match[1], $in_loop);
        $value = $this->value_operand($match[3]);

        if ($variable === null || $value === null) {
            return $this->denied($condition);
        }

        return $variable . ' ' . self::OPERATORS[strtolower($match[2])] . ' ' . $value;
    }

    /**
     * ISSET and EMPTY
     * @param  string $function isset or empty
     * @param  string $operand
     * @param  bool   $in_loop
     * @return string
     */
    protected function check(string $function, string $operand, bool $in_loop): string
    {
        if (preg_match(self::COMPARISON, $operand)) {
            throw $this->error(strtoupper($function) . '="' . $operand . '" takes a variable name, not a comparison');
        }

        $variable = $this->variable_operand($operand, $in_loop);

        return $variable === null ? $this->denied($operand) : $function . '(' . $variable . ')';
    }

    /**
     * The variable of a condition: name, name.key, {name} or {{name}}
     * @param  string      $operand
     * @param  bool        $in_loop
     * @return string|null null for PHP code
     */
    protected function variable_operand(string $operand, bool $in_loop): ?string
    {
        $operand = trim($operand);

        if (preg_match('/^' . self::VARIABLE . '$/i', $operand, $match)) {
            return $this->variable($match[2], $match[1] === '{{');
        }

        if ($operand === '') {
            throw $this->error('a condition has no variable name');
        }

        if ($operand[0] === '$' || str_contains($operand, '(')) {
            return null;
        }

        //a name is read like {name}, or like {{name}} in a LOOP condition
        if (preg_match('/' . self::VARIABLE . '/i', $in_loop ? '{{' . $operand . '}}' : '{' . $operand . '}', $match)) {
            return $this->variable($match[2], $match[1] === '{{');
        }

        throw $this->error('"' . $operand . '" is not a variable name');
    }

    /**
     * The value a variable is compared with: a number, a 'string', {name}, {{name}} or a word, which is a string
     * @param  string      $operand
     * @return string|null null for PHP code
     */
    protected function value_operand(string $operand): ?string
    {
        $operand = trim($operand);

        if (preg_match('/^' . self::VARIABLE . '$/i', $operand, $match)) {
            return $this->variable($match[2], $match[1] === '{{');
        }

        if ($operand !== '' && $operand[0] === '$') {
            return null;
        }

        if (preg_match('/^[+-]?(?:\d+|\d*\.\d+)$/', $operand)) {
            return var_export($operand + 0, true);
        }

        if (strlen($operand) > 1 && $operand[0] === "'" && $operand[-1] === "'") {
            return $this->export(strtr(substr($operand, 1, -1), ['\\\\' => '\\', "\\'" => "'"]));
        }

        return $this->export($operand);
    }

    /**
     * Templates could run PHP code in a condition before, now the condition is false
     * @param  string $condition
     * @return string
     */
    protected function denied(string $condition): string
    {
        trigger_error(
            $this->where() .
                ': the condition "' .
                $condition .
                '" is PHP code, which templates can not run, it is false',
            E_USER_WARNING,
        );

        return 'false';
    }

    /**
     * PHP code that reads a variable: name.key is $this->vars['name']['key'], or $value['name']['key'] in a loop
     * @param  string $name
     * @param  bool   $in_loop
     * @return string
     */
    protected function variable(string $name, bool $in_loop): string
    {
        $code = $in_loop ? '$value' : '$this->vars';

        foreach (explode('.', $name) as $key) {
            $code .= '[' . $this->export($key) . ']';
        }

        return $code;
    }

    /**
     * {name}, {{name}} and {name|filter}
     * @param string      $tag
     * @param string      $braces
     * @param string      $name
     * @param string|null $filter
     */
    protected function compile_variable(string $tag, string $braces, string $name, ?string $filter): void
    {
        $code = $this->variable($name, $braces === '{{');

        //a missing language key prints its own tag, that shows which one is missing
        if (str_contains($tag, '{lang') || str_contains($tag, '{olang')) {
            $code .= ' ?? ' . $this->export($tag);
        }

        $code = match (strtolower((string) $filter)) {
            //the texts are saved encoded already, once or twice, so they are decoded then encoded once
            'e' => 'htmlspecialchars(kleeja_html_decode((string) (' . $code . ')), ENT_QUOTES, \'UTF-8\', false)',
            'url' => 'rawurlencode((string) (' . $code . '))',
            'js' => 'json_encode(' .
                $code .
                ', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)',
            'int' => '(int) (' . $code . ')',
            default => $code,
        };

        $this->code('<?=' . $code . '?>');
    }

    /**
     * LOOP
     * @param string $name
     */
    protected function compile_loop(string $name): void
    {
        //a LOOP inside another one gives the $key and $value of the outer row back when it ends
        $nested = in_array('LOOP', array_column($this->blocks, 'tag'), true);

        $this->open_block(
            'LOOP',
            '<?php ' .
                ($nested ? '$_loop_rows[] = [$key, $value]; ' : '') .
                'foreach (' .
                $this->variable($name, false) .
                ' ?? [] as $key => $value) { ?>',
            $nested,
        );
    }

    /**
     * INCLUDE
     * @param string $name
     * @param string $path
     */
    protected function compile_include(string $name, string $path): void
    {
        if (!preg_match('/^[a-z0-9_\-.\/]+$/i', $name) || str_contains($name, '..')) {
            throw $this->error('<INCLUDE NAME="' . $name . '"> is not a template name');
        }

        $this->code('<?php echo $this->display(' . $this->export($name) . ', ' . $this->export($path) . '); ?>');
    }

    /**
     * ODD and EVEN
     * @param string $tag
     * @param string $column
     * @param string $raw  the tag as it is written
     * @param string $text
     * @param int    $end
     */
    protected function compile_row_block(string $tag, string $column, string $raw, string $text, int $end): void
    {
        //without its closing tag it stays text, as it always did
        if (stripos($text, '</' . $tag . '>', $end) === false) {
            $this->text($raw);

            return;
        }

        $this->open_block(
            $tag,
            '<?php if (intval($value[' .
                $this->export($column) .
                ']) % 2' .
                ($tag === 'EVEN' ? ' == 0' : '') .
                ') { ?>',
        );
        $this->text(' ');
    }

    /**
     * Open a block
     * @param string $tag
     * @param string $code
     * @param bool   $nested_loop
     */
    protected function open_block(string $tag, string $code, bool $nested_loop = false): void
    {
        $this->blocks[] = ['tag' => $tag, 'line' => $this->line, 'else' => false, 'nested_loop' => $nested_loop];
        $this->code($code);
    }

    /**
     * ELSE and ELSEIF continue the innermost block, a condition that has no ELSE yet
     * @param string $tag
     * @param bool   $is_else
     */
    protected function continue_block(string $tag, bool $is_else = false): void
    {
        $index = array_key_last($this->blocks);

        if ($index === null) {
            throw $this->error($tag . ' is not inside an <IF>');
        }

        $block = $this->blocks[$index];

        if ($block['tag'] === 'LOOP') {
            throw $this->error($tag . ' is inside the <LOOP> opened at line ' . $block['line'] . ', close it first');
        }

        if ($block['else']) {
            throw $this->error($tag . ' comes after the <ELSE> of the block opened at line ' . $block['line']);
        }

        $this->blocks[$index]['else'] = $is_else;
    }

    /**
     * Close the innermost block, </IF>, </LOOP>, </UNLESS>, </IS_BROWSER> and </END> close any kind
     * @param string $tag
     * @param string $raw the tag as it is written
     */
    protected function close_block(string $tag, string $raw): void
    {
        $block = end($this->blocks);

        if ($tag === 'ODD' || $tag === 'EVEN') {
            //without its block it stays text, as it always did
            if (!in_array($tag, array_column($this->blocks, 'tag'), true)) {
                $this->text($raw);

                return;
            }

            if ($block['tag'] !== $tag) {
                throw $this->error(
                    $raw . ' comes before the end of the <' . $block['tag'] . '> opened at line ' . $block['line'],
                );
            }

            array_pop($this->blocks);
            $this->text(' ');
            $this->code('<?php } ?>');

            return;
        }

        if ($block === false) {
            throw $this->error($raw . ' has no block to close');
        }

        if ($block['tag'] === 'ODD' || $block['tag'] === 'EVEN') {
            throw $this->error(
                $raw .
                    ' can not close the <' .
                    $block['tag'] .
                    '> opened at line ' .
                    $block['line'] .
                    ', it is closed by </' .
                    $block['tag'] .
                    '>',
            );
        }

        array_pop($this->blocks);
        $this->code($block['nested_loop'] ? '<?php } [$key, $value] = array_pop($_loop_rows); ?>' : '<?php } ?>');
    }

    /**
     * Add text to print
     * @param string $text
     */
    protected function text(string $text): void
    {
        if ($text !== '') {
            $this->parts[] = [false, $text];
        }
    }

    /**
     * Add PHP code
     * @param string $code
     */
    protected function code(string $code): void
    {
        $this->parts[] = [true, $code];
    }

    /**
     * Join the compiled pieces, the text is joined first so that two pieces can't make a "<?" together
     * @return string
     */
    protected function assemble(): string
    {
        $compiled = $text = '';

        foreach ($this->parts as [$is_code, $part]) {
            if ($is_code) {
                $compiled .= $this->escape_text($text) . $part;
                $text = '';
            } else {
                $text .= $part;
            }
        }

        return $compiled . $this->escape_text($text);
    }

    /**
     * A "<?" in the text would open PHP, print it in two pieces
     * @param  string $text
     * @return string
     */
    protected function escape_text(string $text): string
    {
        return str_replace('<?', '<<?php ?>?', $text);
    }

    /**
     * A string as a PHP literal
     * @param  string $value
     * @return string
     */
    protected function export(string $value): string
    {
        return var_export($value, true);
    }

    /**
     * The template and the line being compiled
     * @return string
     */
    protected function where(): string
    {
        return ($this->template_name !== '' ? 'Template "' . $this->template_name . '"' : 'A setting field') .
            ', line ' .
            $this->line;
    }

    /**
     * An error at the line being compiled
     * @param  string                  $message
     * @return KleejaTemplateException
     */
    protected function error(string $message): KleejaTemplateException
    {
        return new KleejaTemplateException($this->where() . ': ' . $message);
    }
}

/**
 * A template that can not be compiled, the message tells where and why
 */
class KleejaTemplateException extends Exception {}
