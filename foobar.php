<?php

$array = [];

for ($i = 1; $i <= 100; $i++) {
    if ($i % 3 == 0 && $i % 5 == 0) {
        $array[] = "FooBar";
    } elseif ($i % 3 == 0) {
        $array[] = "Foo";
    } elseif ($i % 5 == 0) {
        $array[] = "Bar";
    } else {
        $array[] = $i;
    }
}
print_r($array);
