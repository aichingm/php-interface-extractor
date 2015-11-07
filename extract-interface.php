<?php

ini_set("display_errors", 0);

function usage() {
    echo <<<EOF
Usage: 
    with arguments: 
        >php extract-interface.php path/to/MyClass.php MyClass path/to/IMyClass.php IMyClass
        
    interactive mode (Note: the readline extension needs to be installed)
        >php extract-interface.php -i
    

EOF;
}
function errorexit($code, $message) {
    echo $message;
    echo PHP_EOL;
    exit($code);
}
if ($argc != 5 && !in_array("-i", $argv)) {
    usage();
    exit(1);
}
if (in_array("-i", $argv)) {
    if (!function_exists("readline")) {
        errorexit(20, "Can not find the extension \"readline\".");
    }
    $path = getcwd() . DIRECTORY_SEPARATOR . trim(readline("Path to the class file: "));
    if (!is_file($path) || !is_readable($path)) {
        errorexit(2, "Can not read from class file (parameter 1).");
    }
    require_once $path;
    readline_completion_function(function () {
        $classes = get_declared_classes();
        return $classes;
    });

    $class = trim(readline("Class name: "));
    if (!class_exists($class)) {
        errorexit(3, "Can not find class \"$class\" (parameter 2).");
    }
    $Ipath = getcwd() . DIRECTORY_SEPARATOR . trim(readline("Path to the new interface file: "));

    if (is_file($Ipath)) {
        errorexit(4, "Can not write to interface file (parameter 3). File exists.");
    }
    $Iclass = trim(readline("Name for the new interfae: "));
} else {
    $path = getcwd() . DIRECTORY_SEPARATOR . $argv[1];
    $class = $argv[2];
    $Ipath = getcwd() . DIRECTORY_SEPARATOR . $argv[3];
    $Inamespace = substr($argv[4], 0, strrpos($argv[4], "\\"));
    $Iclass = substr($argv[4], strrpos($argv[4], "\\") + 1);
    if (!is_file($path) || !is_readable($path)) {
        errorexit(2, "Can not read from class file (parameter 1).");
    }
    require_once $path;
    if (!class_exists($class)) {
        errorexit(3, "Can not find class \"$class\" (parameter 2).");
    }
    if (is_file($Ipath)) {
        errorexit(4, "Can not write to interface file (parameter 3). File exists.");
    }
}

$rClass = new ReflectionClass($class);
$rMethods = $rClass->getMethods();
$interfaceContent = "<?php " . PHP_EOL;
$interfaceContent .= "namespace " . $rClass->getNamespaceName() . ";" . PHP_EOL . PHP_EOL;
$interfaceContent .= "interface " . $Iclass . "{" . PHP_EOL . PHP_EOL;

foreach ($rMethods as $rMethod) {
    if ($rMethod->isPublic() && !$rMethod->isStatic()) {
        $interfaceContent .= "   public function ";
        $interfaceContent .= $rMethod->getName() . "(";
        foreach ($rMethod->getParameters() as $rParameter) {
            if ($rParameter->isArray()) {
                $interfaceContent .= "array ";
            }
            if ($rParameter->isCallable()) {
                $interfaceContent .= "callable ";
            }
            if ($rParameter->getClass() != "") {
                $interfaceContent .= "\\" . $rParameter->getClass()->getName() . " ";
            }

            $interfaceContent .= "$" . $rParameter->getName();
            if ($rParameter->isDefaultValueAvailable()) {
                $interfaceContent .= " = " . (is_null($rParameter->getDefaultValue()) ? "null" : str_replace("\n", '', var_export($rParameter->getDefaultValue(), true)));
            }
            if ($rParameter != end($rMethod->getParameters())) {
                $interfaceContent .= ", ";
            }
        }
        $interfaceContent .= ");" . PHP_EOL;
    }
}
$interfaceContent .= PHP_EOL . "}" . PHP_EOL;
file_put_contents($Ipath, $interfaceContent);
