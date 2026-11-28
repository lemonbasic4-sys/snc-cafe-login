<?php
declare(strict_types=1);

function snc_is_list(array $array): bool
{
    $index = 0;
    foreach ($array as $key => $_) {
        if ($key !== $index++) {
            return false;
        }
    }
    return true;
}
