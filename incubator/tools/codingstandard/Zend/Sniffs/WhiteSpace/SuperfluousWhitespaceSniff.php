<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category  Zend
 * @package   Zend_CodingStandard
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 * @version   $Id: $
 */

/**
 * Zend_Sniffs_WhiteSpace_SuperfluousWhitespaceSniff.
 *
 * Checks that no whitespace proceeds the first content of the file, exists
 * after the last content of the file, resides after content on any line, or
 * are two empty lines in functions.
 *
 * @category  Zend
 * @package   Zend_CodingStandard
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Sniffs_WhiteSpace_SuperfluousWhitespaceSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_OPEN_TAG,
                T_CLOSE_TAG,
                T_WHITESPACE,
                T_COMMENT,
               );

    }//end register()


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_OPEN_TAG) {

            /*
                Check for start of file whitespace.
            */

            // If its the first token, then there is no space.
            if ($stackPtr === 0) {
                return;
            }

            for ($i = ($stackPtr - 1); $i >= 0; $i--) {
                // If we find something that isn't inline html then there is something previous in the file.
                if ($tokens[$i]['type'] !== 'T_INLINE_HTML') {
                    return;
                }

                // If we have ended up with inline html make sure it isn't just whitespace.
                $tokenContent = trim($tokens[$i]['content']);
                if ($tokenContent !== '') {
                    return;
                }
            }

            $phpcsFile->addError('Additional whitespace found at start of file', $stackPtr);

        } else if ($tokens[$stackPtr]['code'] === T_CLOSE_TAG) {

            /*
                Check for end of file whitespace.
            */

            if (isset($tokens[($stackPtr + 1)]) === false) {
                // The close PHP token is the last in the file.
                return;
            }

            for ($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {
                // If we find something that isn't inline html then there
                // is more to the file.
                if ($tokens[$i]['type'] !== 'T_INLINE_HTML') {
                    return;
                }

                // If we have ended up with inline html make sure it
                // isn't just whitespace.
                $tokenContent = trim($tokens[$i]['content']);
                if (empty($tokenContent) === false) {
                    return;
                }
            }

            $phpcsFile->addError('Additional whitespace found at end of file', $stackPtr);

        } else {

            /*
                Check for end of line whitespace.
            */

            if (strpos($tokens[$stackPtr]['content'], $phpcsFile->eolChar) === false) {
                return;
            }

            $tokenContent = rtrim($tokens[$stackPtr]['content'], $phpcsFile->eolChar);
            if (empty($tokenContent) === false) {
                if (preg_match('|^.*\s+$|', $tokenContent) !== 0) {
                    $phpcsFile->addError('Whitespace found at end of line', $stackPtr);
                }
            }

            /*
                Check for multiple blanks lines in a function.
            */

            if ($phpcsFile->hasCondition($stackPtr, T_FUNCTION) === true) {
                if ($tokens[($stackPtr - 1)]['line'] < $tokens[$stackPtr]['line'] && $tokens[($stackPtr - 2)]['line'] === $tokens[($stackPtr - 1)]['line']) {
                    // This is an empty line and the line before this one is not
                    //  empty, so this could be the start of a multiple empty
                    // line block.
                    $next  = $phpcsFile->findNext(T_WHITESPACE, $stackPtr, null, true);
                    $lines = $tokens[$next]['line'] - $tokens[$stackPtr]['line'];
                    if ($lines > 1) {
                        $error = "Functions must not contain multiple empty lines in a row; found $lines empty lines";
                        $phpcsFile->addError($error, $stackPtr);
                    }
                }
            }

        }//end if

    }//end process()


}//end class

?>
