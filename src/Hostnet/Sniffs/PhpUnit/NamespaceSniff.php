<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types = 1);
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit 5 deprecated the \PHPUnit_Framework_TestCase class
 * in favour of \PHPUnit\Framework\TestCase. In PHPUnit 6 the old
 * class is removed. Detect and replace usages of the old class.
 */
class Hostnet_Sniffs_PhpUnit_NamespaceSniff implements PHP_CodeSniffer_Sniff
{
    const NON_NAMESPACE_TEST_CLASS = 'PHPUnit_Framework_TestCase';
    const PARENT_TEST_CLASS        = 'TestCase';
    const WARNING                  = 'Usage of '
                                     . self::NON_NAMESPACE_TEST_CLASS
                                     . ' found, please use '
                                     . TestCase::class
                                     . '.';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * The PHP open tag is returned, because we want to find the
     * first statement in the file.
     *
     * @return array
     */
    public function register(): array
    {
        return [T_EXTENDS];
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
     * @param int                  $stack_ptr The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return int
     */
    public function process(PHP_CodeSniffer_File $phpcs_file, $stack_ptr): int
    {
        $tokens = $phpcs_file->getTokens();

        // Find the parent class
        $class_ptr = $phpcs_file->findNext(T_STRING, $stack_ptr + 1, null, false, null, true);

        if (false === $class_ptr) {
            return $stack_ptr;
        }

        $class = $tokens[$class_ptr]['content'];
        if (strtolower(self::NON_NAMESPACE_TEST_CLASS) === strtolower($class)) {
            if ($phpcs_file->addFixableWarning(self::WARNING, $class_ptr)) {
                $this->fix($phpcs_file, $class_ptr);
            }
        }

        return $class_ptr;
    }

    private function fix(PHP_CodeSniffer_File $phpcs_file, int $class_ptr)
    {
        $tokens    = $phpcs_file->getTokens();
        $stack_ptr = $phpcs_file->findNext([T_CLASS, T_USE], 0);

        // If there are no use statements yet, we have to add a newline afterwards.
        if (T_CLASS === $tokens[$stack_ptr]['code']) {
            $previous = $phpcs_file->findPrevious(T_WHITESPACE, $stack_ptr - 1, $stack_ptr - 2, true);

            $previous = $phpcs_file->findPrevious(
                [
                    T_DOC_COMMENT_OPEN_TAG,
                    T_DOC_COMMENT,
                    T_DOC_COMMENT_CLOSE_TAG,
                    T_DOC_COMMENT_STAR,
                    T_DOC_COMMENT_STRING,
                    T_DOC_COMMENT_TAG,
                    T_DOC_COMMENT_WHITESPACE,
                    T_COMMENT,
                ],
                false !== $previous ? $previous - 1 : $stack_ptr - 1,
                0,
                true
            );

            $phpcs_file->fixer->addContent(
                false !== $previous ? $previous: $stack_ptr - 1,
                'use ' . TestCase::class . ';' . PHP_EOL . PHP_EOL
            );
        } else {
            // Add use statement
            $phpcs_file->fixer->addContentBefore($stack_ptr, 'use ' . TestCase::class . ';' . PHP_EOL);
        }

        // Remove the \ before PHPUnit_Framework_TestCase
        if (T_NS_SEPARATOR === $tokens[$class_ptr - 1]['code']) {
            $phpcs_file->fixer->replaceToken($class_ptr - 1, '');
        }

        // Replace PHPUnit_Framework_TestCase and with TestCase
        $phpcs_file->fixer->replaceToken($class_ptr, self::PARENT_TEST_CLASS);
    }
}
