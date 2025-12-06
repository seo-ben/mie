<?php

var_dump(0 == '0');    // true
var_dump(0 == 'abc');  // true 😱
var_dump(0 === '0');   // false ✅
