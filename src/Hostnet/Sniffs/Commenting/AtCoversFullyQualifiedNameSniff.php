<?php
declare(strict_types=1);
/**
 * @copyright 2017 Hostnet B.V.
 */

/**
 * This Sniff sniffs that all files examined have a correct @covers notation + added a fixer for those cases.
 */
class Hostnet_Sniffs_Commenting_AtCoversFullyQualifiedNameSniff extends PEAR_Sniffs_Commenting_FileCommentSniff
{
    const ERROR_TYPE    = 'AtCoversNeedsFQCN';
    const ERROR_MESSAGE = 'Covers annotation should use fully qualified class name (it should start with a "\") "%s"';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_DOC_COMMENT_TAG];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
     * @param int                  $stack_ptr  The position of the current token in the stack passed in $tokens.
     *
     * @return int returns a stack pointer. The sniff will not be called again on the current file until the returned
     *              stack pointer is reached. Return (count($tokens) + 1) to skip the rest of the file.
     */
    public function process(PHP_CodeSniffer_File $phpcs_file, $stack_ptr)
    {
        $tokens = $phpcs_file->getTokens();

        // Is it a unit test?
        if (false === strpos($phpcs_file->getFilename(), 'Test.php')) {
            // No, skip the rest of it
            return (count($tokens) + 1);
        }

        // The tag i found is it a @covers tag?
        if ('@covers' !== $tokens[$stack_ptr]['content']) {
            return $stack_ptr;
        }

        $class_name_ptr = $phpcs_file->findNext(T_DOC_COMMENT_STRING, $stack_ptr + 1, null, false, null, true);

        // Did i find a string after the @covers tag?
        if (false === $class_name_ptr) {
            return $stack_ptr;
        }

        $class_name = $tokens[$class_name_ptr]['content'];

        // Does the class name start with a backslash?
        if ('\\' === $class_name[0]) {
            return $stack_ptr;
        }

        // Handle error
        if ($phpcs_file->addFixableError(self::ERROR_MESSAGE, $class_name_ptr, self::ERROR_TYPE)) {
            $phpcs_file->fixer->addContentBefore($class_name_ptr, '\\');
        }

        return $stack_ptr;
    }
}
