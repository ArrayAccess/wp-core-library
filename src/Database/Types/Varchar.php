<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database\Types;

use Doctrine\DBAL\Types\StringType;

class Varchar extends StringType
{
    public const NAME = 'varchar';

    public function getName(): string
    {
        return self::NAME;
    }
}
