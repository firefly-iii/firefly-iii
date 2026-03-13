<?php

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Engine;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class CustomExpressionLanguage extends ExpressionLanguage
{
    protected function registerFunctions(): void
    {
        $basicPhpFunctions = ['min', 'max', 'substr', 'strlen', 'strpos'];
        foreach ($basicPhpFunctions as $function) {
            $this->addFunction(ExpressionFunction::fromPhp($function));
        }
    }
}
