<?php
/**
 * C Compatible PreProcessor for PHP
 *
 * LICENSE:
 *   Copyright (c) 2009, Marin Valeriev Ivanov
 *   All rights reserved.
 *
 *   Redistribution and use in source and binary forms, with or without
 *   modification, are permitted provided that the following conditions are met:
 *       * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *       * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *   THIS SOFTWARE IS PROVIDED BY Marin Valeriev Ivanov ''AS IS'' AND ANY
 *   EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 *   WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 *   DISCLAIMED. IN NO EVENT SHALL Marin Valeriev Ivanov BE LIABLE FOR ANY
 *   DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 *   (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *   LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 *   ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *   (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *   SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author     Marin Ivanov <metala@metala.org>
 * @copyright  2009 Marin Ivanov
 * @license    BSD License http://www.metala.org/licenses/2009/bsd-license.txt
 * @version    0.1 Beta 2
 *
 * References:
 *  #1 (ISO/IEC 9899:1990 TC2 WG14/N1124)  http://www.open-std.org/JTC1/SC22/WG14/www/standards
 *  #2 Argumented Backus-Naur http://www.ietf.org/rfc/rfc2234.txt
 */

/**
Not implemented:

#Pragma
function macros in function macros
single hash (quoting string)
compiled caching
#if defined()
 */

// Version scheme in Argumented Backus-Naur Form (ABNF)[2]
//
// major = minor = build = *DIGIT
// stage = (a | b | rc) *DIGIT
// version = major "." minor ["." build | stage]

define('CCPP_VERSION', '0.1b1');

class CCPP
{
	//Parser options
	public $options = array();

	//Macros
	public $macros = array(); //Object-like macros
	public $functionMacros = array(); //Function-like macros
	public $user_directives = array(); //User defined directives

	//Cache
	protected $_cache_phpSnippets = array();
	protected $_cache_regexps = array();
	protected $_cache_formats = array();

	//Constructor
	public function __construct()
	{
		$this->options += array(
			//Translation
			'translate.trigraphs' => true,
			'translate.lineSlicing' => true,
			'translate.comments' => true,

			//Phase 4 engine
			'translate.p4Engine' => 'compiler',
			'translate.p4tokenizer' => 'internal',
			//Phase 4
			'translate.directives' => true,
			'translate.compactWhitespaces' => false,
			'translate.macros' => true,
			'translate.functionMacros' => true,

			//Protector

			//Output
			'output.format' => 'evaluated',
		);
		$this->_prepareCache();
		$this->_predefineMacros();
	}

	protected function _prepareCache()
	{
		//PHP Snippets
		$sBOB = 'ob_start();';
		$sEOB = 'ob_end_flush();';

		$this->_cache_phpSnippets = array(
			'BOB' => &$sBOB,
			'EOB' => &$sEOB,
			'CFH' => "<?php\n".$sBOB."\n?>\n", //Compiled File Header
			'CFF' => "\n<?php\n".$sEOB."\n?>", //Compiled File Footer
			'POT' => "<?php ",
			'PCT' => " ?>",
		);

		//Regexps
		$this->_cache_regexps = array(
			'defineMacroOperands' => '~^([a-z_][a-z0-9_]+(\\([a-z0-9,_ \'"]*\\))?)(\s+([^\r\n]+))?~i',
			'version' => '~^([0-9]{1,2})\.([0-9]{1,2})((\.([0-9]{1,2}))|((pa|a|b|rc)([0-9]?)))?~',
		);

		//Formats
		$this->_cache_formats = array(
			'directive' => "#%s %[^\n]",
		);

	}

	protected function _predefineMacros()
	{
		//Version
		preg_match_all($this->_cache_regexps['version'], CCPP_VERSION, $matches);
		$ccppVersion = array(
			'major' => $matches[1][0],
			'minor' => $matches[2][0],
			'build' => $matches[5][0],
			'stageFull' => $matches[6][0],
			'stageName' => $matches[7][0],
			'stageVersion' => $matches[8][0],
		);

		//Macros
		$this->macros += array(
			'_CCPP' => 1,
			'_CCPP_MAJOR' => $ccppVersion['major'],
			'_CCPP_MINOR' => $ccppVersion['minor'],
			'_CCPP_VERSION' => $ccppVersion['major'].'.'.$ccppVersion['minor'],
			'_CCPP_VERSION_FULL' => CCPP_VERSION,
			'_CCPP_PHP' => 1,
			'_CCPP_PHP_VERSION' => (float) PHP_VERSION,
		);
		//OS
		$uOS = strtoupper(PHP_OS);
		if (substr($uOS, 0, 3) === 'WIN')
			$this->macros += array('WINDOWS' => 1);
		elseif (substr($uOS, 0, 5) === 'LINUX')
			$this->macros += array('LINUX' => 1, 'UNIX' => 1);
		if (strpos($uOS, 'BSD') !== false)
			$this->macros += array('UNIX' => 1);
	}
	// Options getters and setters
	public function setOption($name, $value) {$this->options[$name] = $value;}
	public function getOption($name = null)
	{
		return isset($name) ? $this->options[$name] : $this->options;
	}
	//Macro getters and setters
	public function define($name, $value) {@$this->macros[$name] = $value;}
	public function isDefined($name) {return (array_key_exists($name, $this->macros) && isset($this->macros[$name]));}
	public function getMacro($name)
	{
		if ($this->isDefined($name))
			return $this->macros[$name];
		fwrite(STDERR, "#CCPP error: attempting to access undefined macro \"$name\"\n");
		return '';
	}

	public function execute($filename, $offset = 0)
	{
		if ($this->options['execute.method'] == 'include') {
			//tmpname()
			fwrite(STDERR, '#error Not implemented execution method "include"');
			exit (1);
		}
		else {
			$evalCode = $this->parseFilename($filename, $offset);
			$evalCode = '?>' . $evalCode . ((substr($evalCode, -2) == '?>')?'<?php ':'');
			return eval($evalCode);
		}
	}
	public function parseFilename($filename, $offset = 0)
	{
		//Fetch file contents
		$code = file_get_contents($filename, false, null, $offset);
		return $this->parse($code);
	}

	public function parse($code)
	{

		// ISO/IEC 9899:1990 (ANSI C99) like translation phases
		// Trigraphs
		if ($this->options['translate.trigraphs'])
			$this->_translationPhase1($code);
		// Line slicing
		if ($this->options['translate.lineSlicing'])
			$this->_translationPhase2($code);
		// Tokenize & Comments
		if ($this->options['translate.comments'])
			$this->_translationPhase3($code);
		// Directives compiler
		if ($this->options['translate.directives'])
			$code = $this->_translationPhase4($code);


		//Interpretate/evaluate compiled code
		if ($this->options['output.format'] == 'compiled')
			; //Do nothing
		elseif ($this->options['output.format'] == 'evaluated')
			$code = $this->_processor_evaluate($code);
		else
			die('Invalid output format: '.(isset($this->options['output.format']) ? $this->options['output.format'] : '<NOT SET>')."\n");

		return $code;
	}

	/**
	 * In phase 1
	 * Trigraphs are translated.
	 *
	 * Reference: ISO/IEC 9899:1990 TC2 (5.1.1.2)
	 */
	protected function _translationPhase1(&$rCode)
	{

		$trigraph_pairs = array(
			'??='  => '#',
			'??/'  => '\\',
			'??\'' => '^',
			'??('  => '[',
			'??)'  => ']',
			'??!'  => '|',
			'??<'  => '{',
			'??>'  => '}',
			'??-'  => '~',
		);
		$rCode = strtr($rCode, $trigraph_pairs);
	}

	/**
	 * In phase 2
	 * The sequence <backslash> <new-line> are removed, so content,
	 * that is sliced using the sequence above on more than one line will be compacted in just one line
	 *
	 * Reference: ISO/IEC 9899:1990 TC2 (5.1.1.2)
	 */

	protected function _translationPhase2(&$rCode)
	{
		$replace_pairs = array(
			"\\\n" => '', // Unix-like
			"\\\r\n" => '', // Windows, DOS, OS/2
			"\\\r" => '', // Mac OS < 9, Apple ][
		);
		$rCode = strtr($rCode, $replace_pairs);
	}

	/**
	 * In phase 3
	 * Comments are replaced by a single <space> character (\x20)
	 *
	 * Reference: ISO/IEC 9899:1990 TC2 (5.1.1.2)
	 */

	protected function _translationPhase3(&$rCode)
	{
		// Copyright headers protection
		// TODO: more
		//preg_replace_callback()

		$patterns = array(
			//            '~/\*(.*?(copyright).*?)\*/~is'
			'~^\s*//.*$~m',
			'~/\*.*?\*/~is',
		);
		$replacements = array(
			//            '',
			' ',
			' ',
		);
		$rCode = preg_replace($patterns, $replacements, $rCode);
	}

	/**
	 * Phase 4 is for
	 * Preprocessor directives processing, macros replacement, #pragma, #include, hash hash replacement
	 *
	 * CCPP does phase 3 tokenization in phase 4 in order to keep tokens in this local variable scope
	 * Also CCPP compiles/translates the processed source and PreProcessor directives to PHP code
	 *
	 * Reference: ISO/IEC 9899:1990 TC2 (5.1.1.2)
	 *
	 * Argument $source is not a reference, to safe time copying the string once again
	 * Otherwise code will be like:
	 * $source = $code;
	 * // Initialize compilated code
	 * $code = '';
	 *
	 */

	protected function _translationPhase4($source) //aka. CCPP - Compilation phase
	{
		//Initialize
		$skipTokens = 0;

		// Initialize compilated code
		$code = '';

		// Prepare PHP snippets
		$sPOT = &$this->_cache_phpSnippets['POT'];
		$sPCT = &$this->_cache_phpSnippets['PCT'];
		$protectPOT = array('<?php' => '<?php echo \'<?php\'; ?>');
		// Phase 3 Tokenization
		// Get source file tokens
		$sourceTokens = token_get_all($source);

		// Phase 4
		// Processing
		foreach ($sourceTokens as $key => &$value) {
			if ($skipTokens > 0) {$skipTokens--; continue;}
			if (is_string($value)) //Operator
				$code .= $value;
			elseif ($value[0] == T_OPEN_TAG) // <?php tag
				$code .= $sPOT.'echo \'<?php\';'.$sPCT."\n";
			elseif ($value[0] == T_WHITESPACE && $this->options['translate.compactWhitespaces']) //Replaces whitespaces with single space character
				$code .= ' ';
			elseif ($value[0] == T_STRING) {
				$code .= $this->_cache_phpSnippets['POT'].$this->_compiler_macro($sourceTokens, $key, $value, $skipTokens).$this->_cache_phpSnippets['PCT'];
			}
			elseif ($value[0] == T_COMMENT && //If tokenizer classified it as comment
				$value[1][0] == '#' && //If has directive operator
				($key == 0 || //Is the first token
					//OR is preceded by whitespace or comment/directive
					(is_array($prevToken = $sourceTokens[$key-1]) &&
						($prevToken[0] == T_WHITESPACE || $prevToken[0] == T_COMMENT || $prevToken[0] == T_OPEN_TAG)
					)
				)
			)
			{ // BEGIN Directives

				//Parse directive with formated input
				sscanf($value[1], $this->_cache_formats['directive'], $directive, $op);
				$directive = strtolower($directive);
				$op = rtrim($op);
				// Two-operand directives
				if ($directive == 'define'){
					//list($op, $op2) = explode(' ', $op, 2); // Not optimized for FMACRO(a, b) (a / b)
					preg_match_all($this->_cache_regexps['defineMacroOperands'], $op, $matches);
					$op = $matches[1][0]; $op2 = $matches[4][0];
					$code .= $sPOT.'$ccpp->_processor_define('.$this->_protector_singleQuoted($op).', '.$this->_protector_singleQuoted($op2).');'.$sPCT."\n";
					unset($matches);
				}
				// One-operand directives
				elseif ($directive == 'include')
					$code .= $sPOT.'echo ($ccpp->_processor_include('. $this->_protector_singleQuoted($op).'));'.$sPCT."\n";
				elseif ($directive == 'if')
					$code .= $sPOT.'if ($ccpp->_processor_ifCondition('.$this->_protector_singleQuoted($op).')):'.$sPCT."\n";
				elseif ($directive == 'elif')
					$code .= $sPOT.'elseif ($ccpp->_processor_ifCondition('.$this->_protector_singleQuoted($op).')):'.$sPCT."\n";
				elseif ($directive == 'ifdef')
					$code .= $sPOT.'if ($ccpp->_processor_isDefined('.$this->_protector_singleQuoted($op).')):'.$sPCT."\n";
				elseif ($directive == 'ifndef')
					$code .= $sPOT.'if (!$ccpp->_processor_isDefined('.$this->_protector_singleQuoted($op).')):'.$sPCT."\n";
				//Non-standard one-operand directives
				elseif ($directive == 'includephp')
					$code .= $sPOT.'echo ($ccpp->_processor_includeAsPhp('.$this->_protector_singleQuoted($op).'));'.$sPCT."\n";
				elseif ($directive == 'literal')
					$code .= $op."\n";
				// Zero-operand directives
				elseif ($directive == 'else')
					$code .= $sPOT.'else:'.$sPCT."\n";
				elseif ($directive == 'endif')
					$code .= $sPOT.'endif;'.$sPCT."\n";
				elseif ($directive == 'error')
					$code .= $sPOT.'$this->_processor_error('.$this->_protector_singleQuoted($op).', '.$value[2].');'.$sPCT."\n";
				elseif ($directive == 'warning')
					$code .= $sPOT.'$this->_processor_warning('.$this->_protector_singleQuoted($op).', '.$value[2].');'.$sPCT."\n";
				// User defined directive (WIP)
				elseif (array_key_exists($directive, $this->user_directives))
					$code .= $sPOT.'$this->_processor_userdirective('.$this->_protector_singleQuoted($op).', '.$value[2].');'.$sPCT."\n";
				else fwrite(STDERR, "#CCPP: Invalid directive \"#$directive\" at line {$value[2]}\n");

			} // END Directives
			elseif ($value[0] == T_DOC_COMMENT); //Skip /** */ PHPDoc comments.
			elseif ($value[1][0] == '/' && $value[1][1] == '/'); //Skip inlined '//' comments (not recognized as T_COMMENT)
			else $code .= strtr($value[1], $protectPOT); //Unrecognized token is appended as is
		}
		return $code; // Return compiled code
	}
	/**
	 * Compiler functions
	 */
	protected function _compiler_macro(&$sourceTokens, $key, &$value, &$rSkip)
	{
		if ( $this->options['translate.functionMacros'] && isset($sourceTokens[$key+1]) && $sourceTokens[$key+1] == '(') { //Function-like macro
			return 'echo '.$this->_compiler_macroFunction($sourceTokens, $key, $rSkip) . ';';
		}
		else //Object-like macro
			return 'echo $ccpp->_processor_macro('.$this->_protector_singleQuoted($value[1]).');';
	}
	protected function _compiler_macroFunction(&$sourceTokens, $key, &$rSkip)
	{
		$skip = 0;
		$k = 0;
		$args = array();
		for ($i = $key+2; (isset($sourceTokens[$i]) && ($iToken = $sourceTokens[$i]) != ')'); $i++) {
			if ($skip > 0) {--$skip; continue;}
			if (is_string($iToken)) {
				if ($iToken == ',') {
					if (isset($args[$k])){
						$args[$k] = $this->_protector_singleQuoted($args[$k]);
						++$k;
					}
				}
				else {
					$args[$k] .= $iToken;
				}
			}
			elseif ($iToken[0] == T_STRING) {
				/* Another Function-like macro... nested macros are still unsupported
				if ($sourceTokens[$i + 1] == '('){
					$args[$k++] = $this->_compiler_macroFunction($sourceTokens, $i, $skip);
				}
				else { */
				$args[$k++] = '$ccpp->_processor_macro('.$this->_protector_singleQuoted($iToken[1]).')';
				@$args[$k] .= $iToken[1];
				/*}*/
			}
			elseif ($iToken[0] != T_WHITESPACE)
				@$args[$k] .= $iToken[1];
		}
		if (isset($sourceTokens[$i])) { //There is matching closing bracket
			if(isset($args[$k])) $args[$k] = $this->_protector_singleQuoted($args[$k]);
			$rSkip = $i - $key;
			return '$ccpp->_processor_macroFunction('.$this->_protector_singleQuoted($sourceTokens[$key][1]).(count($args)?',':'').implode(', ',$args).')';
		}
		else {
			//$this->debug('No matching braket');
			fwrite(STDERR, '#CCPP error: no matching bracket near line '.$sourceTokens[$key][2].' of the code');
			exit (2);
		}
	}

	/**
	 * Evaluator/preprocessor functions
	 */

	protected function _processor_evaluate($code)
	{
		$ccpp = &$this; //Reference to the compiler
		ob_start();
		//Prepare compilation header and footer
		$code = "\n?>" . $code . "<?php\n";
		eval($code);
		return ob_get_clean();
	}
	protected function _processor_macro($token)
	{
		if (isset($this->macros[$token])) return $this->macros[$token];
		else return $token;
	}
	protected function _processor_macroFunction()
	{
		$argv = func_get_args();
		$name = array_shift($argv);
		if (isset($this->functionMacros[$name])) return vsprintf($this->functionMacros[$name], $argv);
		else return $name.'('.implode(', ', $argv).')';
	}

	protected function _processor_include($operand)
	{
		preg_match_all('~("|<)(.*)\\1~', $operand, $matches);
		if ($matches) {
			$filename = $matches[2][0];
			$parsedInclusion = substr($this->parseFilename($filename), $startOffset);
			return $parsedInclusion;
		}
		else return '';
	}
	protected function _processor_includeAsPhp($operand)
	{
		preg_match_all('~("|<)(.*)\\1(\s+([0-9]+))?(\s+([0-9]+))?~', $operand, $matches);
		if (count($matches[0])) {
			$filename = $matches[2][0];
			$startOffset = (int) $matches[4][0];
			if ($maxLength = (int) $matches[6][0])
				$code = file_get_contents($filename, $startOffset, $maxLength);
			else
				$code = file_get_contents($filename, $startOffset);

			if ($code) {
				$sPOT = "<?php\n";
				$code = $sPOT.$code;
				return substr($this->parse($code), strlen($sPOT));
			}
		}
		return '';
	}
	protected function _processor_ifCondition($operand)
	{
		$operand = strtr($operand, $this->macros);
		return eval('return ('.$operand.');');
	}
	protected function _processor_define($macroName, $macroCode)
	{
		if (($pos = strpos($macroName, '(')) !== false) { //Function-like macro
			$name = substr($macroName, 0, $pos);
			$argsString = substr($macroName, $pos + 1, -1);
			$args = preg_split('/\s*,\s*/', $argsString);
			$macroCode = strtr($macroCode, array('%' => '%%'));
			$replace_pairs = array_flip($args);
			$i = 0;
			foreach ($replace_pairs as &$value) $value = '%'.(++$i).'$s';
			$macroCode = strtr($macroCode, $replace_pairs);
			$macroCode = strtr($macroCode, $this->macros);
			$macroCode = preg_replace('~[ ]*(?<!#)(##)(?!#)[ ]*~', '' , $macroCode); // HASH HASH replacement
			$this->functionMacros[$name] = $macroCode;
		}
		else {
			$macroCode = strtr($macroCode, $this->macros);
			$macroCode = preg_replace('~[ ]*(?<!#)(##)(?!#)[ ]*~', '' , $macroCode); // HASH HASH replacement
			$this->macros[$macroName] = $macroCode;
		}
	}
	protected function _processor_userdirective($operand)
	{

	}
	protected function _processor_isDefined($macroName)
	{
		return isset($this->macros[$macroName]);
	}
	protected function _processor_error($message, $line = __LINE__)
	{
		fwrite(STDERR, '#error: "'.$message.'" on line '.$line." in the source\n");
		$this->_processor_exitClean(1);
	}
	protected function _processor_warning($message, $line = __LINE__)
	{
		fwrite(STDERR, '#warning: "'.$message.'" on line '.$line." in the source\n");
	}
	protected function _processor_exitClean($status = 0)
	{
		while(ob_get_level()) ob_end_clean();
		exit ($status);
	}
	protected function _processor_clearBuffers()
	{
		while(ob_get_level()) ob_end_clean();
	}
	protected function _protector_quote($string) // Not utilized
	{
		return '"'.addcslashes($string).'"'; // C  style
	}
	protected function _protector_escapeSingleQuote($string)
	{
		return addcslashes($string, '\'');
	}
	protected function _protector_singleQuoted($string)
	{
		return '\''.$this->_protector_escapeSingleQuote($string).'\'';
	}
}
