<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BillStatus: string implements HasColor, HasLabel
{
    case Overdue = 'overdue';
    case Partial = 'partial';
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Void = 'void';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Overdue => 'danger',
            self::Partial, self::Unpaid => 'warning',
            self::Paid => 'success',
            self::Void => 'gray',
        };
    }
}
