<?php

declare(strict_types=1);

namespace Goat\Driver\Error;

class TableDoesNotExistError extends DatabaseObjectDoesNotExistError implements RelationDoesNotExistError
{
}
