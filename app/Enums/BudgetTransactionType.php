<?php

namespace App\Enums;

enum BudgetTransactionType: string
{
    case Income = 'INCOME';
    case Expense = 'EXPENSE';
    case Adjustment = 'ADJUSTMENT';
    case OpeningBalance = 'OPENING_BALANCE';
}
