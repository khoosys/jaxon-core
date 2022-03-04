<?php

/**
 * Question.php - A confirmation question for a Jaxon request
 *
 * This class adds a confirmation question which is asked before calling a Jaxon function.
 *
 * @package jaxon-core
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Ui\Dialogs;

class Question implements \Jaxon\Contracts\Dialogs\Question
{
    /**
     * Get the script which makes a call only if the user answers yes to the given question
     *
     * @param string            $sQuestion
     * @param string            $sYesScript
     * @param string            $sNoScript
     *
     * @return string
     */
    public function confirm(string $sQuestion, string $sYesScript, string $sNoScript): string
    {
        if(!$sNoScript)
        {
            return 'if(confirm(' . $sQuestion . ')){' . $sYesScript . ';}';
        }
        return 'if(confirm(' . $sQuestion . ')){' . $sYesScript . ';}else{' . $sNoScript . ';}';
    }
}
